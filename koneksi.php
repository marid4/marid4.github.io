<?php
$host = "localhost";
$user = "root"; // Sesuaikan user database
$pass = "";     // Sesuaikan password database
$db   = "db_rtrwnet";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>