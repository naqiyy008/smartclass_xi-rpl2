<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = get_selected_kelas_id();
$idUser = (int) $_SESSION["id_user"];
$idSiswa = null;
$kelasList = get_kelas_list($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pilih_kelas_aktif"])) {
    $kelasAktif = (int) ($_POST["kelas_aktif"] ?? 0);
    if ($kelasAktif > 0) {
        $_SESSION["filter_kelas"] = $kelasAktif;
        set_flash("success", "Kelas aktif berhasil dipilih.");
    } else {
        set_flash("danger", "Silakan pilih kelas yang valid.");
    }
    header("Location: dashboard.php");
    exit;
}

if (is_siswa()) {
    $stmtSiswa = mysqli_prepare($conn, "SELECT id_siswa, id_kelas FROM tbsiswa WHERE id_user = ? LIMIT 1");
    if ($stmtSiswa) {
        mysqli_stmt_bind_param($stmtSiswa, "i", $idUser);
        mysqli_stmt_execute($stmtSiswa);
        $resSiswa = mysqli_stmt_get_result($stmtSiswa);
        $dataSiswa = $resSiswa ? mysqli_fetch_assoc($resSiswa) : null;
        mysqli_stmt_close($stmtSiswa);
        if ($dataSiswa) {
            $idSiswa = (int) $dataSiswa["id_siswa"];
            if (!$selectedKelas && (int) $dataSiswa["id_kelas"] > 0) {
                $selectedKelas = (int) $dataSiswa["id_kelas"];
                $_SESSION["filter_kelas"] = $selectedKelas;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["simpan_kelas_siswa"]) && is_siswa() && $idSiswa) {
    $kelasSiswa = (int) ($_POST["kelas_siswa"] ?? 0);
    if ($kelasSiswa > 0) {
        $qNamaKelas = mysqli_query($conn, "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = {$kelasSiswa} LIMIT 1");
        $rowKelas = $qNamaKelas ? mysqli_fetch_assoc($qNamaKelas) : null;
        if ($rowKelas) {
            $namaKelas = mysqli_real_escape_string($conn, (string) $rowKelas["nama_kelas"]);
            mysqli_query($conn, "UPDATE tbsiswa SET id_kelas = {$kelasSiswa}, kelas = '{$namaKelas}' WHERE id_siswa = {$idSiswa}");
            $_SESSION["filter_kelas"] = $kelasSiswa;
            set_flash("success", "Kelas profil siswa berhasil diperbarui.");
        }
    }
    header("Location: dashboard.php");
    exit;
}

$selectedKelas = get_selected_kelas_id();
$hasClassContext = $selectedKelas !== null && $selectedKelas > 0;

$whereTask = ["1=1"];
if ($selectedTahun) {
    $whereTask[] = "t.id_tahun_ajaran = " . (int) $selectedTahun;
}
$whereTask[] = "t.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($hasClassContext) {
    $whereTask[] = "t.id_kelas = " . (int) $selectedKelas;
} else {
    $whereTask[] = "1 = 0";
}
$taskFilterSql = implode(" AND ", $whereTask);

$stat = [
    "tugas_online" => 0,
    "tugas_offline" => 0,
    "pengumpulan" => 0,
    "hadir_hari_ini" => 0,
];

$qOnline = mysqli_query($conn, "SELECT COUNT(*) total FROM tbtugas t WHERE {$taskFilterSql} AND t.mode_pengumpulan = 'online'");
if ($qOnline) {
    $stat["tugas_online"] = (int) mysqli_fetch_assoc($qOnline)["total"];
}

$qOffline = mysqli_query($conn, "SELECT COUNT(*) total FROM tbtugas t WHERE {$taskFilterSql} AND t.mode_pengumpulan = 'offline'");
if ($qOffline) {
    $stat["tugas_offline"] = (int) mysqli_fetch_assoc($qOffline)["total"];
}

if (is_siswa() && $idSiswa) {
    $qKumpul = mysqli_query(
        $conn,
        "SELECT COUNT(*) total
         FROM tb_pengumpulan_tugas p
         JOIN tbtugas t ON t.id_tugas = p.id_tugas
         WHERE p.id_siswa = {$idSiswa} AND {$taskFilterSql}"
    );
    if ($qKumpul) {
        $stat["pengumpulan"] = (int) mysqli_fetch_assoc($qKumpul)["total"];
    }
} else {
    $qKumpul = mysqli_query(
        $conn,
        "SELECT COUNT(*) total
         FROM tb_pengumpulan_tugas p
         JOIN tbtugas t ON t.id_tugas = p.id_tugas
         WHERE {$taskFilterSql}"
    );
    if ($qKumpul) {
        $stat["pengumpulan"] = (int) mysqli_fetch_assoc($qKumpul)["total"];
    }
}

$whereAbsensi = ["a.tanggal = CURDATE()"];
if ($selectedTahun) {
    $whereAbsensi[] = "a.id_tahun_ajaran = " . (int) $selectedTahun;
}
$whereAbsensi[] = "a.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($hasClassContext) {
    $whereAbsensi[] = "a.id_kelas = " . (int) $selectedKelas;
} else {
    $whereAbsensi[] = "1 = 0";
}
$qHadir = mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM tbabsensi a
     WHERE " . implode(" AND ", $whereAbsensi) . " AND a.status = 'hadir'"
);
if ($qHadir) {
    $stat["hadir_hari_ini"] = (int) mysqli_fetch_assoc($qHadir)["total"];
}

