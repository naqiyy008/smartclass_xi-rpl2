<?php
session_start();
include "../config/koneksi.php";

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header("Location: login.php");
    exit;
}

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

$query = mysqli_query($conn, "SELECT * FROM tbuser WHERE username='$username'");

if (!$query) {
    die("Query error: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($query);

if ($user) {
    if ($password == $user['password']) {
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        header("Location: ../dashboard.php");
        exit;
    } else {
        echo "Password salah!";
    }
} else {
    echo "Username tidak ditemukan!";
}
?>
