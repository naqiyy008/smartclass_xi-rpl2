<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function require_login(): void
{
    if (!isset($_SESSION['id_user'], $_SESSION['role'])) {
        header('Location: auth/login.php');
        exit;
    }
}

function require_roles(array $roles): void
{
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function is_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_guru(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guru';
}

function is_siswa(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'siswa';
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function fetch_all_assoc(mysqli_result $result): array
{
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function get_tahun_ajaran_list(mysqli $conn): array
{
    $sql = "SELECT id_tahun_ajaran, nama_tahun, status_aktif
            FROM tb_tahun_ajaran
            ORDER BY status_aktif DESC, nama_tahun DESC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }
    return fetch_all_assoc($result);
}

function get_semester_list(mysqli $conn): array
{
    $sql = "SELECT id_semester, kode_semester, nama_semester, status_aktif, is_enabled
            FROM tb_semester
            WHERE is_enabled = 1
            ORDER BY status_aktif DESC, id_semester ASC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }
    return fetch_all_assoc($result);
}

function get_active_semester_code(mysqli $conn): string
{
    $result = mysqli_query(
        $conn,
        "SELECT kode_semester
         FROM tb_semester
         WHERE is_enabled = 1
         ORDER BY status_aktif DESC, id_semester ASC
         LIMIT 1"
    );
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        return $row['kode_semester'] === 'genap' ? 'genap' : 'ganjil';
    }
    return 'ganjil';
}

function get_kelas_list(mysqli $conn): array
{
    $sql = "SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas ASC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }
    return fetch_all_assoc($result);
}

function get_active_tahun_ajaran_id(mysqli $conn): ?int
{
    $result = mysqli_query($conn, "SELECT id_tahun_ajaran FROM tb_tahun_ajaran WHERE status_aktif = 1 LIMIT 1");
    if (!$result) {
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    return $row ? (int) $row['id_tahun_ajaran'] : null;
}

function get_selected_tahun_ajaran_id(mysqli $conn): ?int
{
    if (isset($_SESSION['filter_tahun_ajaran'])) {
        return (int) $_SESSION['filter_tahun_ajaran'];
    }
    $active = get_active_tahun_ajaran_id($conn);
    if ($active !== null) {
        $_SESSION['filter_tahun_ajaran'] = $active;
    }
    return $active;
}

function get_selected_semester(?mysqli $conn = null): string
{
    $sessionSemester = $_SESSION['filter_semester'] ?? '';
    if ($sessionSemester !== 'ganjil' && $sessionSemester !== 'genap') {
        $sessionSemester = '';
    }

    if ($conn === null) {
        if ($sessionSemester === '') {
            $_SESSION['filter_semester'] = 'ganjil';
            return 'ganjil';
        }
        return $sessionSemester;
    }

    $semesterList = get_semester_list($conn);
    if (empty($semesterList)) {
        if ($sessionSemester === '') {
            $_SESSION['filter_semester'] = 'ganjil';
            return 'ganjil';
        }
        return $sessionSemester;
    }

    $allowed = array_map(
        static fn(array $item): string => $item['kode_semester'] === 'genap' ? 'genap' : 'ganjil',
        $semesterList
    );

    if ($sessionSemester !== '' && in_array($sessionSemester, $allowed, true)) {
        return $sessionSemester;
    }

    $active = get_active_semester_code($conn);
    $_SESSION['filter_semester'] = $active;
    return $active;
}

function get_selected_kelas_id(): ?int
{
    if (!isset($_SESSION['filter_kelas']) || $_SESSION['filter_kelas'] === '') {
        return null;
    }
    return (int) $_SESSION['filter_kelas'];
}

function require_class_context(): int
{
    $selectedKelas = get_selected_kelas_id();
    if ($selectedKelas === null || $selectedKelas <= 0) {
        set_flash('warning', 'Pilih kelas aktif di dashboard terlebih dahulu.');
        header('Location: dashboard.php');
        exit;
    }
    return $selectedKelas;
}

function parse_deadline(string $date): string
{
    return $date . ' 23:59:59';
}

function find_id_siswa_by_user(mysqli $conn, int $idUser): ?int
{
    $stmt = mysqli_prepare($conn, "SELECT id_siswa FROM tbsiswa WHERE id_user = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $idUser);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ? (int) $row['id_siswa'] : null;
}
