<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tambah Siswa</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Tambah Siswa</header>
<div class="container">

<form action="store.php" method="post">
  <input type="text" name="nama_siswa" placeholder="Nama Siswa" required>
  <input type="text" name="kelas" placeholder="Kelas" required>
  <textarea name="alamat" placeholder="Alamat"></textarea>

  <button class="btn btn-upload">Simpan</button>
</form>

</div>
</body>
</html>
