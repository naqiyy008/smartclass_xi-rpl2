<?php
if (!isset($pageTitle)) {
    $pageTitle = 'SmartClass';
}
$showFilters = $showFilters ?? true;
$flash = get_flash();
$loggedIn = isset($_SESSION['id_user'], $_SESSION['role']);
$showQuickMaster = $loggedIn && $showFilters && $pageTitle === 'Dashboard' && (is_admin() || is_guru());

$tahunList = [];
$semesterList = [];
$kelasList = [];
$selectedTahun = null;
$selectedSemester = 'ganjil';
$selectedKelas = null;

if ($loggedIn && isset($conn) && $showFilters) {
    $tahunList = get_tahun_ajaran_list($conn);
    $semesterList = get_semester_list($conn);
    $kelasList = get_kelas_list($conn);
    $selectedTahun = get_selected_tahun_ajaran_id($conn);
    $selectedSemester = get_selected_semester($conn);
    $selectedKelas = get_selected_kelas_id();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> | SmartClass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div id="page-loader">
    <div class="loader-mark"></div>
    <p>Memuat halaman...</p>
</div>

<?php if ($loggedIn): ?>
<nav class="navbar navbar-expand-lg smart-nav sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand brand-title" href="dashboard.php">SmartClass SMKN 1 Probolinggo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#smartNav" aria-controls="smartNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="smartNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="tugas_online.php">Tugas Online</a></li>
                <li class="nav-item"><a class="nav-link" href="tugas_offline.php">Tugas Offline</a></li>
                <li class="nav-item"><a class="nav-link" href="absensi.php">Absensi</a></li>
                <?php if (is_admin() || is_guru()): ?>
                    <li class="nav-item"><a class="nav-link" href="jadwal_pelajaran.php">Jadwal</a></li>
                    <li class="nav-item"><a class="nav-link" href="pengumpulan.php">Pengumpulan</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="mapel_kelas.php">Mapel Kelas</a></li>
                <?php if (is_admin() || is_guru()): ?>
                    <li class="nav-item"><a class="nav-link" href="master_data.php">Master Data</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="pengumuman.php">Pengumuman</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="pb-5">
    <div class="container py-4">
        <?php if ($loggedIn): ?>
            <div class="hero-strip">
                <div class="hero-meta">
                    <h1 class="hero-title mb-1"><?= e($pageTitle); ?></h1>
                    <p class="mb-0 text-muted">
                        Login sebagai <strong><?= e($_SESSION['nama']); ?></strong>
                        (<?= e(ucfirst($_SESSION['role'])); ?>)
                    </p>
                    <button type="button" id="btn-back-prev" class="btn btn-outline-secondary btn-sm btn-back-prev mt-2">
                        Kembali
                    </button>
                </div>
                <?php if ($showFilters): ?>
                    <form action="set_filter.php" method="post" class="row g-2 align-items-end filter-form">
                        <div class="col-12 col-md">
                            <label class="form-label mb-1">Tahun Ajaran</label>
                            <select name="filter_tahun_ajaran" class="form-select form-select-sm">
                                <?php foreach ($tahunList as $tahun): ?>
                                    <option value="<?= (int) $tahun['id_tahun_ajaran']; ?>" <?= $selectedTahun === (int) $tahun['id_tahun_ajaran'] ? 'selected' : ''; ?>>
                                        <?= e($tahun['nama_tahun']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md">
                            <label class="form-label mb-1">Semester</label>
                            <select name="filter_semester" class="form-select form-select-sm">
                                <?php if (empty($semesterList)): ?>
                                    <option value="ganjil" <?= $selectedSemester === 'ganjil' ? 'selected' : ''; ?>>Ganjil</option>
                                    <option value="genap" <?= $selectedSemester === 'genap' ? 'selected' : ''; ?>>Genap</option>
                                <?php else: ?>
                                    <?php foreach ($semesterList as $semester): ?>
                                        <option value="<?= e($semester['kode_semester']); ?>" <?= $selectedSemester === $semester['kode_semester'] ? 'selected' : ''; ?>>
                                            <?= e($semester['nama_semester']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md">
                            <label class="form-label mb-1">Kelas</label>
                            <select name="filter_kelas" class="form-select form-select-sm">
                                <?php if (empty($kelasList)): ?>
                                    <option value="">Belum ada kelas</option>
                                <?php else: ?>
                                    <option value="">Pilih kelas</option>
                                    <?php foreach ($kelasList as $kelas): ?>
                                        <option value="<?= (int) $kelas['id_kelas']; ?>" <?= $selectedKelas === (int) $kelas['id_kelas'] ? 'selected' : ''; ?>>
                                            <?= e($kelas['nama_kelas']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto">
                            <button class="btn btn-accent btn-sm w-100" type="submit">Terapkan</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($showQuickMaster): ?>
                    <div class="w-100 quick-master-wrap">
                        <details class="quick-master">
                            <summary>Tambah Tahun Ajaran / Semester / Kelas di sini</summary>
                            <div class="row g-3 mt-2">
                                <div class="col-lg-4">
                                    <form action="master_quick_store.php" method="post" class="row g-2">
                                        <input type="hidden" name="action" value="tambah_tahun">
                                        <div class="col-12">
                                            <label class="form-label mb-1">Tahun Ajaran</label>
                                            <input type="text" name="nama_tahun" class="form-control form-control-sm" placeholder="Contoh: 2026/2027" required>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="quick_tahun_aktif" name="status_aktif">
                                                <label class="form-check-label" for="quick_tahun_aktif">Set Aktif</label>
                                            </div>
                                            <button class="btn btn-accent btn-sm" type="submit">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4">
                                    <form action="master_quick_store.php" method="post" class="row g-2">
                                        <input type="hidden" name="action" value="simpan_semester">
                                        <div class="col-5">
                                            <label class="form-label mb-1">Semester</label>
                                            <select name="kode_semester" class="form-select form-select-sm">
                                                <option value="ganjil">Ganjil</option>
                                                <option value="genap">Genap</option>
                                            </select>
                                        </div>
                                        <div class="col-7">
                                            <label class="form-label mb-1">Nama Semester</label>
                                            <input type="text" name="nama_semester" class="form-control form-control-sm" placeholder="Contoh: Semester Ganjil">
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="quick_semester_aktif" name="semester_aktif">
                                                <label class="form-check-label" for="quick_semester_aktif">Set Aktif</label>
                                            </div>
                                            <button class="btn btn-accent btn-sm" type="submit">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4">
                                    <form action="master_quick_store.php" method="post" class="row g-2">
                                        <input type="hidden" name="action" value="tambah_kelas">
                                        <div class="col-12">
                                            <label class="form-label mb-1">Nama Kelas</label>
                                            <input type="text" name="nama_kelas" class="form-control form-control-sm" placeholder="Contoh: XI RPL 3" required>
                                        </div>
                                        <div class="col-4">
                                            <input type="text" name="tingkat" class="form-control form-control-sm" placeholder="XI">
                                        </div>
                                        <div class="col-5">
                                            <input type="text" name="jurusan" class="form-control form-control-sm" placeholder="RPL">
                                        </div>
                                        <div class="col-3 d-grid">
                                            <button class="btn btn-accent btn-sm" type="submit">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?> shadow-sm"><?= e($flash['message']); ?></div>
        <?php endif; ?>

