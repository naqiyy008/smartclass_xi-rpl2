<?php
include "config/helpers.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id_user"])) {
    header("Location: auth/login.php");
    exit;
}

set_flash("info", "Form upload terintegrasi di halaman Tugas Online.");
header("Location: tugas_online.php");
exit;
