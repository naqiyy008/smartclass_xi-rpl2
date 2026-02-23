<?php
include "config/koneksi.php";
include "config/helpers.php";

require_roles(["admin", "guru"]);

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = require_class_context();

if (isset($_GET["hapus"])) {
    $idHapus = (int) $_GET["hapus"];
    mysqli_query($conn, "DELETE FROM tb_jadwal_pelajaran WHERE id_jadwal = {$idHapus}");
    set_flash("success", "Jadwal berhasil dihapus.");
    header("Location: jadwal_pelajaran.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["simpan_jadwal"])) {
    $idJadwal = (int) ($_POST["id_jadwal"] ?? 0);
    $idKelas = (int) ($_POST["id_kelas"] ?? 0);
    $idGuru = (int) ($_POST["id_guru"] ?? 0);
    $mapel = trim($_POST["mapel"] ?? "");
    $hari = $_POST["hari"] ?? "Senin";
    $jamMulai = $_POST["jam_mulai"] ?? "07:00";
    $jamSelesai = $_POST["jam_selesai"] ?? "08:00";
    $ruang = trim($_POST["ruang"] ?? "");
    $idTahun = (int) ($_POST["id_tahun_ajaran"] ?? 0);
    $semester = ($_POST["semester"] ?? "ganjil") === "genap" ? "genap" : "ganjil";
    $keterangan = trim($_POST["keterangan"] ?? "");

    if ($idKelas <= 0 || $idGuru <= 0 || $mapel === "") {
        set_flash("danger", "Data jadwal belum lengkap.");
        header("Location: jadwal_pelajaran.php");
        exit;
    }
    if ($idTahun <= 0) {
        $idTahun = (int) $selectedTahun;
    }

    if ($idJadwal > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE tb_jadwal_pelajaran
             SET id_kelas = ?, id_guru = ?, mapel = ?, hari = ?, jam_mulai = ?, jam_selesai = ?, ruang = ?, id_tahun_ajaran = ?, semester = ?, keterangan = ?
             WHERE id_jadwal = ?"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iisssssissi", $idKelas, $idGuru, $mapel, $hari, $jamMulai, $jamSelesai, $ruang, $idTahun, $semester, $keterangan, $idJadwal);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        set_flash("success", "Jadwal berhasil diperbarui.");
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO tb_jadwal_pelajaran
             (id_kelas, id_guru, mapel, hari, jam_mulai, jam_selesai, ruang, id_tahun_ajaran, semester, keterangan)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iisssssiss", $idKelas, $idGuru, $mapel, $hari, $jamMulai, $jamSelesai, $ruang, $idTahun, $semester, $keterangan);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        set_flash("success", "Jadwal berhasil ditambahkan.");
    }

    header("Location: jadwal_pelajaran.php");
    exit;
}

$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];
    $qEdit = mysqli_query($conn, "SELECT * FROM tb_jadwal_pelajaran WHERE id_jadwal = {$idEdit} LIMIT 1");
    $editData = $qEdit ? mysqli_fetch_assoc($qEdit) : null;
}

$where = ["1=1"];
if ($selectedTahun) {
    $where[] = "j.id_tahun_ajaran = " . (int) $selectedTahun;
}
$where[] = "j.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($selectedKelas) {
    $where[] = "j.id_kelas = " . (int) $selectedKelas;
}
$whereSql = implode(" AND ", $where);

$jadwalRes = mysqli_query(
    $conn,
    "SELECT j.*, k.nama_kelas, g.nama_guru, ta.nama_tahun
     FROM tb_jadwal_pelajaran j
     JOIN tb_kelas k ON k.id_kelas = j.id_kelas
     JOIN tbguru g ON g.id_guru = j.id_guru
     LEFT JOIN tb_tahun_ajaran ta ON ta.id_tahun_ajaran = j.id_tahun_ajaran
     WHERE {$whereSql}
     ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai ASC"
);
$jadwalList = $jadwalRes ? fetch_all_assoc($jadwalRes) : [];

$kelasList = get_kelas_list($conn);
$guruList = fetch_all_assoc(mysqli_query($conn, "SELECT id_guru, nama_guru, mapel FROM tbguru ORDER BY nama_guru ASC"));
$tahunList = get_tahun_ajaran_list($conn);

$pageTitle = "Jadwal Pelajaran";
include "partials/header.php";
?>

