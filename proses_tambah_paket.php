<?php
include 'koneksi.php';
include 'koneksi_mikrotik.php'; // Sertakan koneksi mikrotik jika ingin auto-create

$tipe             = $_POST['tipe']; // 'pppoe' atau 'hotspot'
$nama_paket       = $_POST['nama_paket'];
$profile_mikrotik = $_POST['profile_mikrotik'];
$harga            = $_POST['harga'];
$kecepatan        = $_POST['kecepatan'];

// 1. SIMPAN KE DATABASE
// Pastikan kolom 'tipe' sudah dibuat di database (Langkah 1)
$query = "INSERT INTO paket (tipe, nama_paket, profile_mikrotik, harga, kecepatan) 
          VALUES ('$tipe', '$nama_paket', '$profile_mikrotik', '$harga', '$kecepatan')";

if (mysqli_query($koneksi, $query)) {
    
    // 2. (OPSIONAL) AUTO CREATE PROFILE DI MIKROTIK
    // Jika Anda ingin saat klik simpan, profil juga dibuat di Winbox
    if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
        
        // Cek Tipe
        if ($tipe == 'pppoe') {
            // Tambah PPP Profile
            // Rate limit default misal 2M/2M (bisa disesuaikan logicnya nanti)
            $API->comm("/ppp/profile/add", array(
                "name" => $profile_mikrotik,
                "rate-limit" => "2M/2M", 
                "comment" => "Dibuat dari Web Billing - Rp $harga"
            ));
        } 
        else if ($tipe == 'hotspot') {
            // Tambah Hotspot User Profile
            $API->comm("/ip/hotspot/user/profile/add", array(
                "name" => $profile_mikrotik,
                "rate-limit" => "2M/2M",
                "shared-users" => "1",
                "status-autorefresh" => "1m"
            ));
        }

        $API->disconnect();
    }

    echo "<script>alert('Sukses! Paket $tipe berhasil dibuat.'); window.location='index.php';</script>";

} else {
    echo "Error Database: " . mysqli_error($koneksi);
}
?>