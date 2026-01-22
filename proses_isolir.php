<?php
include 'koneksi.php';
include 'koneksi_mikrotik.php';

$tgl_hari_ini = date('d'); // Tanggal sekarang (1-31)
$bulan_ini    = date('F Y'); // Bulan Tagihan (January 2026)

$jumlah_isolir = 0;

// 1. AMBIL SEMUA PELANGGAN YG MASIH AKTIF
// Kita perlu tahu tipe paketnya (pppoe/hotspot) untuk perintah mikrotik yg benar
$query = mysqli_query($koneksi, "
    SELECT p.*, pk.tipe, pk.profile_mikrotik 
    FROM pelanggan p 
    JOIN paket pk ON p.id_paket = pk.id_paket 
    WHERE p.status = 'aktif'
");

if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {

    while ($row = mysqli_fetch_array($query)) {
        
        $tgl_jatuh_tempo = $row['tgl_jatuh_tempo'];
        $id_pelanggan    = $row['id_pelanggan'];
        $username        = $row['username_pppoe'];
        $tipe            = $row['tipe']; // pppoe atau hotspot

        // 2. LOGIKA CEK TELAT BAYAR
        // Jika tanggal hari ini LEBIH BESAR dari jatuh tempo
        if ($tgl_hari_ini > $tgl_jatuh_tempo) {
            
            // Cek di tabel TAGIHAN, apakah bulan ini sudah ada data LUNAS?
            $cek_bayar = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_pelanggan='$id_pelanggan' AND bulan='$bulan_ini' AND status='Lunas'");
            
            // Jika BELUM BAYAR (Query kosong)
            if (mysqli_num_rows($cek_bayar) == 0) {
                
                // --- EKSEKUSI ISOLIR ---

                // A. Update Database MySQL jadi 'isolir'
                mysqli_query($koneksi, "UPDATE pelanggan SET status='isolir' WHERE id_pelanggan='$id_pelanggan'");

                // B. Update MikroTik (Ganti Profile jadi ISOLIR)
                if ($tipe == 'pppoe') {
                    // Cari ID Secret berdasarkan nama
                    $get_user = $API->comm("/ppp/secret/print", array("?name" => $username));
                    if (count($get_user) > 0) {
                        $uid = $get_user[0]['.id'];
                        
                        // Ubah Profile ke ISOLIR
                        $API->comm("/ppp/secret/set", array(
                            ".id"     => $uid,
                            "profile" => "ISOLIR" // Pastikan profile ini ada di Winbox
                        ));
                        
                        // Kick User agar login ulang dan kena efek Isolir
                        $get_active = $API->comm("/ppp/active/print", array("?name" => $username));
                        if(count($get_active) > 0){
                             $API->comm("/ppp/active/remove", array(".id" => $get_active[0]['.id']));
                        }
                    }
                } 
                elseif ($tipe == 'hotspot') {
                    // Hotspot User
                    $get_user = $API->comm("/ip/hotspot/user/print", array("?name" => $username));
                    if (count($get_user) > 0) {
                        $uid = $get_user[0]['.id'];
                        
                        // Ubah Profile
                        $API->comm("/ip/hotspot/user/set", array(
                            ".id"     => $uid,
                            "profile" => "ISOLIR"
                        ));
                        
                        // Kick User
                        $get_active = $API->comm("/ip/hotspot/active/print", array("?user" => $username));
                        if(count($get_active) > 0){
                             $API->comm("/ip/hotspot/active/remove", array(".id" => $get_active[0]['.id']));
                        }
                    }
                }

                $jumlah_isolir++;
            }
        }
    }
    
    $API->disconnect();
    
    // Selesai, tampilkan laporan
    echo "
    <script>
        alert('Proses Selesai! $jumlah_isolir pelanggan berhasil di-isolir otomatis.');
        window.location='semua_pelanggan.php';
    </script>";

} else {
    echo "Gagal koneksi ke MikroTik. Cek IP/User/Pass.";
}
?>