<?php
session_start();
include "config/koneksi.php";
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tugas Offline</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>Tugas Offline</header>

<div class="container">
<?php
$q = mysqli_query($conn,"SELECT * FROM tbtugas WHERE jenis='offline'");
while($t = mysqli_fetch_assoc($q)){
?>
  <div class="task-card">
    <h3><?= $t['judul']; ?></h3>
    <p>Mapel: <?= $t['mapel']; ?></p>
    <p>Deadline: <?= $t['deadline']; ?></p>
    <p><?= $t['keterangan']; ?></p>
  </div>
<?php } ?>
</div>

</body>
</html>
