<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = get_selected_kelas_id();
$idUser = (int) $_SESSION["id_user"];

$idSiswaLogin = is_siswa() ? find_id_siswa_by_user($conn, $idUser) : null;
if (is_siswa() && $idSiswaLogin) {
    $qKelas = mysqli_query($conn, "SELECT id_kelas FROM tbsiswa WHERE id_siswa = {$idSiswaLogin} LIMIT 1");
    if ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
        if (!$selectedKelas && (int) $row["id_kelas"] > 0) {
            $selectedKelas = (int) $row["id_kelas"];
            $_SESSION["filter_kelas"] = $selectedKelas;
        }
    }
}
$selectedKelas = require_class_context();

if (isset($_GET["hapus"]) && (is_admin() || is_guru())) {
    $idHapus = (int) $_GET["hapus"];
    mysqli_query($conn, "DELETE FROM tbabsensi WHERE id_absensi = {$idHapus}");
    set_flash("success", "Data absensi berhasil dihapus.");
    header("Location: absensi.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["simpan"]) && (is_admin() || is_guru())) {
    $tanggal = $_POST["tanggal"] ?? date("Y-m-d");
    $status = $_POST["status"] ?? "hadir";
    $keterangan = trim($_POST["keterangan"] ?? "");
    $namaManual = trim($_POST["nama_siswa_manual"] ?? "");
    $idSiswa = (int) ($_POST["id_siswa"] ?? 0);
    $idKelas = (int) ($_POST["id_kelas"] ?? 0);
    $tahunInput = (int) ($_POST["id_tahun_ajaran"] ?? 0);
    $semesterInput = ($_POST["semester"] ?? "ganjil") === "genap" ? "genap" : "ganjil";

    if ($idSiswa <= 0 && $namaManual === "") {
        set_flash("danger", "Isi nama siswa manual atau pilih data siswa.");
        header("Location: absensi.php");
        exit;
    }

    if ($idSiswa > 0) {
        $qSiswa = mysqli_query($conn, "SELECT nama_siswa, id_kelas FROM tbsiswa WHERE id_siswa = {$idSiswa} LIMIT 1");
        $siswa = $qSiswa ? mysqli_fetch_assoc($qSiswa) : null;
        if ($siswa) {
            if ($namaManual === "") {
                $namaManual = (string) $siswa["nama_siswa"];
            }
            if ($idKelas <= 0) {
                $idKelas = (int) $siswa["id_kelas"];
            }
        }
    }

    if ($tahunInput <= 0) {
        $tahunInput = (int) $selectedTahun;
    }

    $statusAllowed = ["hadir", "izin", "sakit", "alpa", "alpha"];
    if (!in_array($status, $statusAllowed, true)) {
        $status = "hadir";
    }

    $idSiswaSql = $idSiswa > 0 ? (string) $idSiswa : "NULL";
    $idKelasSql = $idKelas > 0 ? (string) $idKelas : "NULL";
    $tahunSql = $tahunInput > 0 ? (string) $tahunInput : "NULL";
    $tanggalEsc = mysqli_real_escape_string($conn, $tanggal);
    $statusEsc = mysqli_real_escape_string($conn, $status);
    $keteranganEsc = mysqli_real_escape_string($conn, $keterangan);
    $namaEsc = mysqli_real_escape_string($conn, $namaManual);
    $semesterEsc = mysqli_real_escape_string($conn, $semesterInput);
    $insertSql = "INSERT INTO tbabsensi (tanggal, id_siswa, status, keterangan, nama_siswa_manual, id_kelas, id_tahun_ajaran, semester)
                  VALUES ('{$tanggalEsc}', {$idSiswaSql}, '{$statusEsc}', '{$keteranganEsc}', '{$namaEsc}', {$idKelasSql}, {$tahunSql}, '{$semesterEsc}')";
    if (mysqli_query($conn, $insertSql)) {
        set_flash("success", "Absensi berhasil disimpan.");
    } else {
        set_flash("danger", "Gagal menyimpan absensi.");
    }

    header("Location: absensi.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update"]) && (is_admin() || is_guru())) {
    $idAbsensi = (int) ($_POST["id_absensi"] ?? 0);
    $status = $_POST["status"] ?? "hadir";
    $keterangan = trim($_POST["keterangan"] ?? "");
    $namaManual = trim($_POST["nama_siswa_manual"] ?? "");
    $statusAllowed = ["hadir", "izin", "sakit", "alpa", "alpha"];
    if (!in_array($status, $statusAllowed, true)) {
        $status = "hadir";
    }
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE tbabsensi SET status = ?, keterangan = ?, nama_siswa_manual = ? WHERE id_absensi = ?"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssi", $status, $keterangan, $namaManual, $idAbsensi);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Absensi berhasil diperbarui.");
    }
    header("Location: absensi.php");
    exit;
}

