<?php
include "config/koneksi.php";
include "config/helpers.php";

require_roles(["admin", "guru"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$action = $_POST["action"] ?? "";

if ($action === "tambah_tahun") {
    $nama = trim($_POST["nama_tahun"] ?? "");
    $aktif = isset($_POST["status_aktif"]) ? 1 : 0;

    if ($nama === "") {
        set_flash("danger", "Tahun ajaran wajib diisi.");
        header("Location: dashboard.php");
        exit;
    }

    if ($aktif === 1) {
        mysqli_query($conn, "UPDATE tb_tahun_ajaran SET status_aktif = 0");
    }

    mysqli_query(
        $conn,
        "INSERT INTO tb_tahun_ajaran (nama_tahun, status_aktif)
         VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', {$aktif})
         ON DUPLICATE KEY UPDATE status_aktif = VALUES(status_aktif)"
    );
    set_flash("success", "Tahun ajaran berhasil disimpan.");
    header("Location: dashboard.php");
    exit;
}

if ($action === "simpan_semester") {
    $kodeSemester = ($_POST["kode_semester"] ?? "ganjil") === "genap" ? "genap" : "ganjil";
    $namaSemester = trim($_POST["nama_semester"] ?? "");
    $aktif = isset($_POST["semester_aktif"]) ? 1 : 0;

    if ($namaSemester === "") {
        $namaSemester = $kodeSemester === "genap" ? "Semester Genap" : "Semester Ganjil";
    }

    if ($aktif === 1) {
        mysqli_query($conn, "UPDATE tb_semester SET status_aktif = 0");
    }

    mysqli_query(
        $conn,
        "INSERT INTO tb_semester (kode_semester, nama_semester, status_aktif, is_enabled)
         VALUES ('" . mysqli_real_escape_string($conn, $kodeSemester) . "', '" . mysqli_real_escape_string($conn, $namaSemester) . "', {$aktif}, 1)
         ON DUPLICATE KEY UPDATE
           nama_semester = VALUES(nama_semester),
           status_aktif = VALUES(status_aktif),
           is_enabled = 1"
    );
    $_SESSION["filter_semester"] = $kodeSemester;
    set_flash("success", "Semester berhasil disimpan.");
    header("Location: dashboard.php");
    exit;
}

if ($action === "tambah_kelas") {
    $nama = trim($_POST["nama_kelas"] ?? "");
    $tingkat = trim($_POST["tingkat"] ?? "");
    $jurusan = trim($_POST["jurusan"] ?? "");

    if ($nama === "") {
        set_flash("danger", "Nama kelas wajib diisi.");
        header("Location: dashboard.php");
        exit;
    }

    mysqli_query(
        $conn,
        "INSERT INTO tb_kelas (nama_kelas, tingkat, jurusan)
         VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '" . mysqli_real_escape_string($conn, $tingkat) . "', '" . mysqli_real_escape_string($conn, $jurusan) . "')
         ON DUPLICATE KEY UPDATE
           tingkat = VALUES(tingkat),
           jurusan = VALUES(jurusan),
           is_active = 1"
    );
    set_flash("success", "Kelas berhasil disimpan.");
    header("Location: dashboard.php");
    exit;
}

set_flash("danger", "Aksi tidak dikenali.");
header("Location: dashboard.php");
exit;
