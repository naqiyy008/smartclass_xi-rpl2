<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Siswa</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Kelola Data Siswa</header>
<div class="container">

<a href="create.php" class="btn btn-upload">+ Tambah Siswa</a>

<table>
<tr>
  <th>No</th>
  <th>Nama Siswa</th>
  <th>Kelas</th>
  <th>Alamat</th>
  <th>Aksi</th>
</tr>

<?php
$no=1;
$q = mysqli_query($conn,"SELECT * FROM siswa");
while($s=mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $no++; ?></td>
  <td><?= $s['nama_siswa']; ?></td>
  <td><?= $s['kelas']; ?></td>
  <td><?= $s['alamat']; ?></td>
  <td>
    <a href="edit.php?id=<?= $s['id_siswa']; ?>" class="btn btn-izin">Edit</a>
    <a href="delete.php?id=<?= $s['id_siswa']; ?>" class="btn btn-alpha" onclick="return confirm('Hapus data?')">Hapus</a>
  </td>
</tr>
<?php } ?>

</table>
<br>
<a href="../../dashboard.php">⬅ Kembali</a>

</div>
</body>
</html>
