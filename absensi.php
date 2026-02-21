<?php
session_start();
include "config/koneksi.php";

if(!isset($_SESSION['role'])){
    header("Location: login.php");
    exit;
}

if(isset($_POST['simpan']) && $_SESSION['role']=="admin"){

    $id_siswa = (int) $_POST['id_siswa'];
    $status   = $_POST['status'];

    if($id_siswa && $status){

        mysqli_query($conn,"INSERT INTO tbabsensi
        (id_siswa,tanggal,status)
        VALUES
        ('$id_siswa', CURDATE(), '$status')");
    }
}

if(isset($_POST['update']) && $_SESSION['role']=="admin"){

    $id_absensi = (int) $_POST['id_absensi'];
    $status     = $_POST['status'];

    mysqli_query($conn,"UPDATE tbabsensi
                        SET status='$status'
                        WHERE id_absensi='$id_absensi'");
}

$edit_data = null;

if(isset($_GET['edit'])){
    $id_edit = (int) $_GET['edit'];
    $res = mysqli_query($conn,
        "SELECT * FROM tbabsensi WHERE id_absensi='$id_edit'"
    );
    $edit_data = mysqli_fetch_assoc($res);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Absensi</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
  <span>Absensi Siswa</span>
  <a href="dashboard.php" class="btn-back">⬅ Kembali</a>
</header>

<div class="container">

<?php if($_SESSION['role']=="admin"){ ?>

<form method="post">
  <h3><?= $edit_data ? "Edit Absensi" : "Input Absensi"; ?></h3>

  <?php if($edit_data){ ?>
      <input type="hidden" name="id_absensi"
             value="<?= $edit_data['id_absensi']; ?>">
  <?php } ?>

  <select name="id_siswa" required <?= $edit_data ? "disabled" : ""; ?>>
    <option value="">-- Pilih Siswa --</option>
    <?php
    $s = mysqli_query($conn,"SELECT * FROM tbsiswa");
    while($d=mysqli_fetch_assoc($s)){

        $selected = ($edit_data &&
                     $edit_data['id_siswa']==$d['id_siswa'])
                     ? "selected" : "";

        echo "<option value='".$d['id_siswa']."' $selected>
              ".$d['nama_siswa']."</option>";
    }
    ?>
  </select>

  <select name="status" required>
    <option value="">-- Pilih Status --</option>
    <?php
    $status_list = ["hadir","izin","sakit","alpha"];
    foreach($status_list as $st){

        $selected = ($edit_data &&
                     $edit_data['status']==$st)
                     ? "selected" : "";

        echo "<option value='$st' $selected>
              ".ucfirst($st)."</option>";
    }
    ?>
  </select>

  <?php if($edit_data){ ?>
      <button type="submit"
              name="update"
              class="btn-upload">Update</button>

      <a href="absensi.php"
         class="btn-back">Batal</a>
  <?php } else { ?>
      <button type="submit"
              name="simpan"
              class="btn-upload">Simpan</button>
  <?php } ?>

</form>

<?php } ?>

<table>
<tr>
  <th>Nama</th>
  <th>Tanggal</th>
  <th>Status</th>
  <?php if($_SESSION['role']=="admin"){ ?>
      <th>Aksi</th>
  <?php } ?>
</tr>

<?php
$q = mysqli_query($conn,"
SELECT tbabsensi.*, tbsiswa.nama_siswa
FROM tbabsensi
JOIN tbsiswa
ON tbabsensi.id_siswa = tbsiswa.id_siswa
ORDER BY tbabsensi.tanggal DESC
");

while($a=mysqli_fetch_assoc($q)){

  $class="";
  if($a['status']=="hadir") $class="btn-hadir";
  if($a['status']=="izin")  $class="btn-izin";
  if($a['status']=="sakit") $class="btn-sakit";
  if($a['status']=="alpha") $class="btn-alpha";
?>
<tr>
  <td><?= $a['nama_siswa']; ?></td>
  <td><?= $a['tanggal']; ?></td>
  <td>
    <span class="btn <?= $class ?>">
      <?= ucfirst($a['status']); ?>
    </span>
  </td>

  <?php if($_SESSION['role']=="admin"){ ?>
  <td>
    <a href="absensi.php?edit=<?= $a['id_absensi']; ?>"
       class="btn btn-izin">
       Edit
    </a>
  </td>
  <?php } ?>
</tr>
<?php } ?>

</table>

</div>

</body>
</html>