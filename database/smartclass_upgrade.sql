-- SmartClass upgrade SQL (jalankan 1x pada DB baru)
-- Untuk DB yang sudah berjalan, gunakan script idempotent: database/migrate.php

CREATE TABLE IF NOT EXISTS tb_tahun_ajaran (
    id_tahun_ajaran INT NOT NULL AUTO_INCREMENT,
    nama_tahun VARCHAR(20) NOT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_tahun_ajaran),
    UNIQUE KEY uk_nama_tahun (nama_tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO tb_tahun_ajaran (nama_tahun, status_aktif)
SELECT '2025/2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tb_tahun_ajaran);

CREATE TABLE IF NOT EXISTS tb_semester (
    id_semester INT NOT NULL AUTO_INCREMENT,
    kode_semester ENUM('ganjil','genap') NOT NULL,
    nama_semester VARCHAR(50) NOT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_semester),
    UNIQUE KEY uk_kode_semester (kode_semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO tb_semester (kode_semester, nama_semester, status_aktif, is_enabled)
VALUES
('ganjil', 'Semester Ganjil', 1, 1),
('genap', 'Semester Genap', 0, 1)
ON DUPLICATE KEY UPDATE
  nama_semester = VALUES(nama_semester),
  is_enabled = VALUES(is_enabled);

CREATE TABLE IF NOT EXISTS tb_kelas (
    id_kelas INT NOT NULL AUTO_INCREMENT,
    nama_kelas VARCHAR(30) NOT NULL,
    tingkat VARCHAR(10) DEFAULT NULL,
    jurusan VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_kelas),
    UNIQUE KEY uk_nama_kelas (nama_kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE tbuser
    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE tbguru
    ADD COLUMN id_user INT NULL,
    ADD COLUMN no_hp VARCHAR(20) NULL;

ALTER TABLE tbsiswa
    ADD COLUMN nisn VARCHAR(20) NULL,
    ADD COLUMN angkatan YEAR NULL,
    ADD COLUMN id_kelas INT NULL;

ALTER TABLE tbtugas
    ADD COLUMN id_kelas INT NULL,
    ADD COLUMN id_tahun_ajaran INT NULL,
    ADD COLUMN semester ENUM('ganjil', 'genap') NULL,
    ADD COLUMN mode_pengumpulan ENUM('online', 'offline') NULL,
    ADD COLUMN deadline_at DATETIME NULL,
    ADD COLUMN lampiran_file VARCHAR(255) NULL,
    ADD COLUMN created_by INT NULL,
    ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE tb_pengumpulan_tugas
    ADD COLUMN id_user INT NULL,
    ADD COLUMN link_tugas VARCHAR(255) NULL,
    ADD COLUMN format_pengumpulan ENUM('file', 'link', 'teks') NOT NULL DEFAULT 'file',
    ADD COLUMN telat TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN status_pengumpulan ENUM('terkumpul', 'revisi', 'dinilai') NOT NULL DEFAULT 'terkumpul',
    ADD COLUMN nilai DECIMAL(5,2) NULL,
    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE tbabsensi
    MODIFY COLUMN status ENUM('hadir', 'izin', 'sakit', 'alpa', 'alpha') NOT NULL,
    MODIFY COLUMN id_siswa INT NULL,
    ADD COLUMN nama_siswa_manual VARCHAR(100) NULL,
    ADD COLUMN id_kelas INT NULL,
    ADD COLUMN id_tahun_ajaran INT NULL,
    ADD COLUMN semester ENUM('ganjil', 'genap') NULL;

CREATE TABLE IF NOT EXISTS tb_jadwal_pelajaran (
    id_jadwal INT NOT NULL AUTO_INCREMENT,
    id_kelas INT NOT NULL,
    id_guru INT NOT NULL,
    mapel VARCHAR(100) NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruang VARCHAR(50) DEFAULT NULL,
    id_tahun_ajaran INT NOT NULL,
    semester ENUM('ganjil', 'genap') NOT NULL,
    keterangan VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_jadwal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS tb_pengumuman (
    id_pengumuman INT NOT NULL AUTO_INCREMENT,
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    id_user INT NOT NULL,
    id_kelas INT DEFAULT NULL,
    id_tahun_ajaran INT DEFAULT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pengumuman)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
