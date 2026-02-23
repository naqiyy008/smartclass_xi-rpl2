<?php
include "../config/koneksi.php";
include "../config/helpers.php";

if (isset($_SESSION['id_user'])) {
    header("Location: ../dashboard.php");
    exit;
}

$flash = get_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SmartClass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div id="page-loader">
    <div class="loader-mark"></div>
    <p>Memuat halaman...</p>
</div>
<section class="login-shell">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="login-title mb-2">SmartClass</h1>
            <p class="text-muted mb-4">SMKN 1 Probolinggo</p>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
            <?php endif; ?>
            <form action="proses_login.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autocomplete="username">
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-accent w-100">Masuk</button>
            </form>
            <p class="small text-muted mt-3 mb-0">
                Akun berbeda untuk Admin, Guru, dan Siswa.
            </p>
        </div>
    </div>
</section>
<script src="../assets/app.js"></script>
</body>
</html>
