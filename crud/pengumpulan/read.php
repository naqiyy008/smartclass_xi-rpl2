<?php
session_start();
include "../../config/koneksi.php";

if($_SESSION['role']!="guru" && $_SESSION['role']!="admin"){
  exit("Akses ditolak");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Pengumpulan Tugas</title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<header>Data Pengumpulan Tugas</header>
<div class="container">

<table>
<tr>
  <th>No</th>
  <th>Nama Siswa</th>
  <th>Judul Tugas</th>
  <th>File</th>
  <th>Tanggal Kumpul</th>
  <th>Keterangan</th>
  <th>Aksi</th>
</tr>

<?php
$no=1;
$q = mysqli_query($conn,"SELECT pengumpulan_tugas.*, siswa.nama_siswa, tugas.judul 
FROM pengumpulan_tugas
JOIN siswa ON pengumpulan_tugas.id_siswa=siswa.id_siswa
JOIN tugas ON pengumpulan_tugas.id_tugas=tugas.id_tugas
ORDER BY tanggal_kumpul DESC");

while($p=mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $no++; ?></td>
  <td><?= $p['nama_siswa']; ?></td>
  <td><?= $p['judul']; ?></td>
  <td>
    <a href="../../uploads/<?= $p['file_tugas']; ?>" target="_blank">
      Download
    </a>
  </td>
  <td><?= $p['tanggal_kumpul']; ?></td>
  <td><?= $p['keterangan']; ?></td>
  <td>
    <a href="delete.php?id=<?= $p['id_pengumpulan']; ?>" 
       class="btn btn-alpha"
       onclick="return confirm('Hapus data pengumpulan ini?')">
       Hapus
    </a>
  </td>
</tr>
<?php } ?>

</table>

<br>
<a href="../../dashboard.php">⬅ Kembali</a>

</div>
</body>
</html>
