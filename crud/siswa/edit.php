<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM siswa WHERE id_siswa='$id'"));
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Siswa</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Edit Siswa</header>
<div class="container">

<form action="update.php" method="post">
  <input type="hidden" name="id" value="<?= $data['id_siswa']; ?>">

  <input type="text" name="nama_siswa" value="<?= $data['nama_siswa']; ?>" required>
  <input type="text" name="kelas" value="<?= $data['kelas']; ?>" required>
  <textarea name="alamat"><?= $data['alamat']; ?></textarea>

  <button class="btn btn-upload">Update</button>
</form>

</div>
</body>
</html>
