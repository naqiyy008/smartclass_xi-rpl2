<?php
session_start();
include "config/koneksi.php";
if (!isset($_SESSION['role'])) {
  header("Location: auth/login.php");
  exit;
}

if ($_SESSION['role'] != "guru" && $_SESSION['role'] != "admin") {
  exit("Akses ditolak!");
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>Pengumpulan Tugas</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>

  <div id="loading-screen">
    <div class="neon-loader">
      <span></span>
    </div>
  </div>
  <header>
    Pengumpulan Tugas
    <a href="dashboard.php" class="btn-back">⬅ Kembali</a>
  </header>

  <div class="container">

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
      <tr>
        <th>Nama Siswa</th>
        <th>Judul Tugas</th>
        <th>File</th>
        <th>Tanggal</th>
        <th>Keterangan</th>
      </tr>

      <?php
      $q = mysqli_query($conn, "
SELECT tb_pengumpulan_tugas.*, 
       tbsiswa.nama_siswa, 
       tbtugas.judul
FROM tb_pengumpulan_tugas
JOIN tbsiswa 
     ON tb_pengumpulan_tugas.id_siswa = tbsiswa.id_siswa
JOIN tbtugas 
     ON tb_pengumpulan_tugas.id_tugas = tbtugas.id_tugas
ORDER BY tb_pengumpulan_tugas.tanggal_kumpul DESC
");

      if (mysqli_num_rows($q) > 0) {
        while ($p = mysqli_fetch_assoc($q)) {
      ?>

          <tr>
            <td><?= $p['nama_siswa']; ?></td>
            <td><?= $p['judul']; ?></td>
            <td>
              <a href="uploads/<?= $p['file_tugas']; ?>" target="_blank">
                Download
              </a>
            </td>
            <td><?= $p['tanggal_kumpul']; ?></td>
            <td><?= $p['keterangan']; ?></td>
          </tr>

      <?php
        }
      } else {
        echo "<tr><td colspan='5' align='center'>Belum ada pengumpulan</td></tr>";
      }
      ?>

    </table>

  </div>

</body>

</html>