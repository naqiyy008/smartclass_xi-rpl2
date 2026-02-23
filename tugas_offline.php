<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = get_selected_kelas_id();
$idUser = (int) $_SESSION["id_user"];

if (is_siswa()) {
    $idSiswa = find_id_siswa_by_user($conn, $idUser);
    if ($idSiswa) {
        $qKelas = mysqli_query($conn, "SELECT id_kelas FROM tbsiswa WHERE id_siswa = {$idSiswa} LIMIT 1");
        if ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
            if (!$selectedKelas && (int) $row["id_kelas"] > 0) {
                $selectedKelas = (int) $row["id_kelas"];
                $_SESSION["filter_kelas"] = $selectedKelas;
            }
        }
    }
}
$selectedKelas = require_class_context();

if (isset($_GET["hapus"]) && (is_admin() || is_guru())) {
    $idHapus = (int) $_GET["hapus"];
    if ($idHapus > 0) {
        mysqli_query($conn, "DELETE FROM tbtugas WHERE id_tugas = {$idHapus} AND mode_pengumpulan = 'offline'");
        set_flash("success", "Tugas offline berhasil dihapus.");
    }
    header("Location: tugas_offline.php");
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
    $tahunInput = (int) ($_POST["id_tahun_ajaran"] ?? 0);
    $semesterInput = ($_POST["semester"] ?? "ganjil") === "genap" ? "genap" : "ganjil";
    $lampiranFile = null;

    if ($judul === "" || $mapel === "" || $idGuru <= 0 || $idKelas <= 0 || $deadlineDate === "") {
        set_flash("danger", "Data tugas offline belum lengkap.");
        header("Location: tugas_offline.php");
        exit;
    }

    if (!empty($_FILES["lampiran"]["name"])) {
        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }
        $original = $_FILES["lampiran"]["name"];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $safeName = "offline_" . time() . "_" . bin2hex(random_bytes(4)) . ($ext ? "." . $ext : "");
        if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], "uploads/" . $safeName)) {
            $lampiranFile = $safeName;
        }
    }

    if ($tahunInput <= 0) {
        $tahunInput = (int) $selectedTahun;
    }

    $deadlineAt = $deadlineDate . " " . $deadlineTime . ":00";
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tbtugas
        (judul, mapel, id_guru, deadline, keterangan, jenis, id_kelas, id_tahun_ajaran, semester, mode_pengumpulan, deadline_at, lampiran_file, created_by)
        VALUES (?, ?, ?, ?, ?, 'offline', ?, ?, ?, 'offline', ?, ?, ?)"
    );

    if ($stmt) {
        $deadlineDateOnly = $deadlineDate;
        mysqli_stmt_bind_param(
            $stmt,
            "ssissiisssi",
            $judul,
            $mapel,
            $idGuru,
            $deadlineDateOnly,
            $keterangan,
            $idKelas,
            $tahunInput,
            $semesterInput,
            $deadlineAt,
            $lampiranFile,
            $idUser
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Tugas offline berhasil ditambahkan.");
    } else {
        set_flash("danger", "Gagal menambahkan tugas offline.");
    }

    header("Location: tugas_offline.php");
    exit;
}

$guruList = fetch_all_assoc(mysqli_query($conn, "SELECT id_guru, nama_guru, mapel FROM tbguru ORDER BY nama_guru ASC"));
$kelasList = get_kelas_list($conn);
$tahunList = get_tahun_ajaran_list($conn);

$where = ["t.mode_pengumpulan = 'offline'"];
if ($selectedTahun) {
    $where[] = "t.id_tahun_ajaran = " . (int) $selectedTahun;
}
$where[] = "t.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($selectedKelas) {
    $where[] = "t.id_kelas = " . (int) $selectedKelas;
}
$whereSql = implode(" AND ", $where);

$tasksResult = mysqli_query(
    $conn,
    "SELECT t.*, g.nama_guru, k.nama_kelas, ta.nama_tahun
     FROM tbtugas t
     LEFT JOIN tbguru g ON g.id_guru = t.id_guru
     LEFT JOIN tb_kelas k ON k.id_kelas = t.id_kelas
     LEFT JOIN tb_tahun_ajaran ta ON ta.id_tahun_ajaran = t.id_tahun_ajaran
     WHERE {$whereSql}
     ORDER BY t.deadline_at ASC, t.id_tugas DESC"
);
$tasks = $tasksResult ? fetch_all_assoc($tasksResult) : [];

$pageTitle = "Tugas Offline";
include "partials/header.php";
?>

<?php if (is_admin() || is_guru()): ?>
<section class="panel mb-4">
    <h2 class="panel-title">Tambah Tugas Offline</h2>
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
        <div class="col-md-12">
            <label class="form-label">Lampiran Materi/Instruksi (opsional)</label>
            <input type="file" name="lampiran" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-12">
            <button type="submit" name="tambah_tugas" class="btn btn-accent">Simpan Tugas Offline</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel-title">Daftar Tugas Offline</h2>
    <?php if (empty($tasks)): ?>
        <p class="text-muted mb-0">Belum ada tugas offline pada filter ini.</p>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($tasks as $task): ?>
                <?php $deadlineAt = strtotime((string) $task["deadline_at"]); ?>
                <article class="panel task-item <?= ($deadlineAt && $deadlineAt < time()) ? "task-warning" : ""; ?>">
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
                        <?php if (is_admin() || is_guru()): ?>
                            <a href="tugas_offline.php?hapus=<?= (int) $task["id_tugas"]; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus tugas offline ini?')">Hapus</a>
                        <?php endif; ?>
                    </div>

                    <?php if ($task["keterangan"]): ?>
                        <p class="mt-2 mb-2"><?= nl2br(e($task["keterangan"])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($task["lampiran_file"])): ?>
                        <a href="uploads/<?= e($task["lampiran_file"]); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Unduh Lampiran</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include "partials/footer.php"; ?>

