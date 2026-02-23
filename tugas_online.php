<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = get_selected_kelas_id();
$idUser = (int) $_SESSION["id_user"];

$idSiswa = is_siswa() ? find_id_siswa_by_user($conn, $idUser) : null;
if (is_siswa() && $idSiswa) {
    $qOwnKelas = mysqli_query($conn, "SELECT id_kelas FROM tbsiswa WHERE id_siswa = {$idSiswa} LIMIT 1");
    if ($qOwnKelas && ($rowKelas = mysqli_fetch_assoc($qOwnKelas))) {
        if (!$selectedKelas && (int) $rowKelas["id_kelas"] > 0) {
            $selectedKelas = (int) $rowKelas["id_kelas"];
            $_SESSION["filter_kelas"] = $selectedKelas;
        }
    }
}
$selectedKelas = require_class_context();

if (isset($_GET["hapus"]) && (is_admin() || is_guru())) {
    $idHapus = (int) $_GET["hapus"];
    if ($idHapus > 0) {
        mysqli_query($conn, "DELETE FROM tb_pengumpulan_tugas WHERE id_tugas = {$idHapus}");
        mysqli_query($conn, "DELETE FROM tbtugas WHERE id_tugas = {$idHapus}");
        set_flash("success", "Tugas online berhasil dihapus.");
    }
    header("Location: tugas_online.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah_tugas"]) && (is_admin() || is_guru())) {
    $judul = trim($_POST["judul"] ?? "");
    $mapel = trim($_POST["mapel"] ?? "");
    $idGuru = (int) ($_POST["id_guru"] ?? 0);
    $idKelas = (int) ($_POST["id_kelas"] ?? 0);
    $deadlineDate = trim($_POST["deadline_date"] ?? "");
    $deadlineTime = trim($_POST["deadline_time"] ?? "23:59");
    $keterangan = trim($_POST["keterangan"] ?? "");
    $linkPengumpulan = trim($_POST["link_pengumpulan"] ?? "");
    $tahunInput = (int) ($_POST["id_tahun_ajaran"] ?? 0);
    $semesterInput = ($_POST["semester"] ?? "ganjil") === "genap" ? "genap" : "ganjil";

    if ($judul === "" || $mapel === "" || $idGuru <= 0 || $idKelas <= 0 || $deadlineDate === "") {
        set_flash("danger", "Data tugas belum lengkap.");
        header("Location: tugas_online.php");
        exit;
    }

    $deadlineAt = $deadlineDate . " " . $deadlineTime . ":00";
    $lampiranFile = null;

    if (!empty($_FILES["lampiran"]["name"])) {
        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }
        $original = $_FILES["lampiran"]["name"];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $safeName = "task_" . time() . "_" . bin2hex(random_bytes(4)) . ($ext ? "." . $ext : "");
        if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], "uploads/" . $safeName)) {
            $lampiranFile = $safeName;
        }
    }

    if ($tahunInput <= 0) {
        $tahunInput = (int) $selectedTahun;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tbtugas
        (judul, mapel, id_guru, deadline, keterangan, jenis, link_pengumpulan, id_kelas, id_tahun_ajaran, semester, mode_pengumpulan, deadline_at, lampiran_file, created_by)
        VALUES (?, ?, ?, ?, ?, 'online', ?, ?, ?, ?, 'online', ?, ?, ?)"
    );
    if ($stmt) {
        $deadlineDateOnly = $deadlineDate;
        mysqli_stmt_bind_param(
            $stmt,
            "ssisssiisssi",
            $judul,
            $mapel,
            $idGuru,
            $deadlineDateOnly,
            $keterangan,
            $linkPengumpulan,
            $idKelas,
            $tahunInput,
            $semesterInput,
            $deadlineAt,
            $lampiranFile,
            $idUser
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Tugas online berhasil ditambahkan.");
    } else {
        set_flash("danger", "Gagal menambah tugas online.");
    }

    header("Location: tugas_online.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["kumpulkan"]) && is_siswa() && $idSiswa) {
    $idTugas = (int) ($_POST["id_tugas"] ?? 0);
    $format = $_POST["format_pengumpulan"] ?? "file";
    $linkTugas = trim($_POST["link_tugas"] ?? "");
    $keterangan = trim($_POST["keterangan"] ?? "");
    $teksTugas = trim($_POST["teks_tugas"] ?? "");

    $qTask = mysqli_query($conn, "SELECT id_tugas, deadline_at FROM tbtugas WHERE id_tugas = {$idTugas} AND mode_pengumpulan = 'online' LIMIT 1");
    $task = $qTask ? mysqli_fetch_assoc($qTask) : null;

    if (!$task) {
        set_flash("danger", "Tugas tidak ditemukan.");
        header("Location: tugas_online.php");
        exit;
    }

    $deadlineAt = strtotime((string) $task["deadline_at"]);
    if ($deadlineAt && time() > $deadlineAt) {
        set_flash("danger", "Tenggat waktu sudah lewat. Tugas tidak bisa dikumpulkan.");
        header("Location: tugas_online.php");
        exit;
    }

    $allowedFormat = ["file", "link", "teks"];
    if (!in_array($format, $allowedFormat, true)) {
        $format = "file";
    }

    $fileTugas = null;
    if ($format === "file") {
        if (empty($_FILES["file_tugas"]["name"])) {
            set_flash("danger", "Silakan pilih file tugas.");
            header("Location: tugas_online.php");
            exit;
        }
        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }
        $original = $_FILES["file_tugas"]["name"];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $safeName = "submit_" . $idSiswa . "_" . $idTugas . "_" . time() . ($ext ? "." . $ext : "");
        if (!move_uploaded_file($_FILES["file_tugas"]["tmp_name"], "uploads/" . $safeName)) {
            set_flash("danger", "Upload file gagal.");
            header("Location: tugas_online.php");
            exit;
        }
        $fileTugas = $safeName;
    } elseif ($format === "link") {
        if ($linkTugas === "") {
            set_flash("danger", "Link tugas wajib diisi.");
            header("Location: tugas_online.php");
            exit;
        }
    } else {
        if ($teksTugas === "") {
            set_flash("danger", "Teks tugas wajib diisi.");
            header("Location: tugas_online.php");
            exit;
        }
        $keterangan = ($keterangan === "" ? "" : $keterangan . PHP_EOL) . "Jawaban: " . $teksTugas;
    }

    $today = date("Y-m-d");
    $telat = 0;

    $existing = mysqli_query($conn, "SELECT id_kumpul FROM tb_pengumpulan_tugas WHERE id_tugas = {$idTugas} AND id_siswa = {$idSiswa} LIMIT 1");
    $rowExisting = $existing ? mysqli_fetch_assoc($existing) : null;

    if ($rowExisting) {
        $idKumpul = (int) $rowExisting["id_kumpul"];
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE tb_pengumpulan_tugas
             SET file_tugas = ?, link_tugas = ?, format_pengumpulan = ?, tanggal_kumpul = ?, keterangan = ?, telat = ?, id_user = ?
             WHERE id_kumpul = ?"
        );
        if ($stmt) {
            $emptyFile = $fileTugas ?? "";
            mysqli_stmt_bind_param($stmt, "sssssiii", $emptyFile, $linkTugas, $format, $today, $keterangan, $telat, $idUser, $idKumpul);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO tb_pengumpulan_tugas
             (id_tugas, id_siswa, file_tugas, tanggal_kumpul, keterangan, id_user, link_tugas, format_pengumpulan, telat)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt) {
            $emptyFile = $fileTugas ?? "";
            mysqli_stmt_bind_param($stmt, "iisssissi", $idTugas, $idSiswa, $emptyFile, $today, $keterangan, $idUser, $linkTugas, $format, $telat);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    set_flash("success", "Tugas berhasil dikumpulkan.");
    header("Location: tugas_online.php");
    exit;
}

$guruList = fetch_all_assoc(mysqli_query($conn, "SELECT id_guru, nama_guru, mapel FROM tbguru ORDER BY nama_guru ASC"));
$kelasList = get_kelas_list($conn);
$tahunList = get_tahun_ajaran_list($conn);

$where = ["t.mode_pengumpulan = 'online'"];
if ($selectedTahun) {
    $where[] = "t.id_tahun_ajaran = " . (int) $selectedTahun;
}
$where[] = "t.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($selectedKelas) {
    $where[] = "t.id_kelas = " . (int) $selectedKelas;
}
$whereSql = implode(" AND ", $where);

