<?php
session_start();
include 'koneksi.php';
include 'koneksi_mikrotik.php';

// Cek Login
if(!isset($_SESSION['status'])){ header("location:login.php"); exit(); }

$id_pelanggan = $_GET['id'];

if(isset($id_pelanggan)) {
    
    // 1. Ambil Data Pelanggan
    $query = mysqli_query($koneksi, "
        SELECT p.*, pk.tipe 
        FROM pelanggan p 
        JOIN paket pk ON p.id_paket = pk.id_paket 
        WHERE p.id_pelanggan = '$id_pelanggan'
    ");
    $data = mysqli_fetch_assoc($query);
    
    $username = $data['username_pppoe'];
    $tipe     = $data['tipe']; // pppoe atau hotspot

    // 2. Update Database MySQL
    mysqli_query($koneksi, "UPDATE pelanggan SET status='isolir' WHERE id_pelanggan='$id_pelanggan'");

    // 3. Eksekusi ke MikroTik
    if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
        
        if ($tipe == 'pppoe') {
            // Ubah Secret ke Profile ISOLIR
            $get_user = $API->comm("/ppp/secret/print", array("?name" => $username));
            if (count($get_user) > 0) {
                $API->comm("/ppp/secret/set", array(
                    ".id"     => $get_user[0]['.id'],
                    "profile" => "ISOLIR" // Sesuai nama profile di Winbox Langkah 1
                ));
                
                // Kick User agar IP berubah ke IP Isolir
                $get_active = $API->comm("/ppp/active/print", array("?name" => $username));
                if(count($get_active) > 0){
                    $API->comm("/ppp/active/remove", array(".id" => $get_active[0]['.id']));
                }
            }
        } 
        elseif ($tipe == 'hotspot') {
            // Ubah User Hotspot ke Profile ISOLIR
            $get_user = $API->comm("/ip/hotspot/user/print", array("?name" => $username));
            if (count($get_user) > 0) {
                $API->comm("/ip/hotspot/user/set", array(
                    ".id"     => $get_user[0]['.id'],
                    "profile" => "ISOLIR"
                ));
                
                // Kick User
                $get_active = $API->comm("/ip/hotspot/active/print", array("?user" => $username));
                if(count($get_active) > 0){
                    $API->comm("/ip/hotspot/active/remove", array(".id" => $get_active[0]['.id']));
                }
            }
        }
        
        $API->disconnect();
        echo "<script>alert('Pelanggan BERHASIL DI-ISOLIR dan dialihkan ke halaman peringatan.'); window.location='semua_pelanggan.php';</script>";
        
    } else {
        echo "<script>alert('Database update, tapi GAGAL konek MikroTik.'); window.location='semua_pelanggan.php';</script>";
    }

} else {
    echo "ID tidak ditemukan";
}
?>