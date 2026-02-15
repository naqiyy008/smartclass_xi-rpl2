<?php
session_start();
include "config/koneksi.php";

if(isset($_POST['simpan']) && isset($_SESSION['role']) && $_SESSION['role']=="admin"){

    if(!empty($_POST['id_siswa']) && !empty($_POST['status'])){

        $id_siswa = (int) $_POST['id_siswa'];
        $status = $_POST['status'];

        mysqli_query($conn,"INSERT INTO tbabsensi(id_siswa,tanggal,status)
        VALUES('$id_siswa', CURDATE(), '$status')");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Absensi</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  

<header>Absensi Siswa</header>

<div class="container">

<?php if(isset($_SESSION['role']) && $_SESSION['role']=="admin"){ ?>

<form method="post">
  <h3>Input Absensi</h3>

  <select name="id_siswa" required>
    <option value="">-- Pilih Siswa --</option>
    <?php
    $s = mysqli_query($conn,"SELECT * FROM tbsiswa");
    while($d=mysqli_fetch_assoc($s)){
      echo "<option value='".$d['id_siswa']."'>".$d['nama_siswa']."</option>";
    }
    ?>
  </select>

  <select name="status" required>
    <option value="">-- Pilih Status --</option>
    <option value="hadir">Hadir</option>
    <option value="izin">Izin</option>
    <option value="sakit">Sakit</option>
    <option value="alpha">Alpha</option>
  </select>

  <button type="submit" name="simpan" class="btn-upload">Simpan</button>
</form>

<?php } ?>

<table>
<tr>
  <th>Nama</th>
  <th>Tanggal</th>
  <th>Status</th>
</tr>



<?php
$q = mysqli_query($conn,"SELECT tbabsensi.*, tbsiswa.nama_siswa
FROM tbabsensi 
JOIN tbsiswa ON tbabsensi.id_siswa = tbsiswa.id_siswa
ORDER BY tbabsensi.tanggal DESC");

while($a=mysqli_fetch_assoc($q)){

  $class = "";
  if($a['status']=="hadir") $class="btn-hadir";
  if($a['status']=="izin") $class="btn-izin";
  if($a['status']=="sakit") $class="btn-sakit";
  if($a['status']=="alpha") $class="btn-alpha";
?>
<tr>
  <td><?= $a['nama_siswa']; ?></td>
  <td><?= $a['tanggal']; ?></td>
  <td><span class="btn <?= $class ?>"><?= ucfirst($a['status']); ?></span></td>
</tr>
<?php } ?>
</table>

</div>
<a href="dashboard.php">⬅ Kembali</a>

</body>
</html>