$qTasks = mysqli_query(
    $conn,
    "SELECT t.*, g.nama_guru, k.nama_kelas, ta.nama_tahun,
            COUNT(DISTINCT s.id_siswa) AS total_siswa,
            COUNT(DISTINCT p.id_siswa) AS total_kumpul
     FROM tbtugas t
     LEFT JOIN tbguru g ON g.id_guru = t.id_guru
     LEFT JOIN tb_kelas k ON k.id_kelas = t.id_kelas
     LEFT JOIN tb_tahun_ajaran ta ON ta.id_tahun_ajaran = t.id_tahun_ajaran
     LEFT JOIN tbsiswa s ON s.id_kelas = t.id_kelas
     LEFT JOIN tb_pengumpulan_tugas p ON p.id_tugas = t.id_tugas
     WHERE {$whereSql}
     GROUP BY t.id_tugas
     ORDER BY t.deadline_at ASC, t.id_tugas DESC"
);
$tasks = $qTasks ? fetch_all_assoc($qTasks) : [];

$submittedMap = [];
if (is_siswa() && $idSiswa) {
    $qSub = mysqli_query($conn, "SELECT id_tugas, id_kumpul, tanggal_kumpul FROM tb_pengumpulan_tugas WHERE id_siswa = {$idSiswa}");
    if ($qSub) {
        while ($sub = mysqli_fetch_assoc($qSub)) {
            $submittedMap[(int) $sub["id_tugas"]] = $sub;
        }
    }
}

