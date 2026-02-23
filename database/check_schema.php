<?php
$conn = mysqli_connect("localhost", "root", "", "db_manajemen_kelas_xirpl2");
if (!$conn) {
    exit("Koneksi gagal: " . mysqli_connect_error() . PHP_EOL);
}

$tables = ["tb_tahun_ajaran", "tb_semester", "tb_kelas", "tb_jadwal_pelajaran", "tb_pengumuman"];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    echo $table . ": " . (($result && mysqli_num_rows($result) > 0) ? "OK" : "MISSING") . PHP_EOL;
}

$desc = mysqli_query($conn, "DESCRIBE tbabsensi");
while ($desc && ($col = mysqli_fetch_assoc($desc))) {
    if (in_array($col["Field"], ["id_siswa", "nama_siswa_manual", "id_tahun_ajaran", "semester"], true)) {
        echo "tbabsensi." . $col["Field"] . ": " . $col["Type"] . " NULL=" . $col["Null"] . PHP_EOL;
    }
}
