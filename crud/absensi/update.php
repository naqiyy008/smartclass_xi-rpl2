<?php
session_start();
include "../../config/koneksi.php";

mysqli_query($conn,"UPDATE absensi SET
id_siswa='$_POST[id_siswa]',
tanggal='$_POST[tanggal]',
status='$_POST[status]'
WHERE id_absensi='$_POST[id]'
");

header("Location: read.php");
exit;
?>
