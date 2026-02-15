<?php
session_start();
include "../../config/koneksi.php";

mysqli_query($conn,"INSERT INTO absensi(id_siswa,tanggal,status)
VALUES(
'$_POST[id_siswa]',
'$_POST[tanggal]',
'$_POST[status]'
)");

header("Location: read.php");
exit;
?>
