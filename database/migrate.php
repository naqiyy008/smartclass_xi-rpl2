<?php
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect('localhost', 'root', '', 'db_manajemen_kelas_xirpl2');
if (!$conn) {
    exit("Koneksi gagal: " . mysqli_connect_error() . PHP_EOL);
}
mysqli_set_charset($conn, 'utf8mb4');

function execute_sql(mysqli $conn, string $sql, string $label): bool
{
    $ok = mysqli_query($conn, $sql);
    if ($ok) {
        echo "[OK] {$label}" . PHP_EOL;
        return true;
    }
    echo "[ERROR] {$label} :: " . mysqli_error($conn) . PHP_EOL;
    return false;
}

function table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    return $res && mysqli_num_rows($res) > 0;
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $tbl = mysqli_real_escape_string($conn, $table);
    $col = mysqli_real_escape_string($conn, $column);
    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tbl}'
              AND COLUMN_NAME = '{$col}'
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

function add_column_if_missing(mysqli $conn, string $table, string $column, string $definition): void
{
    if (column_exists($conn, $table, $column)) {
        echo "[SKIP] {$table}.{$column} sudah ada" . PHP_EOL;
        return;
    }
    execute_sql($conn, "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}", "Tambah kolom {$table}.{$column}");
}

execute_sql(
    $conn,
    "CREATE TABLE IF NOT EXISTS tb_tahun_ajaran (
        id_tahun_ajaran INT NOT NULL AUTO_INCREMENT,
        nama_tahun VARCHAR(20) NOT NULL,
        status_aktif TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_tahun_ajaran),
        UNIQUE KEY uk_nama_tahun (nama_tahun)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",
    'Buat tabel tb_tahun_ajaran'
);

execute_sql(
    $conn,
    "CREATE TABLE IF NOT EXISTS tb_semester (
        id_semester INT NOT NULL AUTO_INCREMENT,
        kode_semester ENUM('ganjil','genap') NOT NULL,
        nama_semester VARCHAR(50) NOT NULL,
        status_aktif TINYINT(1) NOT NULL DEFAULT 0,
        is_enabled TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_semester),
        UNIQUE KEY uk_kode_semester (kode_semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",
    'Buat tabel tb_semester'
);

execute_sql(
    $conn,
    "INSERT INTO tb_tahun_ajaran (nama_tahun, status_aktif)
     SELECT '2025/2026', 1
     FROM DUAL
     WHERE NOT EXISTS (SELECT 1 FROM tb_tahun_ajaran)",
    'Seed tahun ajaran default'
);

execute_sql(
    $conn,
    "INSERT INTO tb_semester (kode_semester, nama_semester, status_aktif, is_enabled)
     VALUES
     ('ganjil', 'Semester Ganjil', 1, 1),
     ('genap', 'Semester Genap', 0, 1)
     ON DUPLICATE KEY UPDATE
       nama_semester = VALUES(nama_semester),
       is_enabled = VALUES(is_enabled)",
    'Seed semester default'
);

execute_sql(
    $conn,
    "CREATE TABLE IF NOT EXISTS tb_kelas (
        id_kelas INT NOT NULL AUTO_INCREMENT,
        nama_kelas VARCHAR(30) NOT NULL,
        tingkat VARCHAR(10) DEFAULT NULL,
        jurusan VARCHAR(50) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_kelas),
        UNIQUE KEY uk_nama_kelas (nama_kelas)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",
    'Buat tabel tb_kelas'
);

execute_sql(
    $conn,
    "INSERT IGNORE INTO tb_kelas (nama_kelas)
     SELECT DISTINCT kelas
     FROM tbsiswa
     WHERE kelas IS NOT NULL AND TRIM(kelas) <> ''",
    'Sinkron kelas dari data siswa lama'
);

