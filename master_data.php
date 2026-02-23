<?php
include "config/koneksi.php";
include "config/helpers.php";

require_roles(["admin", "guru"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah_tahun"])) {
    $nama = trim($_POST["nama_tahun"] ?? "");
    $aktif = isset($_POST["status_aktif"]) ? 1 : 0;
    if ($nama !== "") {
        if ($aktif === 1) {
            mysqli_query($conn, "UPDATE tb_tahun_ajaran SET status_aktif = 0");
        }
        mysqli_query(
            $conn,
            "INSERT INTO tb_tahun_ajaran (nama_tahun, status_aktif)
             VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', {$aktif})"
        );
        set_flash("success", "Tahun ajaran berhasil ditambahkan.");
    }
    header("Location: master_data.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["simpan_semester"])) {
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
    header("Location: master_data.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["aktifkan_semester"])) {
    $kodeSemester = ($_POST["kode_semester"] ?? "ganjil") === "genap" ? "genap" : "ganjil";
    mysqli_query($conn, "UPDATE tb_semester SET status_aktif = 0");
    mysqli_query(
        $conn,
        "UPDATE tb_semester
         SET status_aktif = 1, is_enabled = 1
         WHERE kode_semester = '" . mysqli_real_escape_string($conn, $kodeSemester) . "'"
    );
    $_SESSION["filter_semester"] = $kodeSemester;
    set_flash("success", "Semester aktif berhasil diperbarui.");
    header("Location: master_data.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["aktifkan_tahun"])) {
    $id = (int) ($_POST["id_tahun_ajaran"] ?? 0);
    mysqli_query($conn, "UPDATE tb_tahun_ajaran SET status_aktif = 0");
    mysqli_query($conn, "UPDATE tb_tahun_ajaran SET status_aktif = 1 WHERE id_tahun_ajaran = {$id}");
    $_SESSION["filter_tahun_ajaran"] = $id;
    set_flash("success", "Tahun ajaran aktif berhasil diperbarui.");
    header("Location: master_data.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah_kelas"])) {
    $nama = trim($_POST["nama_kelas"] ?? "");
    $tingkat = trim($_POST["tingkat"] ?? "");
    $jurusan = trim($_POST["jurusan"] ?? "");
    if ($nama !== "") {
        mysqli_query(
            $conn,
            "INSERT INTO tb_kelas (nama_kelas, tingkat, jurusan)
             VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '" . mysqli_real_escape_string($conn, $tingkat) . "', '" . mysqli_real_escape_string($conn, $jurusan) . "')"
        );
        set_flash("success", "Kelas berhasil ditambahkan.");
    }
    header("Location: master_data.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah_user"])) {
    if (!is_admin()) {
        set_flash("danger", "Hanya admin yang dapat membuat user baru.");
        header("Location: master_data.php");
        exit;
    }

    $nama = trim($_POST["nama"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "siswa";
    $mapel = trim($_POST["mapel"] ?? "");
    $idKelas = (int) ($_POST["id_kelas"] ?? 0);

    if ($nama === "" || $username === "" || $password === "") {
        set_flash("danger", "Nama, username, dan password wajib diisi.");
        header("Location: master_data.php");
        exit;
    }

    $cek = mysqli_query(
        $conn,
        "SELECT id_user FROM tbuser WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1"
    );
    if ($cek && mysqli_num_rows($cek) > 0) {
        set_flash("danger", "Username sudah terpakai.");
        header("Location: master_data.php");
        exit;
    }

    $passHash = password_hash($password, PASSWORD_DEFAULT);
    mysqli_query(
        $conn,
        "INSERT INTO tbuser (nama, username, password, role)
         VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '" . mysqli_real_escape_string($conn, $username) . "', '" . mysqli_real_escape_string($conn, $passHash) . "', '" . mysqli_real_escape_string($conn, $role) . "')"
    );
    $idUser = (int) mysqli_insert_id($conn);

    if ($role === "guru") {
        mysqli_query(
            $conn,
            "INSERT INTO tbguru (nama_guru, mapel, id_user)
             VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '" . mysqli_real_escape_string($conn, $mapel) . "', {$idUser})"
        );
    } elseif ($role === "siswa") {
        $kelasNama = "";
        if ($idKelas > 0) {
            $qKelas = mysqli_query($conn, "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = {$idKelas} LIMIT 1");
            if ($qKelas && ($kelas = mysqli_fetch_assoc($qKelas))) {
                $kelasNama = (string) $kelas["nama_kelas"];
            }
        }
        mysqli_query(
            $conn,
            "INSERT INTO tbsiswa (nama_siswa, kelas, id_user, id_kelas)
             VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '" . mysqli_real_escape_string($conn, $kelasNama) . "', {$idUser}, " . ($idKelas > 0 ? $idKelas : "NULL") . ")"
        );
    }

    set_flash("success", "User baru berhasil dibuat.");
    header("Location: master_data.php");
    exit;
}

