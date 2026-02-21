<?php
include '../../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $judul = $_POST['judul'] ?? '';
    $mapel = $_POST['mapel'] ?? '';
    $id_guru = isset($_POST['id_guru']) ? (int)$_POST['id_guru'] : 0;
    $deadline = $_POST['deadline'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $link_pengumpulan = $_POST['link_pengumpulan'] ?? '';

    if ($id_guru <= 0) {
        die("ID Guru tidak valid!");
    }

    $query = "INSERT INTO tbtugas 
    (judul, mapel, id_guru, deadline, keterangan, jenis, link_pengumpulan)
    VALUES 
    ('$judul', '$mapel', $id_guru, '$deadline', '$keterangan', '$jenis', '$link_pengumpulan')";

    if (!mysqli_query($oon, $query)) {
        die("Query Error: " . mysqli_error($conn));
    }

    header("Location: read.php");
    exit;
}
?>
