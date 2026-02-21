<?php
session_start();
include "../../config/koneksi.php";

$id = $_GET['id'];
mysqli_query($conn,"DELETE FROM tbtugas WHERE id_tugas='$id'");

header("Location: read.php");
exit;
?>
