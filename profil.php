<?php
include "config/koneksi.php";
include "config/helpers.php";

require_login();

$idUser = (int) $_SESSION["id_user"];
$userRes = mysqli_query($conn, "SELECT id_user, nama, username, role, password FROM tbuser WHERE id_user = {$idUser} LIMIT 1");
$user = $userRes ? mysqli_fetch_assoc($userRes) : null;

if (!$user) {
    session_destroy();
    header("Location: auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_akun"])) {
    $newUsername = trim($_POST["username"] ?? "");
    $currentPassword = $_POST["password_lama"] ?? "";
    $newPassword = $_POST["password_baru"] ?? "";
    $confirmPassword = $_POST["konfirmasi_password"] ?? "";

    if ($newUsername === "") {
        set_flash("danger", "Username tidak boleh kosong.");
        header("Location: profil.php");
        exit;
    }

    $cekUsername = mysqli_query(
        $conn,
        "SELECT id_user FROM tbuser WHERE username = '" . mysqli_real_escape_string($conn, $newUsername) . "' AND id_user <> {$idUser} LIMIT 1"
    );
    if ($cekUsername && mysqli_num_rows($cekUsername) > 0) {
        set_flash("danger", "Username sudah dipakai user lain.");
        header("Location: profil.php");
        exit;
    }

    $updates = [];
    $updates[] = "username = '" . mysqli_real_escape_string($conn, $newUsername) . "'";

    if ($newPassword !== "" || $confirmPassword !== "" || $currentPassword !== "") {
        $storedPassword = (string) $user["password"];
        $validCurrent = password_verify($currentPassword, $storedPassword) || hash_equals($storedPassword, $currentPassword);
        if (!$validCurrent) {
            set_flash("danger", "Password lama tidak sesuai.");
            header("Location: profil.php");
            exit;
        }
        if (strlen($newPassword) < 6) {
            set_flash("danger", "Password baru minimal 6 karakter.");
            header("Location: profil.php");
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            set_flash("danger", "Konfirmasi password tidak sama.");
            header("Location: profil.php");
            exit;
        }
        $updates[] = "password = '" . mysqli_real_escape_string($conn, password_hash($newPassword, PASSWORD_DEFAULT)) . "'";
    }

    mysqli_query($conn, "UPDATE tbuser SET " . implode(", ", $updates) . " WHERE id_user = {$idUser}");
    $_SESSION["nama"] = $user["nama"];
    set_flash("success", "Akun berhasil diperbarui.");
    header("Location: profil.php");
    exit;
}

$pageTitle = "Profil Akun";
include "partials/header.php";
?>

<section class="panel">
    <h2 class="panel-title">Pengaturan Akun</h2>
    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" value="<?= e($user["nama"]); ?>" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Role</label>
            <input type="text" class="form-control" value="<?= e(ucfirst((string) $user["role"])); ?>" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Username Baru</label>
            <input type="text" name="username" class="form-control" value="<?= e($user["username"]); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Password Lama</label>
            <input type="password" name="password_lama" class="form-control" placeholder="Isi jika ingin ganti password">
        </div>
        <div class="col-md-4">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password_baru" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="konfirmasi_password" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" name="update_akun" class="btn btn-accent">Simpan Perubahan</button>
        </div>
    </form>
</section>

<?php include "partials/footer.php"; ?>