$deadlineQuery = "SELECT t.id_tugas, t.judul, t.deadline_at, t.mode_pengumpulan, k.nama_kelas
                  FROM tbtugas t
                  LEFT JOIN tb_kelas k ON k.id_kelas = t.id_kelas
                  WHERE {$taskFilterSql} AND t.deadline_at IS NOT NULL
                  ORDER BY t.deadline_at ASC
                  LIMIT 6";
$deadlineResult = mysqli_query($conn, $deadlineQuery);
$deadlines = $deadlineResult ? fetch_all_assoc($deadlineResult) : [];

$wherePengumuman = [];
if ($selectedTahun) {
    $wherePengumuman[] = "(p.id_tahun_ajaran IS NULL OR p.id_tahun_ajaran = " . (int) $selectedTahun . ")";
}
if ($hasClassContext) {
    $wherePengumuman[] = "(p.id_kelas IS NULL OR p.id_kelas = " . (int) $selectedKelas . ")";
} else {
    $wherePengumuman[] = "1 = 0";
}
$announcementFilter = empty($wherePengumuman) ? "1=1" : implode(" AND ", $wherePengumuman);
$announcementResult = mysqli_query(
    $conn,
    "SELECT p.judul, p.isi, p.dibuat_pada, u.nama, k.nama_kelas
     FROM tb_pengumuman p
     JOIN tbuser u ON u.id_user = p.id_user
     LEFT JOIN tb_kelas k ON k.id_kelas = p.id_kelas
     WHERE {$announcementFilter}
     ORDER BY p.dibuat_pada DESC
     LIMIT 5"
);
$announcements = $announcementResult ? fetch_all_assoc($announcementResult) : [];

$kelasAktifNama = "-";
if ($hasClassContext) {
    $qKelasNama = mysqli_query($conn, "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = " . (int) $selectedKelas . " LIMIT 1");
    if ($qKelasNama && ($kelasNama = mysqli_fetch_assoc($qKelasNama))) {
        $kelasAktifNama = (string) $kelasNama["nama_kelas"];
    }
}

$tahunNama = "-";
if ($selectedTahun) {
    $qTahun = mysqli_query($conn, "SELECT nama_tahun FROM tb_tahun_ajaran WHERE id_tahun_ajaran = " . (int) $selectedTahun . " LIMIT 1");
    if ($qTahun && ($tahun = mysqli_fetch_assoc($qTahun))) {
        $tahunNama = (string) $tahun["nama_tahun"];
    }
}

$pageTitle = "Dashboard";
include "partials/header.php";
?>

