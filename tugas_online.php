<?php
session_start();
include "config/koneksi.php";
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tugas Online</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>Tugas Online</header>

<div class="container">
<?php
$q = mysqli_query($conn,"SELECT * FROM tbtugas WHERE jenis='online'");
while($t = mysqli_fetch_assoc($q)){
?>
  <div class="task-card">
    <h3><?= $t['judul']; ?></h3>
    <p>Mapel: <?= $t['mapel']; ?></p>
    <p>Deadline: <?= $t['deadline']; ?></p>

    <?php if($_SESSION['role']=="siswa"){ ?>
      <a href="upload_tugas.php?id=<?= $t['id_tugas']; ?>" class="btn-upload">
        Kumpulkan Tugas
      </a>
    <?php } ?>
  </div>
<?php } ?>
</div>

</body>
</html>
