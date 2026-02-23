<?php
session_start();
include "../../config/koneksi.php";

$id = $_GET['id'];
mysqli_query($conn,"DELETE FROM tbsiswa WHERE id_siswa='$id'");

header("Location: read.php");
exit;
?>