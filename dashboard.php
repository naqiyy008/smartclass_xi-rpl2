<?php
session_start();

if (!isset($_SESSION['role'])) {
  header("Location: auth/login.php");
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard | SmartClass</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
  <span>SmartClass XI RPL 2</span>
  <a href="auth/logout.php" class="btn-back">Logout</a>
</header>

<div class="container">

  <div class="info-box">
    Halo, <b><?= $_SESSION['nama']; ?></b>
    (<?= ucfirst($_SESSION['role']); ?>)
  </div>

  <div class="cards">

    <a href="tugas_offline.php" class="card blue">
      <div class="icon">📝</div>
      <h3>Tugas Offline</h3>
      <p>Lihat & tambah tugas offline</p>
    </a>

    <a href="tugas_online.php" class="card purple">
      <div class="icon">🌐</div>
      <h3>Tugas Online</h3>
      <p>Lihat & tambah tugas online</p>
    </a>

    <a href="absensi.php" class="card green">
      <div class="icon">📅</div>
      <h3>Absensi</h3>
      <p>Kelola absensi siswa</p>
    </a>

    <?php if ($_SESSION['role'] == "guru") { ?>
      <a href="pengumpulan.php" class="card blue">
        <div class="icon">📤</div>
        <h3>Pengumpulan</h3>
        <p>Lihat tugas yang dikumpulkan</p>
      </a>
    <?php } ?>

    <?php if ($_SESSION['role'] == "admin") { ?>
      <a href="crud/tugas/read.php" class="card purple">
        <div class="icon">⚙</div>
        <h3>Kelola Tugas</h3>
        <p>Edit & hapus data tugas</p>
      </a>
    <?php } ?>

  </div>

</div>

</body>
</html>