add_column_if_missing($conn, 'tbuser', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
add_column_if_missing($conn, 'tbguru', 'id_user', 'INT NULL');
add_column_if_missing($conn, 'tbguru', 'no_hp', 'VARCHAR(20) NULL');
add_column_if_missing($conn, 'tbsiswa', 'nisn', 'VARCHAR(20) NULL');
add_column_if_missing($conn, 'tbsiswa', 'angkatan', 'YEAR NULL');
add_column_if_missing($conn, 'tbsiswa', 'id_kelas', 'INT NULL');

execute_sql(
    $conn,
    "UPDATE tbsiswa s
     JOIN tb_kelas k ON k.nama_kelas = s.kelas
     SET s.id_kelas = k.id_kelas
     WHERE s.id_kelas IS NULL",
    'Relasi siswa ke master kelas'
);

add_column_if_missing($conn, 'tbtugas', 'id_kelas', 'INT NULL');
add_column_if_missing($conn, 'tbtugas', 'id_tahun_ajaran', 'INT NULL');
add_column_if_missing($conn, 'tbtugas', 'semester', "ENUM('ganjil','genap') NULL");
add_column_if_missing($conn, 'tbtugas', 'mode_pengumpulan', "ENUM('online','offline') NULL");
add_column_if_missing($conn, 'tbtugas', 'deadline_at', 'DATETIME NULL');
add_column_if_missing($conn, 'tbtugas', 'lampiran_file', 'VARCHAR(255) NULL');
add_column_if_missing($conn, 'tbtugas', 'created_by', 'INT NULL');
add_column_if_missing($conn, 'tbtugas', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

execute_sql(
    $conn,
    "UPDATE tbtugas
     SET mode_pengumpulan = CASE WHEN LOWER(jenis) = 'offline' THEN 'offline' ELSE 'online' END
     WHERE mode_pengumpulan IS NULL",
    'Default mode pengumpulan tugas'
);

execute_sql($conn, "UPDATE tbtugas SET semester = 'ganjil' WHERE semester IS NULL", 'Default semester tugas');
execute_sql(
    $conn,
    "UPDATE tbtugas
     SET id_tahun_ajaran = (
         SELECT id_tahun_ajaran
         FROM tb_tahun_ajaran
         WHERE status_aktif = 1
         ORDER BY id_tahun_ajaran DESC
         LIMIT 1
     )
     WHERE id_tahun_ajaran IS NULL",
    'Default tahun ajaran tugas'
);
execute_sql(
    $conn,
    "UPDATE tbtugas
     SET deadline_at = CONCAT(deadline, ' 23:59:59')
     WHERE deadline IS NOT NULL AND deadline_at IS NULL",
    'Konversi deadline lama ke deadline_at'
);

add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'id_user', 'INT NULL');
add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'link_tugas', 'VARCHAR(255) NULL');
add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'format_pengumpulan', "ENUM('file','link','teks') NOT NULL DEFAULT 'file'");
add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'telat', 'TINYINT(1) NOT NULL DEFAULT 0');
add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'status_pengumpulan', "ENUM('terkumpul','revisi','dinilai') NOT NULL DEFAULT 'terkumpul'");
add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'nilai', 'DECIMAL(5,2) NULL');
add_column_if_missing($conn, 'tb_pengumpulan_tugas', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

execute_sql(
    $conn,
    "UPDATE tb_pengumpulan_tugas p
     JOIN tbsiswa s ON s.id_siswa = p.id_siswa
     SET p.id_user = s.id_user
     WHERE p.id_user IS NULL",
    'Map id_user pada pengumpulan'
);

execute_sql(
    $conn,
    "ALTER TABLE tbabsensi MODIFY COLUMN status ENUM('hadir','izin','sakit','alpa','alpha') NOT NULL",
    'Perluas enum status absensi'
);
execute_sql(
    $conn,
    "ALTER TABLE tbabsensi MODIFY COLUMN id_siswa INT NULL",
    'Izinkan absensi nama manual tanpa id_siswa'
);
add_column_if_missing($conn, 'tbabsensi', 'nama_siswa_manual', 'VARCHAR(100) NULL');
add_column_if_missing($conn, 'tbabsensi', 'id_kelas', 'INT NULL');
add_column_if_missing($conn, 'tbabsensi', 'id_tahun_ajaran', 'INT NULL');
add_column_if_missing($conn, 'tbabsensi', 'semester', "ENUM('ganjil','genap') NULL");

execute_sql(
    $conn,
    "UPDATE tbabsensi a
     LEFT JOIN tbsiswa s ON s.id_siswa = a.id_siswa
     LEFT JOIN tb_kelas k ON k.nama_kelas = s.kelas
     SET a.nama_siswa_manual = COALESCE(a.nama_siswa_manual, s.nama_siswa),
         a.id_kelas = COALESCE(a.id_kelas, s.id_kelas, k.id_kelas)
     WHERE a.nama_siswa_manual IS NULL OR a.id_kelas IS NULL",
    'Sinkron nama manual absensi'
);
execute_sql($conn, "UPDATE tbabsensi SET semester = 'ganjil' WHERE semester IS NULL", 'Default semester absensi');
execute_sql(
    $conn,
    "UPDATE tbabsensi
     SET id_tahun_ajaran = (
         SELECT id_tahun_ajaran
         FROM tb_tahun_ajaran
         WHERE status_aktif = 1
         ORDER BY id_tahun_ajaran DESC
         LIMIT 1
     )
     WHERE id_tahun_ajaran IS NULL",
    'Default tahun ajaran absensi'
);

execute_sql(
    $conn,
    "CREATE TABLE IF NOT EXISTS tb_jadwal_pelajaran (
        id_jadwal INT NOT NULL AUTO_INCREMENT,
        id_kelas INT NOT NULL,
        id_guru INT NOT NULL,
        mapel VARCHAR(100) NOT NULL,
        hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
        jam_mulai TIME NOT NULL,
        jam_selesai TIME NOT NULL,
        ruang VARCHAR(50) DEFAULT NULL,
        id_tahun_ajaran INT NOT NULL,
        semester ENUM('ganjil','genap') NOT NULL,
        keterangan VARCHAR(200) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_jadwal)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",
    'Buat tabel jadwal pelajaran'
);

execute_sql(
    $conn,
    "CREATE TABLE IF NOT EXISTS tb_pengumuman (
        id_pengumuman INT NOT NULL AUTO_INCREMENT,
        judul VARCHAR(150) NOT NULL,
        isi TEXT NOT NULL,
        id_user INT NOT NULL,
        id_kelas INT DEFAULT NULL,
        id_tahun_ajaran INT DEFAULT NULL,
        dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_pengumuman)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",
    'Buat tabel pengumuman'
);

echo PHP_EOL . "Migrasi selesai." . PHP_EOL;
