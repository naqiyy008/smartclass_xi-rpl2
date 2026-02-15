<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tambah Absensi</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Tambah Absensi</header>
<div class="container">

<form action="store.php" method="post">
  <label>Nama Siswa</label>
  <select name="id_siswa" required>
    <?php
    $s = mysqli_query($conn,"SELECT * FROM siswa");
    while($d=mysqli_fetch_assoc($s)){
      echo "<option value='$d[id_siswa]'>$d[nama_siswa]</option>";
    }
    ?>
  </select>

  <label>Tanggal</label>
  <input type="date" name="tanggal" required>

  <label>Status</label>
  <select name="status">
    <option value="hadir">Hadir</option>
    <option value="izin">Izin</option>
    <option value="sakit">Sakit</option>
    <option value="alpha">Alpha</option>
  </select>

  <button class="btn btn-upload">Simpan</button>
</form>

</div>
</body>
</html>
