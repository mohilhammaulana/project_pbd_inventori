<?php
$host = "localhost";
$user = "root";
$pass = ""; // Password default Laragon biasanya kosong
$db   = "db_proyek_pbd";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>