<section class="panel mb-4">
    <h2 class="panel-title">Pilih Kelas Aktif</h2>
    <form method="post" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label">Pilih kelas dulu sebelum lanjut ke fitur lainnya</label>
            <select name="kelas_aktif" class="form-select" required>
                <option value="">Pilih kelas</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <option value="<?= (int) $kelas["id_kelas"]; ?>" <?= $selectedKelas === (int) $kelas["id_kelas"] ? "selected" : ""; ?>>
                        <?= e($kelas["nama_kelas"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-grid">
            <button class="btn btn-accent" type="submit" name="pilih_kelas_aktif">Gunakan Kelas Ini</button>
        </div>
    </form>

    <?php if (is_siswa()): ?>
        <form method="post" class="row g-2 align-items-end mt-2">
            <div class="col-md-8">
                <label class="form-label">Atur kelas profil siswa</label>
                <select name="kelas_siswa" class="form-select" required>
                    <option value="">Pilih kelas</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= (int) $kelas["id_kelas"]; ?>" <?= $selectedKelas === (int) $kelas["id_kelas"] ? "selected" : ""; ?>>
                            <?= e($kelas["nama_kelas"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-grid">
                <button class="btn btn-outline-secondary" type="submit" name="simpan_kelas_siswa">Simpan Kelas Saya</button>
            </div>
        </form>
    <?php endif; ?>

</section>

<section class="panel mb-4">
    <h2 class="panel-title">Profil Login</h2>
    <div class="row g-2">
        <div class="col-md-3">
            <small class="text-muted d-block">Nama</small>
            <strong><?= e($_SESSION["nama"]); ?></strong>
        </div>
        <div class="col-md-2">
            <small class="text-muted d-block">Role</small>
            <strong><?= e(ucfirst($_SESSION["role"])); ?></strong>
        </div>
        <div class="col-md-3">
            <small class="text-muted d-block">Kelas Aktif</small>
            <strong><?= e($kelasAktifNama); ?></strong>
        </div>
        <div class="col-md-2">
            <small class="text-muted d-block">Tahun/Semester</small>
            <strong><?= e($tahunNama); ?> / <?= e(ucfirst($selectedSemester)); ?></strong>
        </div>
        <div class="col-md-2 d-grid">
            <a class="btn btn-outline-secondary btn-sm" href="profil.php">Buka Profil</a>
        </div>
    </div>
</section>

<?php if (!$hasClassContext): ?>
    <div class="alert alert-warning">Kelas aktif belum dipilih. Silakan pilih kelas terlebih dahulu.</div>
<?php endif; ?>

<section class="card-grid mb-4">
    <div class="card smart-card">
        <div class="card-body">
            <h6 class="text-muted mb-2">Tugas Online</h6>
            <h2 class="mb-0"><?= (int) $stat["tugas_online"]; ?></h2>
        </div>
    </div>
    <div class="card smart-card">
        <div class="card-body">
            <h6 class="text-muted mb-2">Tugas Offline</h6>
            <h2 class="mb-0"><?= (int) $stat["tugas_offline"]; ?></h2>
        </div>
    </div>
    <div class="card smart-card">
        <div class="card-body">
            <h6 class="text-muted mb-2"><?= is_siswa() ? "Tugas Terkumpul" : "Total Pengumpulan"; ?></h6>
            <h2 class="mb-0"><?= (int) $stat["pengumpulan"]; ?></h2>
        </div>
    </div>
    <div class="card smart-card">
        <div class="card-body">
            <h6 class="text-muted mb-2">Hadir Hari Ini</h6>
            <h2 class="mb-0"><?= (int) $stat["hadir_hari_ini"]; ?></h2>
        </div>
    </div>
</section>

<section class="panel mb-4">
    <h2 class="panel-title">Akses Cepat</h2>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-accent <?= !$hasClassContext ? "disabled" : ""; ?>" href="tugas_online.php">Kelola Tugas Online</a>
        <a class="btn btn-outline-secondary <?= !$hasClassContext ? "disabled" : ""; ?>" href="tugas_offline.php">Lihat Tugas Offline</a>
        <a class="btn btn-outline-secondary <?= !$hasClassContext ? "disabled" : ""; ?>" href="absensi.php">Absensi</a>
        <?php if (is_admin() || is_guru()): ?>
            <a class="btn btn-outline-secondary <?= !$hasClassContext ? "disabled" : ""; ?>" href="jadwal_pelajaran.php">Jadwal Pelajaran</a>
            <a class="btn btn-outline-secondary <?= !$hasClassContext ? "disabled" : ""; ?>" href="pengumpulan.php">Pengumpulan Tugas</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary <?= !$hasClassContext ? "disabled" : ""; ?>" href="mapel_kelas.php">Cek Mapel Kelas</a>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="panel h-100">
            <h2 class="panel-title">Deadline Terdekat</h2>
            <?php if (empty($deadlines)): ?>
                <p class="text-muted mb-0">Belum ada deadline pada filter saat ini.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($deadlines as $deadline): ?>
                        <?php
                        $deadlineAt = strtotime((string) $deadline["deadline_at"]);
                        $isLate = $deadlineAt < time();
                        $closeToDeadline = !$isLate && $deadlineAt < strtotime("+2 days");
                        $className = "task-item panel " . ($isLate ? "task-late" : ($closeToDeadline ? "task-warning" : ""));
                        ?>
                        <div class="<?= e($className); ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h6 class="mb-1"><?= e($deadline["judul"]); ?></h6>
                                    <small class="text-muted"><?= e($deadline["nama_kelas"] ?? "Semua kelas"); ?> | <?= e(ucfirst((string) $deadline["mode_pengumpulan"])); ?></small>
                                </div>
                                <span class="badge text-bg-dark"><?= e(date("d M Y H:i", $deadlineAt)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="panel h-100">
            <h2 class="panel-title">Pengumuman Terbaru</h2>
            <?php if (empty($announcements)): ?>
                <p class="text-muted mb-0">Belum ada pengumuman.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($announcements as $notice): ?>
                        <article class="panel">
                            <h6 class="mb-1"><?= e($notice["judul"]); ?></h6>
                            <p class="mb-2 small"><?= nl2br(e($notice["isi"])); ?></p>
                            <small class="text-muted">
                                <?= e($notice["nama"]); ?>
                                | <?= e($notice["nama_kelas"] ?? "Semua kelas"); ?>
                                | <?= e(date("d M Y H:i", strtotime((string) $notice["dibuat_pada"]))); ?>
                            </small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include "partials/footer.php"; ?>

