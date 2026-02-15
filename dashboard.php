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

  <div id="loading-screen">
    <div class="neon-loader">
      <span></span>
    </div>
  </div>
  <header>SmartClass XI RPL 2</header>

  <div class="container">
    <p>Halo, <b><?= $_SESSION['nama']; ?></b> (<?= $_SESSION['role']; ?>)</p>

    <div class="cards">
      <a href="tugas_offline.php" class="card blue">📝<br>Tugas Offline</a>
      <a href="tugas_online.php" class="card purple">🌐<br>Tugas Online</a>
      <a href="absensi.php" class="card green">📅<br>Absensi</a>

      <?php if ($_SESSION['role'] == "guru") { ?>
        <a href="pengumpulan.php" class="card orange">📤<br>Pengumpulan</a>
      <?php } ?>

      <?php if ($_SESSION['role'] == "admin") { ?>
        <a href="crud/tugas/read.php" class="card orange">⚙ Kelola Tugas</a>
      <?php } ?>
    </div>

    <br>
    <a href="auth/logout.php">Logout</a>
  </div>

</body>

</html>