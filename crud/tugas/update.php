<?php
session_start();
include "../../config/koneksi.php";

mysqli_query($conn,"UPDATE tugas SET
judul='$_POST[judul]',
mapel='$_POST[mapel]',
guru='$_POST[guru]',
deadline='$_POST[deadline]',
keterangan='$_POST[keterangan]',
jenis='$_POST[jenis]'
WHERE id_tugas='$_POST[id]'
");

header("Location: read.php");
exit;
?>