<section class="panel mb-4">
    <h2 class="panel-title"><?= $editData ? "Edit Jadwal" : "Tambah Jadwal"; ?></h2>
    <form method="post" class="row g-3">
        <?php if ($editData): ?>
            <input type="hidden" name="id_jadwal" value="<?= (int) $editData["id_jadwal"]; ?>">
        <?php endif; ?>
        <div class="col-md-3">
            <label class="form-label">Kelas</label>
            <select name="id_kelas" class="form-select" required>
                <option value="">Pilih kelas</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <?php $val = $editData["id_kelas"] ?? $selectedKelas; ?>
                    <option value="<?= (int) $kelas["id_kelas"]; ?>" <?= (int) $val === (int) $kelas["id_kelas"] ? "selected" : ""; ?>>
                        <?= e($kelas["nama_kelas"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Guru</label>
            <select name="id_guru" class="form-select" required>
                <option value="">Pilih guru</option>
                <?php foreach ($guruList as $guru): ?>
                    <option value="<?= (int) $guru["id_guru"]; ?>" <?= (int) ($editData["id_guru"] ?? 0) === (int) $guru["id_guru"] ? "selected" : ""; ?>>
                        <?= e($guru["nama_guru"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Mapel</label>
            <input type="text" name="mapel" class="form-control" value="<?= e($editData["mapel"] ?? ""); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Hari</label>
            <?php $hariNow = $editData["hari"] ?? "Senin"; ?>
            <select name="hari" class="form-select">
                <?php foreach (["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"] as $hari): ?>
                    <option value="<?= e($hari); ?>" <?= $hariNow === $hari ? "selected" : ""; ?>><?= e($hari); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jam Mulai</label>
            <input type="time" name="jam_mulai" class="form-control" value="<?= e($editData["jam_mulai"] ?? "07:00"); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-control" value="<?= e($editData["jam_selesai"] ?? "08:00"); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ruang</label>
            <input type="text" name="ruang" class="form-control" value="<?= e($editData["ruang"] ?? ""); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tahun Ajaran</label>
            <select name="id_tahun_ajaran" class="form-select">
                <?php foreach ($tahunList as $tahun): ?>
                    <?php $val = $editData["id_tahun_ajaran"] ?? $selectedTahun; ?>
                    <option value="<?= (int) $tahun["id_tahun_ajaran"]; ?>" <?= (int) $val === (int) $tahun["id_tahun_ajaran"] ? "selected" : ""; ?>>
                        <?= e($tahun["nama_tahun"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Semester</label>
            <?php $sem = $editData["semester"] ?? $selectedSemester; ?>
            <select name="semester" class="form-select">
                <option value="ganjil" <?= $sem === "ganjil" ? "selected" : ""; ?>>Ganjil</option>
                <option value="genap" <?= $sem === "genap" ? "selected" : ""; ?>>Genap</option>
            </select>
        </div>
        <div class="col-md-9">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="<?= e($editData["keterangan"] ?? ""); ?>">
        </div>
        <div class="col-md-3 d-grid">
            <label class="form-label">&nbsp;</label>
            <button type="submit" name="simpan_jadwal" class="btn btn-accent"><?= $editData ? "Update" : "Simpan"; ?></button>
        </div>
    </form>
</section>

<section class="panel">
    <h2 class="panel-title">Daftar Jadwal</h2>
    <div class="table-wrap">
        <table class="table table-smart table-striped align-middle">
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Waktu</th>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Guru</th>
                    <th>Ruang</th>
                    <th>Tahun/Semester</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jadwalList)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada jadwal pada filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($jadwalList as $jadwal): ?>
                        <tr>
                            <td><?= e($jadwal["hari"]); ?></td>
                            <td><?= e(substr((string) $jadwal["jam_mulai"], 0, 5)); ?> - <?= e(substr((string) $jadwal["jam_selesai"], 0, 5)); ?></td>
                            <td><?= e($jadwal["nama_kelas"]); ?></td>
                            <td><?= e($jadwal["mapel"]); ?></td>
                            <td><?= e($jadwal["nama_guru"]); ?></td>
                            <td><?= e($jadwal["ruang"] ?? "-"); ?></td>
                            <td><?= e($jadwal["nama_tahun"] ?? "-"); ?> / <?= e(ucfirst((string) $jadwal["semester"])); ?></td>
                            <td><?= e($jadwal["keterangan"] ?? "-"); ?></td>
                            <td>
                                <a href="jadwal_pelajaran.php?edit=<?= (int) $jadwal["id_jadwal"]; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="jadwal_pelajaran.php?hapus=<?= (int) $jadwal["id_jadwal"]; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus jadwal ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "partials/footer.php"; ?>

