<?php
session_start();
include "config/koneksi.php";
if($_SESSION['role']!="guru"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pengumpulan Tugas</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>Pengumpulan Tugas</header>

<div class="container">
<table>
<tr>
  <th>Nama</th><th>Judul</th><th>File</th><th>Waktu</th>
</tr>
<?php
$q = mysqli_query($conn,"SELECT pengumpulan_tugas.*, siswa.nama_siswa, tugas.judul
FROM pengumpulan_tugas
JOIN siswa ON pengumpulan_tugas.id_siswa=siswa.id_siswa
JOIN tugas ON pengumpulan_tugas.id_tugas=tugas.id_tugas");

while($p=mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $p['nama_siswa']; ?></td>
  <td><?= $p['judul']; ?></td>
  <td>
    <a href="uploads/<?= $p['file_tugas']; ?>">Download</a>
  </td>
  <td><?= $p['tanggal_kumpul']; ?></td>
</tr>
<?php } ?>
</table>
</div>

</body>
</html>