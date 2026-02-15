<?php
session_start();
include "../../config/koneksi.php";

mysqli_query($conn,"UPDATE siswa SET
nama_siswa='$_POST[nama_siswa]',
kelas='$_POST[kelas]',
alamat='$_POST[alamat]'
WHERE id_siswa='$_POST[id]'
");

header("Location: read.php");
exit;
?>
