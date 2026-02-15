<?php
session_start();
include "../../config/koneksi.php";

mysqli_query($conn,"INSERT INTO siswa(nama_siswa,kelas,alamat)
VALUES(
'$_POST[nama_siswa]',
'$_POST[kelas]',
'$_POST[alamat]'
)");

header("Location: read.php");
exit;
?>
