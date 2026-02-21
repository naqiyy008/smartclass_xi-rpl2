<?php
session_start();
include "../../config/koneksi.php";
if ($_SESSION['role'] != "admin") {
  exit("Akses ditolak");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tambah Tugas</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>
  <span>Tambah Tugas</span>
  <a href="read.php" class="btn-back">⬅ Kembali</a>
</header>

<div class="container">
<form action="store.php" method="post">

<input type="text" name="judul" placeholder="Judul Tugas" required>

<input type="text" name="mapel" placeholder="Mapel" required>

<select name="id_guru" required>
  <option value="">-- Pilih Guru --</option>
  <?php
  $guru = mysqli_query($conn, "SELECT * FROM tbguru");
  while($g = mysqli_fetch_assoc($guru)){
      echo "<option value='".$g['id_guru']."'>".$g['nama_guru']." - ".$g['mapel']."</option>";
  }
  ?>
</select>

<input type="date" name="deadline" required>

<textarea name="keterangan" placeholder="Keterangan"></textarea>

<select name="jenis" required>
  <option value="offline">Offline</option>
  <option value="online">Online</option>
</select>

<input type="text" name="link_pengumpulan" placeholder="Link Pengumpulan (isi jika online)">

<button type="submit" class="btn btn-upload">Simpan</button>

</form>
</div>

</body>
</html>