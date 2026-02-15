<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Absensi</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Kelola Absensi</header>
<div class="container">

<a href="create.php" class="btn btn-upload">+ Tambah Absensi</a>

<table>
<tr>
  <th>No</th>
  <th>Nama Siswa</th>
  <th>Tanggal</th>
  <th>Status</th>
  <th>Aksi</th>
</tr>

<?php
$no=1;
$q = mysqli_query($conn,"SELECT absensi.*, siswa.nama_siswa 
FROM absensi 
JOIN siswa ON absensi.id_siswa=siswa.id_siswa");

while($a=mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $no++; ?></td>
  <td><?= $a['nama_siswa']; ?></td>
  <td><?= $a['tanggal']; ?></td>
  <td><?= $a['status']; ?></td>
  <td>
    <a href="edit.php?id=<?= $a['id_absensi']; ?>" class="btn btn-izin">Edit</a>
    <a href="delete.php?id=<?= $a['id_absensi']; ?>" class="btn btn-alpha" onclick="return confirm('Hapus data?')">Hapus</a>
  </td>
</tr>
<?php } ?>

</table>
<br>
<a href="../../dashboard.php">⬅ Kembali</a>

</div>
</body>
</html>
