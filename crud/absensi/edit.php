<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM absensi WHERE id_absensi='$id'"));
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Absensi</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Edit Absensi</header>
<div class="container">

<form action="update.php" method="post">
  <input type="hidden" name="id" value="<?= $data['id_absensi']; ?>">

  <label>Nama Siswa</label>
  <select name="id_siswa">
    <?php
    $s = mysqli_query($conn,"SELECT * FROM siswa");
    while($d=mysqli_fetch_assoc($s)){
      $selected = ($d['id_siswa']==$data['id_siswa']) ? "selected" : "";
      echo "<option value='$d[id_siswa]' $selected>$d[nama_siswa]</option>";
    }
    ?>
  </select>

  <label>Tanggal</label>
  <input type="date" name="tanggal" value="<?= $data['tanggal']; ?>">

  <label>Status</label>
  <select name="status">
    <option value="hadir" <?= $data['status']=="hadir"?"selected":"" ?>>Hadir</option>
    <option value="izin" <?= $data['status']=="izin"?"selected":"" ?>>Izin</option>
    <option value="sakit" <?= $data['status']=="sakit"?"selected":"" ?>>Sakit</option>
    <option value="alpha" <?= $data['status']=="alpha"?"selected":"" ?>>Alpha</option>
  </select>

  <button class="btn btn-upload">Update</button>
</form>

</div>
</body>
</html>
