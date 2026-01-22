<?php
session_start();
// Cek Login Admin
if(!isset($_SESSION['status'])){ header("location:login.php"); exit(); }

include 'koneksi_mikrotik.php';

// Nama File Download
$filename = "Backup_User_Mikrotik_" . date('Y-m-d_H-i') . ".csv";

// Header agar browser mengenali ini sebagai file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis Judul Kolom (Header CSV)
fputcsv($output, array('Tipe Service', 'Username', 'Password', 'Profile', 'Limit Uptime', 'Limit Bytes', 'Komentar'));

if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {

    // --- 1. AMBIL DATA PPPOE ---
    $pppoe = $API->comm("/ppp/secret/print");
    
    foreach ($pppoe as $p) {
        $limit_bytes = isset($p['limit-bytes-out']) ? $p['limit-bytes-out'] : '';
        
        $lineData = array(
            'PPPoE', 
            $p['name'], 
            isset($p['password']) ? $p['password'] : '', 
            isset($p['profile']) ? $p['profile'] : 'default',
            '-', // PPPoE jarang pakai limit uptime di secret
            $limit_bytes,
            isset($p['comment']) ? $p['comment'] : ''
        );
        fputcsv($output, $lineData);
    }

    // --- 2. AMBIL DATA HOTSPOT ---
    $hotspot = $API->comm("/ip/hotspot/user/print");
    
    foreach ($hotspot as $h) {
        // Lewatkan user default sistem (default-trial, dll)
        if(substr($h['name'], 0, 1) == '*') continue; 

        $limit_uptime = isset($h['limit-uptime']) ? $h['limit-uptime'] : '';
        $limit_bytes  = isset($h['limit-bytes-total']) ? $h['limit-bytes-total'] : '';

        $lineData = array(
            'Hotspot', 
            $h['name'], 
            isset($h['password']) ? $h['password'] : '', 
            isset($h['profile']) ? $h['profile'] : 'default',
            $limit_uptime,
            $limit_bytes,
            isset($h['comment']) ? $h['comment'] : ''
        );
        fputcsv($output, $lineData);
    }

    $API->disconnect();
} else {
    // Jika gagal konek, tulis error di file
    fputcsv($output, array('ERROR: Gagal Koneksi ke MikroTik'));
}

fclose($output);
exit();
?>