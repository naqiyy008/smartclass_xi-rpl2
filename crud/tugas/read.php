<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Tugas</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Kelola Data Tugas</header>
<div class="container">

<a href="create.php" class="btn btn-upload">+ Tambah Tugas</a>

<table>
<tr>
  <th>No</th>
  <th>Judul</th>
  <th>Mapel</th>
  <th>Guru</th>
  <th>Deadline</th>
  <th>Jenis</th>
  <th>Aksi</th>
</tr>

<?php
$no=1;
$q = mysqli_query($conn,"SELECT * FROM tbtugas");
while($t=mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $no++; ?></td>
  <td><?= $t['judul']; ?></td>
  <td><?= $t['mapel']; ?></td>
  <td><?= $t['guru']; ?></td>
  <td><?= $t['deadline']; ?></td>
  <td><?= $t['jenis']; ?></td>
  <td>
    <a href="edit.php?id=<?= $t['id_tugas']; ?>" class="btn btn-izin">Edit</a>
    <a href="delete.php?id=<?= $t['id_tugas']; ?>" class="btn btn-alpha" onclick="return confirm('Hapus data?')">Hapus</a>
  </td>
</tr>
<?php } ?>

</table>
<br>
<a href="../../dashboard.php">⬅ Kembali</a>
</div>

</body>
</html>