$editData = null;
if (isset($_GET["edit"]) && (is_admin() || is_guru())) {
    $idEdit = (int) $_GET["edit"];
    $res = mysqli_query($conn, "SELECT * FROM tbabsensi WHERE id_absensi = {$idEdit} LIMIT 1");
    $editData = $res ? mysqli_fetch_assoc($res) : null;
}

$where = ["1=1"];
if ($selectedTahun) {
    $where[] = "a.id_tahun_ajaran = " . (int) $selectedTahun;
}
$where[] = "a.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($selectedKelas) {
    $where[] = "a.id_kelas = " . (int) $selectedKelas;
}
if (is_siswa() && $idSiswaLogin) {
    $where[] = "a.id_siswa = " . (int) $idSiswaLogin;
}
$whereSql = implode(" AND ", $where);

if (isset($_GET["export"]) && $_GET["export"] === "1") {
    $exportSql = "SELECT a.tanggal, COALESCE(a.nama_siswa_manual, s.nama_siswa) AS nama, k.nama_kelas, a.status, a.keterangan
                  FROM tbabsensi a
                  LEFT JOIN tbsiswa s ON s.id_siswa = a.id_siswa
                  LEFT JOIN tb_kelas k ON k.id_kelas = a.id_kelas
                  WHERE {$whereSql}
                  ORDER BY a.tanggal DESC, a.id_absensi DESC";
    $exportRes = mysqli_query($conn, $exportSql);

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=absensi_" . date("Ymd_His") . ".csv");
    echo "\xEF\xBB\xBF";

    $out = fopen("php://output", "w");
    fputcsv($out, ["Tanggal", "Nama Siswa", "Kelas", "Status", "Keterangan"]);
    if ($exportRes) {
        while ($row = mysqli_fetch_assoc($exportRes)) {
            fputcsv($out, [$row["tanggal"], $row["nama"], $row["nama_kelas"], $row["status"], $row["keterangan"]]);
        }
    }
    fclose($out);
    exit;
}

$absensiRes = mysqli_query(
    $conn,
    "SELECT a.*, COALESCE(a.nama_siswa_manual, s.nama_siswa) AS nama_siswa, k.nama_kelas
     FROM tbabsensi a
     LEFT JOIN tbsiswa s ON s.id_siswa = a.id_siswa
     LEFT JOIN tb_kelas k ON k.id_kelas = a.id_kelas
     WHERE {$whereSql}
     ORDER BY a.tanggal DESC, a.id_absensi DESC"
);
$absensiList = $absensiRes ? fetch_all_assoc($absensiRes) : [];

$kelasList = get_kelas_list($conn);
$tahunList = get_tahun_ajaran_list($conn);
$siswaSql = "SELECT id_siswa, nama_siswa FROM tbsiswa";
if ($selectedKelas) {
    $siswaSql .= " WHERE id_kelas = " . (int) $selectedKelas;
}
$siswaSql .= " ORDER BY nama_siswa ASC";
$siswaList = fetch_all_assoc(mysqli_query($conn, $siswaSql));

$pageTitle = "Absensi";
include "partials/header.php";
?>