$doneCount = 0;
if (is_siswa()) {
    foreach ($tasks as $task) {
        if (isset($submittedMap[(int) $task["id_tugas"]])) {
            $doneCount++;
        }
    }
}

$pageTitle = "Tugas Online";
include "partials/header.php";
?>

<?php if (is_admin() || is_guru()): ?>
<section class="panel mb-4">
    <h2 class="panel-title">Tambah Tugas Online</h2>
    <form method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Judul Tugas</label>
            <input type="text" name="judul" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Mata Pelajaran</label>
            <input type="text" name="mapel" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Guru</label>
            <select name="id_guru" class="form-select" required>
                <option value="">Pilih guru</option>
                <?php foreach ($guruList as $guru): ?>
                    <option value="<?= (int) $guru["id_guru"]; ?>"><?= e($guru["nama_guru"]); ?> - <?= e($guru["mapel"]); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kelas</label>
            <select name="id_kelas" class="form-select" required>
                <option value="">Pilih kelas</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <option value="<?= (int) $kelas["id_kelas"]; ?>" <?= $selectedKelas === (int) $kelas["id_kelas"] ? "selected" : ""; ?>>
                        <?= e($kelas["nama_kelas"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tahun Ajaran</label>
            <select name="id_tahun_ajaran" class="form-select">
                <?php foreach ($tahunList as $tahun): ?>
                    <option value="<?= (int) $tahun["id_tahun_ajaran"]; ?>" <?= $selectedTahun === (int) $tahun["id_tahun_ajaran"] ? "selected" : ""; ?>>
                        <?= e($tahun["nama_tahun"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select">
                <option value="ganjil" <?= $selectedSemester === "ganjil" ? "selected" : ""; ?>>Ganjil</option>
                <option value="genap" <?= $selectedSemester === "genap" ? "selected" : ""; ?>>Genap</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal Deadline</label>
            <input type="date" name="deadline_date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Jam Deadline</label>
            <input type="time" name="deadline_time" class="form-control" value="23:59" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Link Pengumpulan (opsional)</label>
            <input type="url" name="link_pengumpulan" class="form-control" placeholder="https://...">
        </div>
        <div class="col-md-6">
            <label class="form-label">Lampiran Tugas (opsional)</label>
            <input type="file" name="lampiran" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-12">
            <button type="submit" name="tambah_tugas" class="btn btn-accent">Simpan Tugas Online</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (is_siswa()): ?>
<section class="panel mb-4">
    <h2 class="panel-title">Progres Tugas Anda</h2>
    <p class="mb-0">
        Total tugas online: <strong><?= count($tasks); ?></strong> |
        Sudah dikumpulkan: <strong><?= $doneCount; ?></strong> |
        Belum dikumpulkan: <strong><?= max(0, count($tasks) - $doneCount); ?></strong>
    </p>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel-title">Daftar Tugas Online</h2>
    <?php if (empty($tasks)): ?>
        <p class="text-muted mb-0">Belum ada tugas online pada filter ini.</p>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($tasks as $task): ?>
                <?php
                $idTugas = (int) $task["id_tugas"];
                $deadlineAt = strtotime((string) $task["deadline_at"]);
                $expired = $deadlineAt && time() > $deadlineAt;
                $submitted = isset($submittedMap[$idTugas]);
                ?>
                <article class="panel task-item <?= $expired ? "task-late" : ""; ?>">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1"><?= e($task["judul"]); ?></h5>
                            <p class="mb-1 text-muted">
                                <?= e($task["mapel"]); ?> â€¢ <?= e($task["nama_guru"] ?? "-"); ?> â€¢ <?= e($task["nama_kelas"] ?? "-"); ?>
                            </p>
                            <small class="text-muted">
                                <?= e($task["nama_tahun"] ?? "-"); ?> â€¢ Semester <?= e(ucfirst((string) $task["semester"])); ?> â€¢ Deadline <?= e(date("d M Y H:i", $deadlineAt)); ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-soft">Progress kelas <?= (int) $task["total_kumpul"]; ?>/<?= (int) $task["total_siswa"]; ?></span>
                            <?php if (is_admin() || is_guru()): ?>
                                <div class="mt-2">
                                    <a href="tugas_online.php?hapus=<?= $idTugas; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus tugas ini?')">Hapus</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($task["keterangan"]): ?>
                        <p class="mt-2 mb-2"><?= nl2br(e($task["keterangan"])); ?></p>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php if (!empty($task["link_pengumpulan"])): ?>
                            <a href="<?= e($task["link_pengumpulan"]); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Buka Link Tugas</a>
                        <?php endif; ?>
                        <?php if (!empty($task["lampiran_file"])): ?>
                            <a href="uploads/<?= e($task["lampiran_file"]); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Unduh Lampiran</a>
                        <?php endif; ?>
                    </div>

                    <?php if (is_siswa()): ?>
                        <div class="mb-2">
                            <?php if ($submitted): ?>
                                <span class="badge text-bg-success">Sudah dikumpulkan</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning">Belum dikumpulkan</span>
                            <?php endif; ?>
                            <?php if ($expired): ?>
                                <span class="badge text-bg-danger">Lewat tenggat, terkunci</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!$expired): ?>
                            <form method="post" enctype="multipart/form-data" class="row g-2">
                                <input type="hidden" name="id_tugas" value="<?= $idTugas; ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Format</label>
                                    <select name="format_pengumpulan" class="form-select form-select-sm" required>
                                        <option value="file">File</option>
                                        <option value="link">Link</option>
                                        <option value="teks">Teks</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">File</label>
                                    <input type="file" name="file_tugas" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Link</label>
                                    <input type="url" name="link_tugas" class="form-control form-control-sm" placeholder="https://...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Teks</label>
                                    <input type="text" name="teks_tugas" class="form-control form-control-sm" placeholder="Jawaban singkat">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Catatan (opsional)">
                                </div>
                                <div class="col-md-3 d-grid">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="kumpulkan" class="btn btn-accent btn-sm"><?= $submitted ? "Update" : "Kumpulkan"; ?></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include "partials/footer.php"; ?>

