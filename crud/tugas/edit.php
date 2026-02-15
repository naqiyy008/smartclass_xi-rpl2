<?php
session_start();
include "../../config/koneksi.php";
if($_SESSION['role']!="admin"){ exit("Akses ditolak"); }

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM tugas WHERE id_tugas='$id'"));
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Tugas</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Edit Tugas</header>
<div class="container">

<form action="update.php" method="post">
  <input type="hidden" name="id" value="<?= $data['id_tugas']; ?>">

  <input type="text" name="judul" value="<?= $data['judul']; ?>" required>
  <input type="text" name="mapel" value="<?= $data['mapel']; ?>" required>
  <input type="text" name="guru" value="<?= $data['guru']; ?>" required>
  <input type="date" name="deadline" value="<?= $data['deadline']; ?>" required>

  <textarea name="keterangan"><?= $data['keterangan']; ?></textarea>

  <select name="jenis">
    <option value="offline" <?= $data['jenis']=="offline"?"selected":"" ?>>Offline</option>
    <option value="online" <?= $data['jenis']=="online"?"selected":"" ?>>Online</option>
  </select>

  <button class="btn btn-upload">Update</button>
</form>

</div>
</body>
</html>
