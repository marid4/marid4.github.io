<?php
session_start();
include 'koneksi.php';
include 'koneksi_mikrotik.php';

// Cek Koneksi MikroTik dulu sebelum simpan database
if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {

    // --- 1. TANGKAP DATA DARI FORM ---
    $service    = $_POST['service']; // 'pppoe' atau 'hotspot'
    
    // Data Pelanggan
    $nama       = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $alamat     = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $no_wa      = mysqli_real_escape_string($koneksi, $_POST['no_wa']);
    
    // Data Koneksi & Harga
    $username   = $_POST['username'];
    $password   = $_POST['password'];
    $profile    = $_POST['profile'];
    $harga      = $_POST['harga']; // Ini yang penting untuk billing!

    // Data Advanced (Bisa Kosong)
    $rate_limit     = $_POST['rate_limit'] ?? '';
    $limit_uptime   = $_POST['limit_uptime'] ?? '';
    $limit_bytes    = $_POST['limit_bytes'] ?? '';
    $remote_address = $_POST['remote_address'] ?? '';
    $mac_address    = $_POST['mac_address'] ?? '';


    // --- 2. LOGIKA DATABASE (AUTO CREATE PAKET) ---
    // Karena ini user custom, kita buatkan entry paket khusus di database 
    // agar sistem billing tahu berapa tagihannya.
    
    $nama_paket_custom = " $profile " . $nama; // Cth: Custom - Budi
    $kecepatan_info    = ($rate_limit != '') ? $rate_limit : "Sesuai Profile $profile";
    
    // Insert ke tabel PAKET dulu
    $q_paket = "INSERT INTO paket (tipe, nama_paket, profile_mikrotik, harga, kecepatan) 
                VALUES ('$service', '$nama_paket_custom', '$profile', '$harga', '$kecepatan_info')";
    
    if(mysqli_query($koneksi, $q_paket)) {
        // Ambil ID Paket yang barusan dibuat
        $id_paket_baru = mysqli_insert_id($koneksi);
        
        // Insert ke tabel PELANGGAN menggunakan ID Paket Baru
        $q_pelanggan = "INSERT INTO pelanggan (nama, alamat, no_wa, username_pppoe, password_pppoe, id_paket, status)
                        VALUES ('$nama', '$alamat', '$no_wa', '$username', '$password', '$id_paket_baru', 'aktif')";
        
        mysqli_query($koneksi, $q_pelanggan);
        
    } else {
        die("Error Database: " . mysqli_error($koneksi));
    }


    // --- 3. LOGIKA MIKROTIK (PUSH DATA) ---
    
    $mikrotik_data = [
        "name"     => $username,
        "password" => $password,
        "profile"  => $profile,
        "comment"  => "$nama | $no_wa | PRO"
    ];

    if ($service == 'pppoe') {
        $mikrotik_data["service"] = "pppoe";
        if (!empty($rate_limit))     $mikrotik_data["rate-limit"] = $rate_limit;
        if (!empty($remote_address)) $mikrotik_data["remote-address"] = $remote_address;
        if (!empty($limit_bytes))    $mikrotik_data["limit-bytes-out"] = $limit_bytes;
        
        $API->comm("/ppp/secret/add", $mikrotik_data);
    } 
    elseif ($service == 'hotspot') {
        $mikrotik_data["server"] = "all";
        if (!empty($limit_uptime)) $mikrotik_data["limit-uptime"] = $limit_uptime;
        if (!empty($limit_bytes))  $mikrotik_data["limit-bytes-total"] = $limit_bytes;
        if (!empty($mac_address))  $mikrotik_data["mac-address"] = $mac_address;
        
        $API->comm("/ip/hotspot/user/add", $mikrotik_data);
    }

    $API->disconnect();
    
    echo "<script>alert('Sukses! Pelanggan Pro tersimpan di Database & MikroTik.'); window.location='index.php';</script>";

} else {
    echo "Gagal Konek ke MikroTik. Percek IP/User/Pass.";
}
?>