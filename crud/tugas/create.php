<?php
session_start();
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tambah Tugas</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Tambah Tugas</header>
<div class="container">

<form action="store.php" method="post">
  <input type="text" name="judul" placeholder="Judul Tugas" required>
  <input type="text" name="mapel" placeholder="Mapel" required>
  <input type="text" name="guru" placeholder="Nama Guru" required>
  <input type="date" name="deadline" required>

  <textarea name="keterangan" placeholder="Keterangan"></textarea>

  <select name="jenis">
    <option value="offline">Offline</option>
    <option value="online">Online</option>
  </select>

  <button class="btn btn-upload">Simpan</button>
</form>

</div>
</body>
</html>