<?php if (is_admin() || is_guru()): ?>
<section class="panel mb-4">
    <h2 class="panel-title"><?= $editData ? "Edit Absensi" : "Input Absensi"; ?></h2>
    <form method="post" class="row g-3">
        <?php if ($editData): ?>
            <input type="hidden" name="id_absensi" value="<?= (int) $editData["id_absensi"]; ?>">
        <?php endif; ?>

        <?php if (!$editData): ?>
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= date("Y-m-d"); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <select name="id_kelas" class="form-select">
                    <option value="">Pilih kelas</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= (int) $kelas["id_kelas"]; ?>" <?= $selectedKelas === (int) $kelas["id_kelas"] ? "selected" : ""; ?>>
                            <?= e($kelas["nama_kelas"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tahun Ajaran</label>
                <select name="id_tahun_ajaran" class="form-select">
                    <?php foreach ($tahunList as $tahun): ?>
                        <option value="<?= (int) $tahun["id_tahun_ajaran"]; ?>" <?= $selectedTahun === (int) $tahun["id_tahun_ajaran"] ? "selected" : ""; ?>>
                            <?= e($tahun["nama_tahun"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select">
                    <option value="ganjil" <?= $selectedSemester === "ganjil" ? "selected" : ""; ?>>Ganjil</option>
                    <option value="genap" <?= $selectedSemester === "genap" ? "selected" : ""; ?>>Genap</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pilih Siswa (opsional)</label>
                <select name="id_siswa" class="form-select">
                    <option value="">Input manual</option>
                    <?php foreach ($siswaList as $siswa): ?>
                        <option value="<?= (int) $siswa["id_siswa"]; ?>"><?= e($siswa["nama_siswa"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-6">
            <label class="form-label">Nama Siswa Manual <?= $editData ? "" : "(opsional jika pilih siswa)"; ?></label>
            <input type="text" name="nama_siswa_manual" class="form-control" value="<?= e($editData["nama_siswa_manual"] ?? ""); ?>" placeholder="Contoh: Budi Santoso">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <?php $statusCurrent = $editData["status"] ?? "hadir"; ?>
            <select name="status" class="form-select" required>
                <option value="hadir" <?= $statusCurrent === "hadir" ? "selected" : ""; ?>>Hadir</option>
                <option value="izin" <?= $statusCurrent === "izin" ? "selected" : ""; ?>>Izin</option>
                <option value="sakit" <?= $statusCurrent === "sakit" ? "selected" : ""; ?>>Sakit</option>
                <option value="alpa" <?= in_array($statusCurrent, ["alpa", "alpha"], true) ? "selected" : ""; ?>>Alpa</option>
            </select>
        </div>
        <div class="col-md-9">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="<?= e($editData["keterangan"] ?? ""); ?>" placeholder="Opsional">
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" name="<?= $editData ? "update" : "simpan"; ?>" class="btn btn-accent">
                <?= $editData ? "Update Absensi" : "Simpan Absensi"; ?>
            </button>
            <?php if ($editData): ?>
                <a href="absensi.php" class="btn btn-outline-secondary">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="panel-title mb-0">Rekap Absensi</h2>
        <a href="absensi.php?export=1" class="btn btn-outline-success btn-sm">Export Excel (CSV)</a>
    </div>
    <div class="table-wrap">
        <table class="table table-smart table-striped align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <?php if (is_admin() || is_guru()): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absensiList)): ?>
                    <tr>
                        <td colspan="<?= (is_admin() || is_guru()) ? 6 : 5; ?>" class="text-center text-muted">Belum ada data absensi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($absensiList as $absen): ?>
                        <?php
                        $status = $absen["status"] === "alpha" ? "alpa" : $absen["status"];
                        $statusClass = "status-" . $status;
                        ?>
                        <tr>
                            <td><?= e($absen["tanggal"]); ?></td>
                            <td><?= e($absen["nama_siswa"] ?? "-"); ?></td>
                            <td><?= e($absen["nama_kelas"] ?? "-"); ?></td>
                            <td><span class="badge <?= e($statusClass); ?>"><?= e(ucfirst($status)); ?></span></td>
                            <td><?= e($absen["keterangan"] ?? "-"); ?></td>
                            <?php if (is_admin() || is_guru()): ?>
                                <td>
                                    <a href="absensi.php?edit=<?= (int) $absen["id_absensi"]; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="absensi.php?hapus=<?= (int) $absen["id_absensi"]; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data absensi ini?')">Hapus</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "partials/footer.php"; ?>

