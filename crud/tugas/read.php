<?php
session_start();
include "../../config/koneksi.php";
if ($_SESSION['role'] != "admin") {
  exit("Akses ditolak");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Tugas</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>
  <span>Kelola Data Tugas</span>
  <a href="../../dashboard.php" class="btn-back">⬅ Kembali</a>
</header>

<div class="container">

<a href="create.php" class="btn btn-upload">+ Tambah Tugas</a>

<table>
<tr>
  <th>No</th>
  <th>Judul</th>
  <th>Mapel</th>
  <th>Guru</th>
  <th>Deadline</th>
  <th>Jenis</th>
  <th>Link</th>
  <th>Aksi</th>
</tr>

<?php
$no=1;
$q = mysqli_query($conn,"
SELECT tbtugas.*, tbguru.nama_guru
FROM tbtugas
LEFT JOIN tbguru ON tbtugas.id_guru = tbguru.id_guru
");

while($t=mysqli_fetch_assoc($q)){
?>
<tr>
<td><?= $no++; ?></td>
<td><?= $t['judul']; ?></td>
<td><?= $t['mapel']; ?></td>
<td><?= $t['nama_guru']; ?></td>
<td><?= $t['deadline']; ?></td>
<td><?= ucfirst($t['jenis']); ?></td>
<td>
<?php 
if($t['jenis']=="online" && !empty($t['link_pengumpulan'])){
    echo "<a href='".$t['link_pengumpulan']."' target='_blank'>Buka</a>";
}else{
    echo "-";
}
?>
</td>
<td>
<a href="edit.php?id=<?= $t['id_tugas']; ?>" class="btn btn-izin">Edit</a>
<a href="delete.php?id=<?= $t['id_tugas']; ?>" class="btn btn-alpha" onclick="return confirm('Hapus data?')">Hapus</a>
</td>
</tr>
<?php } ?>
</table>

</div>
</body>
</html>