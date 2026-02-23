<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = require_class_context();

$kelasNama = "-";
$qKelas = mysqli_query($conn, "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = {$selectedKelas} LIMIT 1");
if ($qKelas && ($kelas = mysqli_fetch_assoc($qKelas))) {
    $kelasNama = (string) $kelas["nama_kelas"];
}

$tahunNama = "-";
if ($selectedTahun) {
    $qTahun = mysqli_query($conn, "SELECT nama_tahun FROM tb_tahun_ajaran WHERE id_tahun_ajaran = {$selectedTahun} LIMIT 1");
    if ($qTahun && ($tahun = mysqli_fetch_assoc($qTahun))) {
        $tahunNama = (string) $tahun["nama_tahun"];
    }
}

$jadwalRes = mysqli_query(
    $conn,
    "SELECT j.mapel, g.nama_guru, COUNT(*) AS total_jam
     FROM tb_jadwal_pelajaran j
     JOIN tbguru g ON g.id_guru = j.id_guru
     WHERE j.id_kelas = {$selectedKelas}
       AND j.id_tahun_ajaran = " . (int) $selectedTahun . "
       AND j.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'
     GROUP BY j.mapel, g.nama_guru
     ORDER BY j.mapel ASC"
);
$jadwalMapel = $jadwalRes ? fetch_all_assoc($jadwalRes) : [];

$tugasRes = mysqli_query(
    $conn,
    "SELECT t.mapel,
            SUM(CASE WHEN t.mode_pengumpulan = 'online' THEN 1 ELSE 0 END) AS total_online,
            SUM(CASE WHEN t.mode_pengumpulan = 'offline' THEN 1 ELSE 0 END) AS total_offline,
            COUNT(*) AS total_tugas
     FROM tbtugas t
     WHERE t.id_kelas = {$selectedKelas}
       AND t.id_tahun_ajaran = " . (int) $selectedTahun . "
       AND t.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'
     GROUP BY t.mapel
     ORDER BY t.mapel ASC"
);
$tugasMapel = $tugasRes ? fetch_all_assoc($tugasRes) : [];

$pageTitle = "Cek Mapel Kelas";
include "partials/header.php";
?>

<section class="panel mb-4">
    <h2 class="panel-title">Ringkasan Kelas</h2>
    <p class="mb-0">
        Kelas: <strong><?= e($kelasNama); ?></strong> |
        Tahun Ajaran: <strong><?= e($tahunNama); ?></strong> |
        Semester: <strong><?= e(ucfirst($selectedSemester)); ?></strong>
    </p>
</section>

<div class="row g-4">
    <div class="col-lg-6">
        <section class="panel h-100">
            <h2 class="panel-title">Mapel dari Jadwal</h2>
            <div class="table-wrap">
                <table class="table table-smart table-striped">
                    <thead>
                        <tr>
                            <th>Mapel</th>
                            <th>Guru</th>
                            <th>Total Slot/Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jadwalMapel)): ?>
                            <tr><td colspan="3" class="text-center text-muted">Belum ada data jadwal untuk kelas ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($jadwalMapel as $row): ?>
                                <tr>
                                    <td><?= e($row["mapel"]); ?></td>
                                    <td><?= e($row["nama_guru"]); ?></td>
                                    <td><?= (int) $row["total_jam"]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel h-100">
            <h2 class="panel-title">Mapel dari Tugas</h2>
            <div class="table-wrap">
                <table class="table table-smart table-striped">
                    <thead>
                        <tr>
                            <th>Mapel</th>
                            <th>Tugas Online</th>
                            <th>Tugas Offline</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tugasMapel)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada data tugas untuk kelas ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tugasMapel as $row): ?>
                                <tr>
                                    <td><?= e($row["mapel"]); ?></td>
                                    <td><?= (int) $row["total_online"]; ?></td>
                                    <td><?= (int) $row["total_offline"]; ?></td>
                                    <td><?= (int) $row["total_tugas"]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php include "partials/footer.php"; ?>

