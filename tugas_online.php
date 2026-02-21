<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['role'])){
    header("Location: login.php");
    exit;
}

/* TAMBAH TUGAS ONLINE */
if(isset($_POST['tambah']) && 
   ($_SESSION['role']=="admin" || $_SESSION['role']=="guru")){

    $judul = $_POST['judul'];
    $mapel = $_POST['mapel'];
    $id_guru = $_POST['id_guru'];
    $deadline = $_POST['deadline'];
    $keterangan = $_POST['keterangan'];
    $link_pengumpulan = $_POST['link_pengumpulan'];
    $jenis = "online";

    mysqli_query($conn,"INSERT INTO tbtugas
    (judul,mapel,id_guru,deadline,keterangan,jenis,link_pengumpulan)
    VALUES
    ('$judul','$mapel','$id_guru','$deadline',
     '$keterangan','$jenis','$link_pengumpulan')");
}

/* UPLOAD SISWA */
if(isset($_POST['upload']) && $_SESSION['role']=="siswa"){

    $id_tugas = $_POST['id_tugas'];
    $link = $_POST['link'];
    $id_user = $_SESSION['id_user'];

    mysqli_query($conn,"INSERT INTO tbpengumpulan
    (id_tugas,id_user,link_pengumpulan,tanggal)
    VALUES
    ('$id_tugas','$id_user','$link',CURDATE())");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tugas Online</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
    <span>Tugas Online</span>
    <a href="dashboard.php" class="btn-back">⬅ Kembali</a>
</header>

<div class="container">

<?php if($_SESSION['role']=="admin" || $_SESSION['role']=="guru"){ ?>
<form method="post">
    <h3>Tambah Tugas Online</h3>

    <input type="text" name="judul" placeholder="Judul" required>
    <input type="text" name="mapel" placeholder="Mata Pelajaran">

    <select name="id_guru" required>
        <option value="">Pilih Guru</option>
        <?php
        $guru = mysqli_query($conn,"SELECT * FROM tbguru");
        while($g=mysqli_fetch_assoc($guru)){
            echo "<option value='".$g['id_guru']."'>".$g['nama_guru']."</option>";
        }
        ?>
    </select>

    <input type="date" name="deadline" required>
    <input type="text" name="link_pengumpulan" placeholder="Link Pengumpulan">
    <textarea name="keterangan" placeholder="Keterangan"></textarea>

    <button type="submit" name="tambah" class="btn-upload">
        Tambah
    </button>
</form>
<?php } ?>

<?php
$q = mysqli_query($conn,"
SELECT tbtugas.*, tbguru.nama_guru
FROM tbtugas
LEFT JOIN tbguru ON tbtugas.id_guru = tbguru.id_guru
WHERE jenis='online'
ORDER BY deadline ASC
");

while($d=mysqli_fetch_assoc($q)){
?>
<div class="task-card">
    <h3><?= $d['judul']; ?></h3>
    <p><b>Mapel:</b> <?= $d['mapel']; ?></p>
    <p><b>Guru:</b> <?= $d['nama_guru']; ?></p>
    <p><b>Deadline:</b> <?= $d['deadline']; ?></p>
    <p><?= $d['keterangan']; ?></p>

    <?php if(!empty($d['link_pengumpulan'])){ ?>
        <p><a href="<?= $d['link_pengumpulan']; ?>" target="_blank">
            Buka Link Tugas
        </a></p>
    <?php } ?>
</div>
<?php } ?>

<?php if($_SESSION['role']=="siswa"){ ?>
<form method="post">
    <h3>Kumpulkan Tugas</h3>

    <select name="id_tugas" required>
        <option value="">Pilih Tugas</option>
        <?php
        $t = mysqli_query($conn,"SELECT id_tugas, judul FROM tbtugas WHERE jenis='online'");
        while($dt=mysqli_fetch_assoc($t)){
            echo "<option value='".$dt['id_tugas']."'>".$dt['judul']."</option>";
        }
        ?>
    </select>

    <input type="text" name="link" placeholder="Link Google Drive" required>

    <button type="submit" name="upload" class="btn-upload">
        Upload
    </button>
</form>
<?php } ?>

</div>

</body>
</html>