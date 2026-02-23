<?php
$conn = mysqli_connect("localhost", "root", "", "db_manajemen_kelas_xirpl2");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set("Asia/Jakarta");
?>
