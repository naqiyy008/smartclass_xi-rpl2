<?php
session_start();
include "../../config/koneksi.php";

mysqli_query($conn,"INSERT INTO tugas
(judul,mapel,guru,deadline,keterangan,jenis)
VALUES(
'$_POST[judul]',
'$_POST[mapel]',
'$_POST[guru]',
'$_POST[deadline]',
'$_POST[keterangan]',
'$_POST[jenis]'
)");

header("Location: read.php");
exit;
?>
