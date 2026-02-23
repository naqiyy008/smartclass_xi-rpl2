<?php
include 'config/koneksi.php';
include 'config/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputSemester = $_POST['filter_semester'] ?? '';
    $semesterList = get_semester_list($conn);
    $allowedSemester = array_map(
        static fn(array $item): string => $item['kode_semester'] === 'genap' ? 'genap' : 'ganjil',
        $semesterList
    );

    if (!empty($allowedSemester) && in_array($inputSemester, $allowedSemester, true)) {
        $_SESSION['filter_semester'] = $inputSemester;
    } else {
        $_SESSION['filter_semester'] = get_active_semester_code($conn);
    }

    $_SESSION['filter_kelas'] = trim($_POST['filter_kelas'] ?? '');

    $idTahun = isset($_POST['filter_tahun_ajaran']) ? (int) $_POST['filter_tahun_ajaran'] : 0;
    if ($idTahun > 0) {
        $_SESSION['filter_tahun_ajaran'] = $idTahun;
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header('Location: ' . $redirect);
exit;
