<?php
session_start();
include "../../config/koneksi.php";

$id = $_GET['id'];
mysqli_query($conn,"DELETE FROM tbabsensi WHERE id_absensi='$id'");

header("Location: read.php");
exit;
?>
