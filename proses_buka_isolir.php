<?php
session_start();
include 'koneksi.php';
include 'koneksi_mikrotik.php';

// Cek Login Admin
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php");
    exit();
}

if(isset($_GET['id'])) {
    $id_pelanggan = $_GET['id'];

    // 1. AMBIL DATA PELANGGAN & PAKET ASLINYA
    // Kita butuh tahu dia langganan paket apa, agar profilenya dikembalikan dengan benar
    $query = mysqli_query($koneksi, "
        SELECT p.*, pk.tipe, pk.profile_mikrotik 
        FROM pelanggan p 
        JOIN paket pk ON p.id_paket = pk.id_paket 
        WHERE p.id_pelanggan = '$id_pelanggan'
    ");
    
    $data = mysqli_fetch_assoc($query);
    
    if($data) {
        $username       = $data['username_pppoe'];
        $tipe           = $data['tipe']; // pppoe atau hotspot
        $profile_asli   = $data['profile_mikrotik']; // Ini profile target (misal: 10Mbps)

        // 2. UPDATE STATUS DATABASE JADI 'AKTIF'
        $update_db = mysqli_query($koneksi, "UPDATE pelanggan SET status='aktif' WHERE id_pelanggan='$id_pelanggan'");

        // 3. UPDATE MIKROTIK (RESTORE PROFILE)
        if ($update_db) {
            
            if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
                
                // --- JIKA PPPOE ---
                if ($tipe == 'pppoe') {
                    // Cari ID Secret berdasarkan username
                    $get_user = $API->comm("/ppp/secret/print", array("?name" => $username));
                    
                    if (count($get_user) > 0) {
                        // Kembalikan Profile ke Profile Asli (Bukan ISOLIR lagi)
                        $API->comm("/ppp/secret/set", array(
                            ".id"     => $get_user[0]['.id'],
                            "profile" => $profile_asli 
                        ));
                        
                        // Kick User agar reconnect dapat IP/Speed baru
                        $get_active = $API->comm("/ppp/active/print", array("?name" => $username));
                        if(count($get_active) > 0){
                            $API->comm("/ppp/active/remove", array(".id" => $get_active[0]['.id']));
                        }
                    }
                } 
                
                // --- JIKA HOTSPOT ---
                elseif ($tipe == 'hotspot') {
                    // Cari ID User Hotspot
                    $get_user = $API->comm("/ip/hotspot/user/print", array("?name" => $username));
                    
                    if (count($get_user) > 0) {
                        // Kembalikan Profile
                        $API->comm("/ip/hotspot/user/set", array(
                            ".id"     => $get_user[0]['.id'],
                            "profile" => $profile_asli
                        ));
                        
                        // Kick User
                        $get_active = $API->comm("/ip/hotspot/active/print", array("?user" => $username));
                        if(count($get_active) > 0){
                            $API->comm("/ip/hotspot/active/remove", array(".id" => $get_active[0]['.id']));
                        }
                    }
                }
                
                $API->disconnect();
                
                // Berhasil
                echo "<script>
                        alert('ISOLIR DIBUKA! Pelanggan $username kembali ke paket $profile_asli.'); 
                        window.location='semua_pelanggan.php';
                      </script>";
            } else {
                echo "<script>
                        alert('Database Update OK, Tapi Gagal Konek MikroTik. Cek koneksi router!'); 
                        window.location='semua_pelanggan.php';
                      </script>";
            }

        } else {
            echo "Gagal update database.";
        }
    } else {
        echo "Data pelanggan tidak ditemukan.";
    }

} else {
    echo "ID tidak valid.";
}
?>