<?php
session_start();
include "config/koneksi.php";

$id_tugas = $_GET['id'];
$id_user  = $_SESSION['id_user'];

$siswa = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT * FROM siswa WHERE id_user='$id_user'")
);
$id_siswa = $siswa['id_siswa'];
?>
<!DOCTYPE html>
<html>

<head>
  <title>Upload Tugas</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>

  <div id="loading-screen">
    <div class="neon-loader">
      <span></span>
    </div>
  </div>
  <header>Upload Tugas</header>

  <div class="container">
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="file" required>
      <textarea name="keterangan" placeholder="Keterangan (opsional)"></textarea>
      <button class="btn-upload">Upload</button>
    </form>

    <?php
    if (isset($_FILES['file'])) {
      $file = $_FILES['file']['name'];
      $tmp  = $_FILES['file']['tmp_name'];

      move_uploaded_file($tmp, "uploads/" . $file);

      mysqli_query($conn, "INSERT INTO pengumpulan_tugas
  (id_tugas,id_siswa,file_tugas,tanggal_kumpul,keterangan)
  VALUES
  ('$id_tugas','$id_siswa','$file',NOW(),'$_POST[keterangan]')");

      echo "<p>✅ Tugas berhasil dikumpulkan</p>";
    }
    ?>
  </div>
  <script src="assets/script.js"></script>

</body>

</html>