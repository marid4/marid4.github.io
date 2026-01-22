<?php
// 1. MULAI SESI PALING AWAL
session_start();

// 2. HUBUNGKAN DATABASE & MIKROTIK
include 'koneksi.php'; 
include 'koneksi_mikrotik.php';

// --- 3. AMBIL DATA DARI FORM ---
// Gunakan Null Coalescing Operator (??) untuk mencegah error jika data kosong
$qty          = (int)($_POST['qty'] ?? 0);
$profile      = $_POST['profile'] ?? '';
$harga        = $_POST['harga'] ?? 0;
$durasi_label = $_POST['durasi'] ?? '';       
$limit_uptime = $_POST['limit_uptime'] ?? ''; 
$quota_angka  = $_POST['limit_quota_angka'] ?? '';
$quota_satuan = $_POST['limit_quota_satuan'] ?? '';

// Data Generator Kode
$length       = $_POST['length'] ?? 6;
$prefix       = $_POST['prefix'] ?? '';
$same_pass    = isset($_POST['user_same_pass']);
$admin_name   = $_SESSION['username'] ?? 'Admin';

// Validasi dasar
if ($qty < 1 || empty($profile)) {
    die("Data tidak lengkap. <a href='voucher.php'>Kembali</a>");
}

// --- 4. SIAPKAN LIMIT KUOTA ---
$limit_bytes_total = "";
if(!empty($quota_angka) && $quota_angka > 0){
    $limit_bytes_total = $quota_angka . $quota_satuan; 
}

function generateCode($len) {
    $chars = "23456789abcdefghjkmnpqrstuvwxyz"; 
    $res = "";
    for ($i = 0; $i < $len; $i++) $res .= $chars[rand(0, strlen($chars) - 1)];
    return $res;
}

$generated_users = [];

// --- 5. EKSEKUSI KE MIKROTIK ---
if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    
    $API->debug = false; 

    // Batch ID Unik untuk komentar (agar bisa dilacak nanti)
    $batch_id = date('dmy-His');
    $comment_text = "VC $durasi_label | $batch_id";

    for ($i = 0; $i < $qty; $i++) {
        $code = $prefix . generateCode($length);
        $u = $code;
        $p = $same_pass ? $code : generateCode(4);

        $data_mikrotik = array(
            "server"   => "all",
            "profile"  => $profile,
            "name"     => $u,
            "password" => $p,
            "comment"  => $comment_text
        );

        if (!empty($limit_uptime)) $data_mikrotik['limit-uptime'] = (string)$limit_uptime;
        if (!empty($limit_bytes_total)) $data_mikrotik['limit-bytes-total'] = (string)$limit_bytes_total;

        $API->comm("/ip/hotspot/user/add", $data_mikrotik);

        $generated_users[] = [
            'username' => $u, 'password' => $p, 
            'harga'    => $harga, 'durasi'   => $durasi_label, 
            'profile'  => $profile
        ];
    }
    
    $API->disconnect();

    // --- 6. SIMPAN LOG KE DATABASE ---
    $total_nominal = $qty * $harga;
    $tgl_sekarang = date('Y-m-d H:i:s');
    
    // Pastikan tabel ada, jika belum buat tabelnya (Safety)
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `riwayat_voucher` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
      `profile` varchar(50) NOT NULL,
      `qty` int(11) NOT NULL,
      `harga_satuan` decimal(10,0) NOT NULL,
      `total_nominal` decimal(10,0) NOT NULL,
      `admin` varchar(50) DEFAULT 'System',
      PRIMARY KEY (`id`)
    )");

    $query_log = "INSERT INTO riwayat_voucher (tanggal, profile, qty, harga_satuan, total_nominal, admin) 
                  VALUES ('$tgl_sekarang', '$profile', '$qty', '$harga', '$total_nominal', '$admin_name')";
    mysqli_query($koneksi, $query_log);

    // --- 7. SIMPAN SESSION & REDIRECT (FIX UTAMA) ---
    $_SESSION['vouchers'] = $generated_users;
    
    // PENTING: Tulis sesi ke disk sebelum redirect agar tidak hilang
    session_write_close(); 
    
    header("Location: cetak_voucher.php");
    exit();

} else {
    echo "<div style='text-align:center; margin-top:50px; color:red; font-family:sans-serif;'>
            <h3>GAGAL KONEKSI KE MIKROTIK</h3>
            <p>Sistem tidak dapat terhubung ke Router.</p>
            <a href='voucher.php' style='padding:10px; background:#ddd; text-decoration:none;'>Kembali</a>
          </div>";
}
?>