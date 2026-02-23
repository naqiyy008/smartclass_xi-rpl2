<?php
include "config/koneksi.php";
include "config/helpers.php";

require_roles(["admin", "guru"]);

$selectedTahun = get_selected_tahun_ajaran_id($conn);
$selectedSemester = get_selected_semester($conn);
$selectedKelas = require_class_context();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_penilaian"])) {
    $idKumpul = (int) ($_POST["id_kumpul"] ?? 0);
    $nilai = trim($_POST["nilai"] ?? "");
    $statusPengumpulan = $_POST["status_pengumpulan"] ?? "terkumpul";
    $catatan = trim($_POST["catatan"] ?? "");

    $allowed = ["terkumpul", "revisi", "dinilai"];
    if (!in_array($statusPengumpulan, $allowed, true)) {
        $statusPengumpulan = "terkumpul";
    }

    $nilaiSql = $nilai === "" ? "NULL" : (float) $nilai;
    $statusEsc = mysqli_real_escape_string($conn, $statusPengumpulan);
    $catatanEsc = mysqli_real_escape_string($conn, $catatan);

    mysqli_query(
        $conn,
        "UPDATE tb_pengumpulan_tugas
         SET nilai = {$nilaiSql}, status_pengumpulan = '{$statusEsc}', keterangan = '{$catatanEsc}'
         WHERE id_kumpul = {$idKumpul}"
    );
    set_flash("success", "Penilaian berhasil diperbarui.");
    header("Location: pengumpulan.php");
    exit;
}

$where = ["t.mode_pengumpulan = 'online'"];
if ($selectedTahun) {
    $where[] = "t.id_tahun_ajaran = " . (int) $selectedTahun;
}
$where[] = "t.semester = '" . mysqli_real_escape_string($conn, $selectedSemester) . "'";
if ($selectedKelas) {
    $where[] = "t.id_kelas = " . (int) $selectedKelas;
}
$whereSql = implode(" AND ", $where);

$res = mysqli_query(
    $conn,
    "SELECT p.*, s.nama_siswa, t.judul, t.deadline_at, t.mapel, k.nama_kelas
     FROM tb_pengumpulan_tugas p
     JOIN tbsiswa s ON s.id_siswa = p.id_siswa
     JOIN tbtugas t ON t.id_tugas = p.id_tugas
     LEFT JOIN tb_kelas k ON k.id_kelas = t.id_kelas
     WHERE {$whereSql}
     ORDER BY p.tanggal_kumpul DESC, p.id_kumpul DESC"
);
$rows = $res ? fetch_all_assoc($res) : [];

$pageTitle = "Pengumpulan Tugas";
include "partials/header.php";
?>

<section class="panel">
    <h2 class="panel-title">Data Pengumpulan Tugas Online</h2>
    <div class="table-wrap">
        <table class="table table-smart table-striped align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Tugas</th>
                    <th>Mapel</th>
                    <th>File/Link</th>
                    <th>Status</th>
                    <th>Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada pengumpulan pada filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $deadline = strtotime((string) $row["deadline_at"]);
                        $tanggalKumpul = strtotime((string) $row["tanggal_kumpul"]);
                        $isLate = $deadline && $tanggalKumpul > $deadline;
                        ?>
                        <tr>
                            <td><?= e($row["tanggal_kumpul"]); ?></td>
                            <td><?= e($row["nama_siswa"]); ?></td>
                            <td><?= e($row["nama_kelas"] ?? "-"); ?></td>
                            <td><?= e($row["judul"]); ?></td>
                            <td><?= e($row["mapel"]); ?></td>
                            <td>
                                <?php if ($row["format_pengumpulan"] === "file" && !empty($row["file_tugas"])): ?>
                                    <a href="uploads/<?= e($row["file_tugas"]); ?>" target="_blank">Unduh File</a>
                                <?php elseif ($row["format_pengumpulan"] === "link" && !empty($row["link_tugas"])): ?>
                                    <a href="<?= e($row["link_tugas"]); ?>" target="_blank">Buka Link</a>
                                <?php else: ?>
                                    <span><?= e($row["keterangan"] ?? "-"); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $isLate ? "text-bg-danger" : "text-bg-success"; ?>">
                                    <?= $isLate ? "Telat" : "Tepat Waktu"; ?>
                                </span>
                                <br>
                                <span class="badge badge-soft mt-1"><?= e($row["status_pengumpulan"]); ?></span>
                            </td>
                            <td><?= $row["nilai"] !== null ? e((string) $row["nilai"]) : "-"; ?></td>
                            <td>
                                <form method="post" class="d-flex flex-column gap-1">
                                    <input type="hidden" name="id_kumpul" value="<?= (int) $row["id_kumpul"]; ?>">
                                    <input type="number" name="nilai" class="form-control form-control-sm" min="0" max="100" step="0.01" value="<?= e((string) ($row["nilai"] ?? "")); ?>" placeholder="Nilai">
                                    <select name="status_pengumpulan" class="form-select form-select-sm">
                                        <option value="terkumpul" <?= $row["status_pengumpulan"] === "terkumpul" ? "selected" : ""; ?>>Terkumpul</option>
                                        <option value="revisi" <?= $row["status_pengumpulan"] === "revisi" ? "selected" : ""; ?>>Revisi</option>
                                        <option value="dinilai" <?= $row["status_pengumpulan"] === "dinilai" ? "selected" : ""; ?>>Dinilai</option>
                                    </select>
                                    <input type="text" name="catatan" class="form-control form-control-sm" value="<?= e((string) ($row["keterangan"] ?? "")); ?>" placeholder="Catatan">
                                    <button type="submit" name="update_penilaian" class="btn btn-sm btn-accent">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "partials/footer.php"; ?>

