<?php
session_start();

// Cek Login
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php");
    exit();
}

// 1. HUBUNGKAN DATABASE
include 'koneksi.php'; 
include 'koneksi_mikrotik.php'; 

$bulan_ini = date('F Y'); 

// --- PERBAIKAN LOGIKA KEUANGAN (MENGGUNAKAN LEFT JOIN & COALESCE) ---

// A. Hitung TOTAL TARGET (Semua Pelanggan Aktif)
// Pakai LEFT JOIN agar jika paket terhapus, user tetap terhitung
$q_total = mysqli_query($koneksi, "
    SELECT 
        COUNT(pelanggan.id_pelanggan) as total_user, 
        SUM(COALESCE(paket.harga, 0)) as total_omset 
    FROM pelanggan 
    LEFT JOIN paket ON pelanggan.id_paket = paket.id_paket 
    WHERE pelanggan.status = 'aktif'
");
$d_total = mysqli_fetch_assoc($q_total);

// Pastikan angka minimal 0 (bukan blank/null)
$total_user_aktif    = $d_total['total_user'] ?? 0;
$total_omset_potensi = $d_total['total_omset'] ?? 0;


// B. Hitung YANG SUDAH BAYAR (Lunas)
$q_lunas = mysqli_query($koneksi, "
    SELECT 
        COUNT(t.id_tagihan) as user_lunas,
        SUM(COALESCE(pk.harga, 0)) as uang_lunas
    FROM tagihan t
    LEFT JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    LEFT JOIN paket pk ON p.id_paket = pk.id_paket
    WHERE t.bulan = '$bulan_ini' AND t.status = 'Lunas'
");
$d_lunas = mysqli_fetch_assoc($q_lunas);

$user_lunas = $d_lunas['user_lunas'] ?? 0;
$uang_lunas = $d_lunas['uang_lunas'] ?? 0;


// C. Hitung YANG BELUM BAYAR (Sisa)
$user_belum = $total_user_aktif - $user_lunas;
$uang_belum = $total_omset_potensi - $uang_lunas;

// Mencegah angka minus jika data tidak konsisten
if($user_belum < 0) $user_belum = 0;
if($uang_belum < 0) $uang_belum = 0;


// --- LOGIKA DATA PELANGGAN (TOTAL DATABASE) ---
$q_pelanggan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pelanggan WHERE status != 'nonaktif'");
$d_pelanggan = mysqli_fetch_assoc($q_pelanggan);
$total_pelanggan = $d_pelanggan['total'] ?? 0;

$q_pendapatan = mysqli_query($koneksi, "
    SELECT SUM(COALESCE(paket.harga, 0)) as omset 
    FROM pelanggan 
    LEFT JOIN paket ON pelanggan.id_paket = paket.id_paket 
    WHERE pelanggan.status = 'aktif'
");
$d_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$total_pendapatan = $d_pendapatan['omset'] ?? 0;


// --- LOGIKA MIKROTIK (Status & Resource) ---
$mikrotik_text = "Offline"; 
$mikrotik_color = "bg-danger"; 
$uptime = "-"; $identity = "-"; $model = "-"; $version = "-";
$cpu_load = "0%"; $mem_info = "- / -";
$hotspot_active = 0; $pppoe_active = 0;

// Gunakan try-catch atau pengecekan manual untuk API
if (isset($API) && $API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    // Ambil Resource
    $res = $API->comm("/system/resource/print");
    if(isset($res[0])) {
        $first = $res[0];
        $uptime = $first['uptime'] ?? '-';
        $version = $first['version'] ?? '-';
        $model = $first['board-name'] ?? '-';
        $cpu_load = ($first['cpu-load'] ?? 0) . "%";
        $free_mem = $first['free-memory'] ?? 0;
        $mem_info = round($free_mem/1024/1024,0)." MB Free";
    }

    // Ambil Identity
    $ident = $API->comm("/system/identity/print");
    if(isset($ident[0])) {
        $identity = $ident[0]['name'];
    }
   
    // Hitung User Online
    $hs = $API->comm("/ip/hotspot/active/print", array("count-only"=> ""));
    $hotspot_active = $hs;
    
    $ppp = $API->comm("/ppp/active/print", array("count-only"=> ""));
    $pppoe_active = $ppp;

    $mikrotik_text = "Online";
    $mikrotik_color = "bg-primary";
    $API->disconnect();
}

?>
	
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
	<meta http-equiv="refresh" content="60; url=http://localhost:8080/rt_rw">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AwraNet Dashboard Billing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: var(--bs-body-bg); font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }
        .navbar {background-color: rgba(255,255,255, .5);backdrop-filter: blur(3px);}
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: .3s; }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.5rem; }
        .bg-gradient-success { background: linear-gradient(135deg, #198754 0%, #0f5132 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%); }
        .btn-theme-toggle { position: fixed; upper: 5px; right: 5px; width: 20px; height: 20px; border-radius: 50%; z-index: 1050; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: none; background-color: var(--bs-primary); color: white; font-size: 1.2rem; }
        
 /* BOTTOM NAV & FAB */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background-color: rgba(255,255,255, .5);backdrop-filter: blur(3px);
            display: flex; justify-content: space-around; align-items: center;
            padding: 10px 0; z-index: 1050;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.05);
        }
        .nav-item-mobile { text-align: center; color: #adb5bd; text-decoration: none; font-size: 0.7rem; flex: 1; }
        .nav-item-mobile i { font-size: 1.4rem; display: block; margin-bottom: 2px; }
        .nav-item-mobile.active { color: #0d6efd; font-weight: 700; }

        .fab-container { position: fixed; bottom: 35px; left: 50%; transform: translateX(-50%); z-index: 1060; }
        .fab-btn {
            width: 60px; height: 60px; border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0043a8);
            color: white; border: 4px solid #f8f9fa;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
            text-decoration: none; transition: transform 0.2s;
        }
        
        /* VOUCHER CARD SPECIAL */
        .card-voucher {
            background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%);
            color: white;
        }
        .card-voucher .stat-label { color: rgba(255,255,255,0.7); }
        .card-voucher .stat-value { color: white; }
        
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#"><i class="fas fa-wifi me-2"></i>AWRANET</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    
                     <div class="vr d-none d-lg-block mx-1"></div>

                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="list_hotspot.php"><i class="fas fa-users"></i>All User Hotspot</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="user_aktif.php"><i class="fas fa-wifi me-1"></i> User Aktif</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="halaman_backup.php"><i class="fas fa-database text-secondary"></i> Backup Data </a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="logout.php" onclick="return confirm('Yakin ingin keluar?')"><i class="fas fa-sign-out-alt"></i>Keluar</a></li>

                </ul>
            </div>
        </div>
    </nav>
    
     <button class="btn-theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon" id="theme-icon"></i></button>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="theme.js"></script>
    
     <div class="container" style="margin-top: 100px; padding-bottom: 50px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard Overview</h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                <i class="fas fa-calendar-alt me-1"></i> <?php echo $bulan_ini; ?>
            </span>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-stat h-100 bg-white border-start border-5 border-success position-relative overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small fw-bold text-uppercase mb-1">Sudah Bayar</p>
                                <h3 class="fw-bold text-success mb-0"><?php echo $user_lunas; ?></h3>
                            </div>
                            <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Pelanggan Lunas</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card card-stat h-100 bg-white border-start border-5 border-danger position-relative overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small fw-bold text-uppercase mb-1">Belum Bayar</p>
                                <h3 class="fw-bold text-danger mb-0"><?php echo $user_belum; ?></h3>
                            </div>
                            <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-circle">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Pelanggan Nunggak</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card card-stat h-100 bg-gradient-success text-white">
                    <div class="card-body">
                        <p class="text-white-50 small fw-bold text-uppercase mb-1">Dana Masuk</p>
                        <h5 class="fw-bold mb-0">Rp <?php echo number_format($uang_lunas, 0, ',', '.'); ?></h5>
                        <small class="text-white-50" style="font-size: 0.75rem;">Cashflow Aman</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card card-stat h-100 bg-gradient-warning text-dark">
                    <div class="card-body">
                        <p class="text-black-50 small fw-bold text-uppercase mb-1">Tunggakan</p>
                        <h5 class="fw-bold mb-0">Rp <?php echo number_format($uang_belum, 0, ',', '.'); ?></h5>
                        <small class="text-black-50" style="font-size: 0.75rem;">Segera Tagih!</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card card-stat bg-white shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                                <i class="fas fa-database"></i>
                            </div>
                            <div>
                                <h6 class="text-muted small mb-0">Total Database</h6>
                                <h4 class="fw-bold mb-0"><?php echo $total_pelanggan; ?> User</h4>
                            </div>
                        </div>
                        <hr class="my-2 border-light">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-success bg-opacity-10 text-success rounded-3 me-3">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div>
                                <h6 class="text-muted small mb-0">Estimasi Omset</h6>
                                <h5 class="fw-bold mb-0 text-success">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8">
                <div class="card card-stat bg-white shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-broadcast-tower text-warning me-2"></i>Status Koneksi Live</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row text-center align-items-center h-100">
                            <div class="col-6 border-end">
                                <h2 class="fw-bold text-primary mb-0"><?php echo $pppoe_active; ?></h2>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill">PPPoE Online</span>
                            </div>
                            <div class="col-6">
                                <h2 class="fw-bold text-warning mb-0"><?php echo $hotspot_active; ?></h2>
                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 rounded-pill">Hotspot Online</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            
            <div class="col-12 col-lg-8">
                <div class="card card-stat bg-white shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                        <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-chart-area me-2"></i>Traffic Monitor (Ether1)</h6>
                        <span class="badge bg-primary shadow-sm" id="speed-indicator">0 Mbps</span>
                    </div>
                    <div class="card-body">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card card-stat <?php echo $mikrotik_color; ?> text-white shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold mb-0"><?php echo $identity; ?></h5>
                                <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25">
                                    <i class="fas fa-circle me-1 small <?php echo ($mikrotik_text=='Online')?'text-success':'text-danger';?>"></i> <?php echo $mikrotik_text; ?>
                                </span>
                            </div>
                            <i class="fas <?php echo ($mikrotik_text=='Online')?'fa-server':'fa-unlink'; ?> fa-3x opacity-25"></i>
                        </div>
                        
                        <div class="bg-black bg-opacity-10 rounded-3 p-3">
                            <ul class="list-group list-group-flush info-list">
                                <li class="list-group-item d-flex justify-content-between border-bottom border-secondary border-opacity-25">
                                    <span class="opacity-75"><i class="fas fa-microchip me-2"></i>Model</span>
                                    <span class="fw-bold"><?php echo $model; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between border-bottom border-secondary border-opacity-25">
                                    <span class="opacity-75"><i class="fas fa-tachometer-alt me-2"></i>CPU Load</span>
                                    <span class="fw-bold"><?php echo $cpu_load; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="opacity-75"><i class="fas fa-clock me-2"></i>Uptime</span>
                                    <span class="fw-bold" style="font-size: 0.8rem;"><?php echo $uptime; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       <!-- FAB & NAV -->
    <div class="fab-container">
        <a href="tambah_user.php" class="fab-btn"><i class="fas fa-plus"></i></a>
    </div>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item-mobile active"><i class="fas fa-home"></i> Home</a>
        <a href="billing.php" class="nav-item-mobile"><i class="fas fa-file-invoice-dollar"></i> Tagihan</a>
        <div style="width: 50px;"></div>
        <a href="voucher.php" class="nav-item-mobile"><i class="fas fa-ticket-alt"></i> Voucher</a>
        <a href="semua_pelanggan.php" class="nav-item-mobile"><i class="fas fa-users"></i> Users</a>
    </nav>
    </div>

        <script>
        const ctx = document.getElementById('trafficChart').getContext('2d');
        
        // Konfigurasi Awal Chart
        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], // Waktu
                datasets: [
                    { label: 'Download (RX)', borderColor: 'rgb(25, 135, 84)', backgroundColor: 'rgba(25, 135, 84, 0.1)', data: [], fill: true, tension: 0.4 },
                    { label: 'Upload (TX)', borderColor: 'rgb(13, 110, 253)', backgroundColor: 'rgba(13, 110, 253, 0.1)', data: [], fill: true, tension: 0.4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                scales: {
                    x: { display: false },
                    y: { beginAtZero: true, ticks: { callback: function(value) { return (value / 1000000).toFixed(1) + ' Mbps'; } } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });

        function updateTraffic() {
            fetch('traffic_data.php')
                .then(response => response.json())
                .then(data => {
                    const rx = data.rx; const tx = data.tx;
                    const totalSpeed = ((rx + tx) / 1000000).toFixed(2);
                    document.getElementById('speed-indicator').innerText = totalSpeed + " Mbps";

                    const now = new Date().toLocaleTimeString();
                    if (trafficChart.data.labels.length > 20) {
                        trafficChart.data.labels.shift(); trafficChart.data.datasets[0].data.shift(); trafficChart.data.datasets[1].data.shift();
                    }
                    trafficChart.data.labels.push(now);
                    trafficChart.data.datasets[0].data.push(rx);
                    trafficChart.data.datasets[1].data.push(tx);
                    trafficChart.update();
                })
                .catch(err => console.error("Gagal ambil data traffic", err));
        }
        setInterval(updateTraffic, 3000);
    </script>
</body>
</html>