$tahunList = get_tahun_ajaran_list($conn);
$semesterList = get_semester_list($conn);
$kelasList = get_kelas_list($conn);
$kelasMaster = fetch_all_assoc(mysqli_query($conn, "SELECT id_kelas, nama_kelas, tingkat, jurusan FROM tb_kelas ORDER BY nama_kelas ASC"));
$users = fetch_all_assoc(mysqli_query($conn, "SELECT id_user, nama, username, role FROM tbuser ORDER BY id_user DESC LIMIT 20"));

$pageTitle = "Master Data";
include "partials/header.php";
?>

<div class="row g-4">
    <div class="col-lg-4">
        <section class="panel">
            <h2 class="panel-title">Master Tahun Ajaran</h2>
            <form method="post" class="row g-2 mb-3">
                <div class="col-md-8">
                    <input type="text" name="nama_tahun" class="form-control" placeholder="Contoh: 2026/2027" required>
                </div>
                <div class="col-md-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="aktif" name="status_aktif">
                        <label class="form-check-label" for="aktif">Aktif</label>
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" name="tambah_tahun" class="btn btn-accent">Tambah</button>
                </div>
            </form>
            <div class="table-wrap">
                <table class="table table-smart table-striped">
                    <thead>
                        <tr>
                            <th>Tahun</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tahunList as $tahun): ?>
                            <tr>
                                <td><?= e($tahun["nama_tahun"]); ?></td>
                                <td><?= (int) $tahun["status_aktif"] === 1 ? "<span class='badge text-bg-success'>Aktif</span>" : "<span class='badge text-bg-secondary'>Nonaktif</span>"; ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="id_tahun_ajaran" value="<?= (int) $tahun["id_tahun_ajaran"]; ?>">
                                        <button class="btn btn-sm btn-outline-primary" name="aktifkan_tahun">Aktifkan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="panel">
            <h2 class="panel-title">Master Semester</h2>
            <form method="post" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="kode_semester" class="form-select" required>
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" name="nama_semester" class="form-control" placeholder="Contoh: Semester Ganjil">
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="semester_aktif" name="semester_aktif">
                        <label class="form-check-label" for="semester_aktif">Aktif</label>
                    </div>
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" name="simpan_semester" class="btn btn-accent">Simpan Semester</button>
                </div>
            </form>
            <div class="table-wrap">
                <table class="table table-smart table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($semesterList)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada data semester.</td></tr>
                        <?php else: ?>
                            <?php foreach ($semesterList as $semester): ?>
                                <tr>
                                    <td><?= e(ucfirst((string) $semester["kode_semester"])); ?></td>
                                    <td><?= e($semester["nama_semester"]); ?></td>
                                    <td><?= (int) $semester["status_aktif"] === 1 ? "<span class='badge text-bg-success'>Aktif</span>" : "<span class='badge text-bg-secondary'>Nonaktif</span>"; ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="kode_semester" value="<?= e($semester["kode_semester"]); ?>">
                                            <button class="btn btn-sm btn-outline-primary" name="aktifkan_semester">Aktifkan</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="panel">
            <h2 class="panel-title">Master Kelas</h2>
            <form method="post" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="nama_kelas" class="form-control" placeholder="Nama kelas" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="tingkat" class="form-control" placeholder="X/XI/XII">
                </div>
                <div class="col-md-3">
                    <input type="text" name="jurusan" class="form-control" placeholder="RPL/TKJ">
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" name="tambah_kelas" class="btn btn-accent">Tambah Kelas</button>
                </div>
            </form>
            <div class="table-wrap">
                <table class="table table-smart table-striped">
                    <thead>
                        <tr>
                            <th>Kelas</th>
                            <th>Tingkat</th>
                            <th>Jurusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kelasMaster as $kelas): ?>
                            <tr>
                                <td><?= e($kelas["nama_kelas"]); ?></td>
                                <td><?= e($kelas["tingkat"] ?? "-"); ?></td>
                                <td><?= e($kelas["jurusan"] ?? "-"); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php if (is_admin()): ?>
    <section class="panel mt-4">
        <h2 class="panel-title">Buat User Baru (Admin/Guru/Siswa)</h2>
        <form method="post" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Password</label>
                <input type="text" name="password" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                    <option value="siswa">Siswa</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mapel (khusus guru)</label>
                <input type="text" name="mapel" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas (khusus siswa)</label>
                <select name="id_kelas" class="form-select">
                    <option value="">Pilih kelas</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= (int) $kelas["id_kelas"]; ?>"><?= e($kelas["nama_kelas"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <label class="form-label">&nbsp;</label>
                <button type="submit" name="tambah_user" class="btn btn-accent">Buat User</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table table-smart table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user["nama"]); ?></td>
                            <td><?= e($user["username"]); ?></td>
                            <td><?= e(ucfirst((string) $user["role"])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php include "partials/footer.php"; ?>
