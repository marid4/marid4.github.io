<?php
include 'koneksi.php';
include 'koneksi_mikrotik.php';

$id = $_GET['id'];

// 1. AMBIL DATA PELANGGAN & PAKET
$query = mysqli_query($koneksi, "
    SELECT p.*, pk.nama_paket, pk.harga, pk.tipe, pk.profile_mikrotik 
    FROM pelanggan p 
    JOIN paket pk ON p.id_paket = pk.id_paket 
    WHERE p.id_pelanggan = '$id'
");
$data = mysqli_fetch_assoc($query);

if(!$data) { echo "Data tidak ditemukan"; exit(); }

// 2. CEK STATUS LIVE DI MIKROTIK
$mikrotik_info = "Offline / Tidak Terkoneksi";
$last_logged_out = "-";
$uptime = "-";
$ip_address = "-";
$mac_address = "-";

if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    $username = $data['username_pppoe'];
    $tipe = $data['tipe'];

    if($tipe == 'pppoe'){
        // Cek Active Connection
        $active = $API->comm("/ppp/active/print", array("?name" => $username));
        if(count($active) > 0){
            $mikrotik_info = "<span class='badge bg-success'>Online</span>";
            $uptime = $active[0]['uptime'];
            $ip_address = $active[0]['address'];
            $mac_address = $active[0]['caller-id'];
        }
    } elseif($tipe == 'hotspot'){
        // Cek Active Hotspot
        $active = $API->comm("/ip/hotspot/active/print", array("?user" => $username));
        if(count($active) > 0){
            $mikrotik_info = "<span class='badge bg-success'>Online</span>";
            $uptime = $active[0]['uptime'];
            $ip_address = $active[0]['address'];
            $mac_address = $active[0]['mac-address'];
        }
    }
    $API->disconnect();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail: <?php echo $data['nama']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .card-detail { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .label-field { font-size: 0.8rem; font-weight: bold; color: #6c757d; text-transform: uppercase; }
        .value-field { font-weight: 600; font-size: 1rem; color: #212529; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white shadow-sm sticky-top mb-4">
        <div class="container">
            <a class="btn btn-secondary btn-sm rounded-circle" href="semua_pelanggan.php"><i class="fas fa-arrow-left"></i></a>
            <span class="navbar-brand mb-0 h1 fs-6 fw-bold mx-auto">Detail Pelanggan</span>
        </div>
    </nav>

    <div class="container pb-5">

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card card-detail h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user-circle"></i> Profil & Koneksi</h6>
                    </div>
                    <div class="card-body">
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge <?php echo ($data['status']=='aktif')?'bg-success':'bg-danger';?> px-3 py-2">
                                Status DB: <?php echo strtoupper($data['status']); ?>
                            </span>
                            <span><?php echo $mikrotik_info; ?></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="label-field">Nama Lengkap</div>
                                <div class="value-field"><?php echo $data['nama']; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">Paket</div>
                                <div class="value-field"><?php echo $data['nama_paket']; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">Tagihan</div>
                                <div class="value-field">Rp <?php echo number_format($data['harga']); ?></div>
                            </div>
                            <div class="col-12">
                                <hr>
                            </div>
                            <div class="col-6">
                                <div class="label-field">Username (MikroTik)</div>
                                <div class="value-field text-primary"><?php echo $data['username_pppoe']; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">Password</div>
                                <div class="value-field bg-light px-2 rounded"><?php echo $data['password_pppoe']; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">IP Address (Live)</div>
                                <div class="value-field"><?php echo $ip_address; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">MAC Address</div>
                                <div class="value-field small"><?php echo $mac_address; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">Uptime</div>
                                <div class="value-field"><?php echo $uptime; ?></div>
                            </div>
                            <div class="col-6">
                                <div class="label-field">Jatuh Tempo</div>
                                <div class="value-field text-danger">Tgl <?php echo $data['tgl_jatuh_tempo']; ?></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card card-detail h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-success"><i class="fas fa-history"></i> Riwayat Pembayaran</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 small align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Tgl Bayar</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_riwayat = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_pelanggan='$id' ORDER BY id_tagihan DESC LIMIT 10");
                                    if(mysqli_num_rows($q_riwayat) > 0){
                                        while($r = mysqli_fetch_array($q_riwayat)){
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $r['bulan']; ?></td>
                                        <td><?php echo date('d/m/y H:i', strtotime($r['tgl_bayar'])); ?></td>
                                        <td><span class="badge bg-success">Lunas</span></td>
                                        <td>
                                            <a href="cetak_struk.php?id=<?php echo $r['id_tagihan']; ?>" class="btn btn-sm btn-dark py-0" style="font-size: 0.7rem;">Struk</a>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-3 text-muted'>Belum ada riwayat pembayaran.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="https://wa.me/<?php echo $data['no_wa']; ?>" target="_blank" class="btn btn-success fw-bold">
                <i class="fab fa-whatsapp"></i> Chat WhatsApp
            </a>
            <?php if($data['status'] == 'isolir') { ?>
                <a href="proses_buka_isolir.php?id=<?php echo $id; ?>" class="btn btn-danger fw-bold" onclick="return confirm('Buka Isolir?')">
                    <i class="fas fa-lock-open"></i> BUKA ISOLIR SEKARANG
                </a>
            <?php } ?>
        </div>

    </div>
    
</body>
</html>