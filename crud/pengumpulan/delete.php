<?php
session_start();
include "../../config/koneksi.php";

if($_SESSION['role']!="guru" && $_SESSION['role']!="admin"){
  exit("Akses ditolak");
}

$id = $_GET['id'];

/* ambil nama file dulu */
$data = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT file_tugas FROM pengumpulan_tugas WHERE id_pengumpulan='$id'"));

$file = $data['file_tugas'];

/* hapus dari database */
mysqli_query($conn,"DELETE FROM pengumpulan_tugas WHERE id_pengumpulan='$id'");

/* hapus file fisik */
if(file_exists("../../uploads/".$file)){
  unlink("../../uploads/".$file);
}

header("Location: read.php");
exit;
?>
