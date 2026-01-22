<?php
session_start();
include 'koneksi.php';
include 'koneksi_mikrotik.php'; 

// AMBIL DATA DASAR
$tipe_koneksi   = $_POST['tipe_koneksi']; 
$nama           = $_POST['nama'];
$alamat         = $_POST['alamat'];
$no_wa          = $_POST['no_wa'];
$username       = $_POST['username_pppoe'];
$password       = $_POST['password_pppoe'];
$id_paket       = $_POST['id_paket'];

// AMBIL DATA ADVANCED (Bisa Kosong)
$rate_limit     = $_POST['rate_limit'] ?? '';
$limit_uptime   = $_POST['limit_uptime'] ?? '';
$limit_bytes    = $_POST['limit_bytes'] ?? '';
$remote_address = $_POST['remote_address'] ?? '';
$local_address  = $_POST['local_address'] ?? '';
$mac_address    = $_POST['mac_address'] ?? '';
$ip_address     = $_POST['ip_address'] ?? ''; // Untuk hotspot static

// 1. AMBIL PROFILE DARI DATABASE PAKET
$q_paket = mysqli_query($koneksi, "SELECT profile_mikrotik FROM paket WHERE id_paket='$id_paket'");
$d_paket = mysqli_fetch_assoc($q_paket);
$profile_target = $d_paket['profile_mikrotik'];

// 2. SIMPAN KE MYSQL (Billing)
$simpan_db = mysqli_query($koneksi, "INSERT INTO pelanggan (nama, alamat, no_wa, username_pppoe, password_pppoe, id_paket, status) VALUES ('$nama', '$alamat', '$no_wa', '$username', '$password', '$id_paket', 'aktif')");

if ($simpan_db) {
    
    // 3. PUSH KE MIKROTIK (Dengan Parameter Tambahan)
    if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
        
        // ARRAY DATA DASAR
        $mikrotik_data = [
            "name"     => $username,
            "password" => $password,
            "profile"  => $profile_target,
            "comment"  => "$nama | $no_wa"
        ];

        if ($tipe_koneksi == 'pppoe') {
            $mikrotik_data["service"] = "pppoe";
            // Tambahkan parameter advanced jika diisi
            if(!empty($rate_limit))     $mikrotik_data["rate-limit"] = $rate_limit;
            if(!empty($remote_address)) $mikrotik_data["remote-address"] = $remote_address;
            if(!empty($local_address))  $mikrotik_data["local-address"] = $local_address;
            if(!empty($limit_bytes))    $mikrotik_data["limit-bytes-out"] = $limit_bytes; // Biasanya Out = Download user
            
            $API->comm("/ppp/secret/add", $mikrotik_data);
        } 
        elseif ($tipe_koneksi == 'hotspot') {
            $mikrotik_data["server"] = "all";
            // Tambahkan parameter advanced
            if(!empty($limit_uptime)) $mikrotik_data["limit-uptime"] = $limit_uptime;
            if(!empty($limit_bytes))  $mikrotik_data["limit-bytes-total"] = $limit_bytes;
            if(!empty($mac_address))  $mikrotik_data["mac-address"] = $mac_address;
            if(!empty($ip_address))   $mikrotik_data["address"] = $ip_address;

            $API->comm("/ip/hotspot/user/add", $mikrotik_data);
        }
        
        $API->disconnect();
        echo "<script>alert('Sukses! User berhasil ditambahkan ke Database & MikroTik.'); window.location='index.php';</script>";
        
    } else {
        echo "<script>alert('Database tersimpan, tapi GAGAL konek MikroTik.'); window.location='index.php';</script>";
    }

} else {
    echo "Gagal simpan database: " . mysqli_error($koneksi);
}
?>