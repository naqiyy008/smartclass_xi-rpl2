<?php
session_start();
include "config/koneksi.php";
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

<?php if($_SESSION['role']=="admin"){ ?>
<form method="post">
  <select name="id_siswa">
    <?php
    $s = mysqli_query($conn,"SELECT * FROM siswa");
    while($d=mysqli_fetch_assoc($s)){
      echo "<option value='$d[id_siswa]'>$d[nama_siswa]</option>";
    }
    ?>
  </select>

  <select name="status">
    <option value="hadir">Hadir</option>
    <option value="izin">Izin</option>
    <option value="sakit">Sakit</option>
    <option value="alpha">Alpha</option>
  </select>

  <button class="btn-primary">Simpan</button>
</form>

<?php
if(isset($_POST['status'])){
  mysqli_query($conn,"INSERT INTO absensi(id_siswa,tanggal,status)
  VALUES('$_POST[id_siswa]',CURDATE(),'$_POST[status]')");
}
}
?>

<table>
<tr>
  <th>Nama</th><th>Tanggal</th><th>Status</th>
</tr>
<?php
$q = mysqli_query($conn,"SELECT absensi.*, siswa.nama_siswa
FROM absensi JOIN tbsiswa ON absensi.id_siswa=siswa.id_siswa");
while($a=mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $a['nama_siswa']; ?></td>
  <td><?= $a['tanggal']; ?></td>
  <td><?= $a['status']; ?></td>
</tr>
<?php } ?>
</table>

</div>
</body>
</html>
