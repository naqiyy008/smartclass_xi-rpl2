<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['role'])) {
  header("Location: login.php");
  exit;
}

if (
  isset($_POST['tambah']) &&
  ($_SESSION['role'] == "admin" || $_SESSION['role'] == "guru")
) {

  $judul = $_POST['judul'];
  $mapel = $_POST['mapel'];
  $deadline = $_POST['deadline'];
  $keterangan = $_POST['keterangan'];
  $jenis = "offline";
  $id_guru = $_SESSION['id_user'];

  mysqli_query($conn, "INSERT INTO tbtugas
    (judul,mapel,id_guru,deadline,keterangan,jenis,link_pengumpulan)
    VALUES
    ('$judul','$mapel','$id_guru','$deadline',
     '$keterangan','$jenis','-')");
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Tugas Offline</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>

  <div id="loading-screen">
    <div class="neon-loader">
      <span></span>
    </div>
  </div>
  <header>
    Tugas Offline
    <a href="dashboard.php" class="btn-back">⬅ Kembali</a>
  </header>

  <div class="container">

    <?php if ($_SESSION['role'] == "admin" || $_SESSION['role'] == "guru") { ?>
      <form method="post">
        <h3>Tambah Tugas Offline</h3>
        <input type="text" name="judul" placeholder="Judul" required>
        <input type="text" name="mapel" placeholder="Mata Pelajaran">
        <input type="date" name="deadline" required>
        <textarea name="keterangan" placeholder="Keterangan"></textarea>
        <button type="submit" name="tambah" class="btn-upload">Add</button>
      </form>
    <?php } ?>

    <?php
    $q = mysqli_query($conn, "SELECT * FROM tbtugas 
WHERE jenis='offline'
ORDER BY deadline ASC");

    while ($d = mysqli_fetch_assoc($q)) {
    ?>
      <div class="task-card">
        <h3><?= $d['judul']; ?></h3>
        <p><b>Mapel:</b> <?= $d['mapel']; ?></p>
        <p><b>Deadline:</b> <?= $d['deadline']; ?></p>
        <p><?= $d['keterangan']; ?></p>
      </div>
    <?php } ?>

  </div>
  <script src="assets/script.js"></script>

</body>

</html>