<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedKelas = require_class_context();
$idUser = (int) $_SESSION["id_user"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["simpan_pengumuman"]) && (is_admin() || is_guru())) {
    $judul = trim($_POST["judul"] ?? "");
    $isi = trim($_POST["isi"] ?? "");
    $idKelas = (int) ($_POST["id_kelas"] ?? 0);
    $idTahun = (int) ($_POST["id_tahun_ajaran"] ?? 0);

    if ($judul === "" || $isi === "") {
        set_flash("danger", "Judul dan isi pengumuman wajib diisi.");
        header("Location: pengumuman.php");
        exit;
    }

    $idKelasSql = $idKelas > 0 ? (string) $idKelas : "NULL";
    $idTahunSql = $idTahun > 0 ? (string) $idTahun : "NULL";
    $judulEsc = mysqli_real_escape_string($conn, $judul);
    $isiEsc = mysqli_real_escape_string($conn, $isi);

    mysqli_query(
        $conn,
        "INSERT INTO tb_pengumuman (judul, isi, id_user, id_kelas, id_tahun_ajaran)
         VALUES ('{$judulEsc}', '{$isiEsc}', {$idUser}, {$idKelasSql}, {$idTahunSql})"
    );
    set_flash("success", "Pengumuman berhasil dipublikasikan.");
    header("Location: pengumuman.php");
    exit;
}

if (isset($_GET["hapus"]) && (is_admin() || is_guru())) {
    $id = (int) $_GET["hapus"];
    mysqli_query($conn, "DELETE FROM tb_pengumuman WHERE id_pengumuman = {$id}");
    set_flash("success", "Pengumuman dihapus.");
    header("Location: pengumuman.php");
    exit;
}

$where = ["1=1"];
if ($selectedTahun) {
    $where[] = "(p.id_tahun_ajaran IS NULL OR p.id_tahun_ajaran = " . (int) $selectedTahun . ")";
}
if ($selectedKelas) {
    $where[] = "(p.id_kelas IS NULL OR p.id_kelas = " . (int) $selectedKelas . ")";
}
if (is_siswa()) {
    $idSiswa = find_id_siswa_by_user($conn, $idUser);
    if ($idSiswa) {
        $qSiswa = mysqli_query($conn, "SELECT id_kelas FROM tbsiswa WHERE id_siswa = {$idSiswa} LIMIT 1");
        if ($qSiswa && ($siswa = mysqli_fetch_assoc($qSiswa))) {
            $kelasSiswa = (int) $siswa["id_kelas"];
            $where[] = "(p.id_kelas IS NULL OR p.id_kelas = {$kelasSiswa})";
        }
    }
}
$whereSql = implode(" AND ", $where);

$res = mysqli_query(
    $conn,
    "SELECT p.*, u.nama, k.nama_kelas, ta.nama_tahun
     FROM tb_pengumuman p
     JOIN tbuser u ON u.id_user = p.id_user
     LEFT JOIN tb_kelas k ON k.id_kelas = p.id_kelas
     LEFT JOIN tb_tahun_ajaran ta ON ta.id_tahun_ajaran = p.id_tahun_ajaran
     WHERE {$whereSql}
     ORDER BY p.dibuat_pada DESC"
);
$rows = $res ? fetch_all_assoc($res) : [];

$kelasList = get_kelas_list($conn);
$tahunList = get_tahun_ajaran_list($conn);

$pageTitle = "Pengumuman";
include "partials/header.php";
?>

<?php if (is_admin() || is_guru()): ?>
<section class="panel mb-4">
    <h2 class="panel-title">Buat Pengumuman</h2>
    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Kelas (opsional)</label>
            <select name="id_kelas" class="form-select">
                <option value="">Semua kelas</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <option value="<?= (int) $kelas["id_kelas"]; ?>"><?= e($kelas["nama_kelas"]); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tahun Ajaran (opsional)</label>
            <select name="id_tahun_ajaran" class="form-select">
                <option value="">Semua tahun</option>
                <?php foreach ($tahunList as $tahun): ?>
                    <option value="<?= (int) $tahun["id_tahun_ajaran"]; ?>" <?= $selectedTahun === (int) $tahun["id_tahun_ajaran"] ? "selected" : ""; ?>>
                        <?= e($tahun["nama_tahun"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Isi Pengumuman</label>
            <textarea name="isi" class="form-control" rows="4" required></textarea>
        </div>
        <div class="col-12">
            <button type="submit" name="simpan_pengumuman" class="btn btn-accent">Publikasikan</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel-title">Daftar Pengumuman</h2>
    <?php if (empty($rows)): ?>
        <p class="text-muted mb-0">Belum ada pengumuman pada filter ini.</p>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($rows as $row): ?>
                <article class="panel">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1"><?= e($row["judul"]); ?></h5>
                            <small class="text-muted">
                                Oleh <?= e($row["nama"]); ?> • <?= e($row["nama_kelas"] ?? "Semua kelas"); ?> • <?= e($row["nama_tahun"] ?? "Semua tahun"); ?>
                            </small>
                        </div>
                        <?php if (is_admin() || is_guru()): ?>
                            <a href="pengumuman.php?hapus=<?= (int) $row["id_pengumuman"]; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a>
                        <?php endif; ?>
                    </div>
                    <p class="mt-2 mb-0"><?= nl2br(e($row["isi"])); ?></p>
                    <small class="text-muted d-block mt-2"><?= e(date("d M Y H:i", strtotime((string) $row["dibuat_pada"]))); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include "partials/footer.php"; ?>
