<?php
include "../config/koneksi.php";
include "../config/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    set_flash("danger", "Username dan password wajib diisi.");
    header("Location: login.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id_user, nama, username, password, role FROM tbuser WHERE username = ? LIMIT 1");
if (!$stmt) {
    set_flash("danger", "Terjadi kesalahan server.");
    header("Location: login.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$user) {
    set_flash("danger", "Username tidak ditemukan.");
    header("Location: login.php");
    exit;
}

$storedPassword = (string) $user["password"];
$validPassword = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

if (!$validPassword) {
    set_flash("danger", "Password salah.");
    header("Location: login.php");
    exit;
}

if (!password_get_info($storedPassword)["algo"]) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $update = mysqli_prepare($conn, "UPDATE tbuser SET password = ? WHERE id_user = ?");
    if ($update) {
        mysqli_stmt_bind_param($update, "si", $newHash, $user["id_user"]);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
    }
}

$_SESSION["id_user"] = (int) $user["id_user"];
$_SESSION["nama"] = $user["nama"];
$_SESSION["role"] = $user["role"];
if (!isset($_SESSION["filter_tahun_ajaran"])) {
    $activeYear = get_active_tahun_ajaran_id($conn);
    if ($activeYear !== null) {
        $_SESSION["filter_tahun_ajaran"] = $activeYear;
    }
}
$_SESSION["filter_semester"] = get_selected_semester($conn);

header("Location: ../dashboard.php");
exit;
