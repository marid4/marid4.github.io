<?php
include 'koneksi.php';
include 'koneksi_mikrotik.php'; // Tambahkan ini

$id_pelanggan = $_GET['id'];
$bulan        = $_GET['bulan']; 

if(isset($id_pelanggan) && isset($bulan)){
    
    // Cek double bayar
    $cek = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_pelanggan='$id_pelanggan' AND bulan='$bulan'");
    if(mysqli_num_rows($cek) > 0){
        header("Location: billing.php?filter_bulan=".date('Y-m', strtotime($bulan)));
        exit();
    }

    // 1. SIMPAN TAGIHAN (LUNAS)
    $query = "INSERT INTO tagihan (id_pelanggan, bulan, status, tgl_bayar) VALUES ('$id_pelanggan', '$bulan', 'Lunas', NOW())";
    
    if(mysqli_query($koneksi, $query)){
        
        // 2. BUKA ISOLIR (RESTORE PROFILE)
        // Ambil info user & paket aslinya
        $q_user = mysqli_query($koneksi, "SELECT p.*, pk.profile_mikrotik, pk.tipe FROM pelanggan p JOIN paket pk ON p.id_paket = pk.id_paket WHERE p.id_pelanggan='$id_pelanggan'");
        $d_user = mysqli_fetch_assoc($q_user);
        
        $username_asli = $d_user['username_pppoe'];
        $profile_asli  = $d_user['profile_mikrotik']; // Profile internet normal (misal: 10Mbps)
        $tipe          = $d_user['tipe'];

        // Update Database jadi 'aktif' lagi
        mysqli_query($koneksi, "UPDATE pelanggan SET status='aktif' WHERE id_pelanggan='$id_pelanggan'");

        // Update MikroTik (Kembalikan Profile)
        if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
            
            if ($tipe == 'pppoe') {
                $get_user = $API->comm("/ppp/secret/print", array("?name" => $username_asli));
                if (count($get_user) > 0) {
                    $API->comm("/ppp/secret/set", array(
                        ".id"     => $get_user[0]['.id'],
                        "profile" => $profile_asli // Kembalikan ke profile paket
                    ));
                    // Kick agar speed balik normal
                     $get_act = $API->comm("/ppp/active/print", array("?name" => $username_asli));
                     if(count($get_act)>0) $API->comm("/ppp/active/remove", array(".id" => $get_act[0]['.id']));
                }
            } 
            elseif ($tipe == 'hotspot') {
                $get_user = $API->comm("/ip/hotspot/user/print", array("?name" => $username_asli));
                if (count($get_user) > 0) {
                    $API->comm("/ip/hotspot/user/set", array(
                        ".id"     => $get_user[0]['.id'],
                        "profile" => $profile_asli
                    ));
                     $get_act = $API->comm("/ip/hotspot/active/print", array("?user" => $username_asli));
                     if(count($get_act)>0) $API->comm("/ip/hotspot/active/remove", array(".id" => $get_act[0]['.id']));
                }
            }
            $API->disconnect();
        }

        // Redirect balik
        $filter_date = date('Y-m', strtotime($bulan));
        header("Location: billing.php?filter_bulan=$filter_date");

    } else {
        echo "Gagal menyimpan: " . mysqli_error($koneksi);
    }
    
} else {
    echo "Data tidak lengkap";
}
?>