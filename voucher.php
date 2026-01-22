<?php
session_start();
include 'koneksi.php';          
include 'koneksi_mikrotik.php'; 

// --- 1. LOGIKA FILTER BULAN ---
if (isset($_GET['filter_bulan'])) {
    $input_bulan = $_GET['filter_bulan'];
    $bulan_sql = date('Y-m', strtotime($input_bulan));
    $label_bulan = date('F Y', strtotime($input_bulan));
} else {
    $input_bulan = date('Y-m');
    $bulan_sql = date('Y-m');
    $label_bulan = date('F Y');
}

function rp($angka){ return "Rp " . number_format($angka, 0, ',', '.'); }

// --- 2. HITUNG PENDAPATAN (QUERY) ---
// Kita taruh query ini di atas agar datanya siap ditampilkan di Header
$q_sum = mysqli_query($koneksi, "
    SELECT SUM(qty) as total_pcs, SUM(total_nominal) as total_uang 
    FROM riwayat_voucher 
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_sql'
");
$d_sum = mysqli_fetch_assoc($q_sum);
$total_pcs = $d_sum['total_pcs'] ?? 0;
$total_uang = $d_sum['total_uang'] ?? 0;

// --- 3. AMBIL PROFILE MIKROTIK ---
$profiles = [];
$status_mikrotik = false;
if (isset($API) && $API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    $profiles = $API->comm("/ip/hotspot/user/profile/print");
    $status_mikrotik = true;
    $API->disconnect();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Voucher Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding-bottom: 90px; }
        
        /* Sticky Header Control */
        .header-control {
            background: white; padding: 15px; position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom: 1px solid #eee;
        }

        /* Stats Cards */
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; position: relative; }
        .bg-gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); }
        .bg-gradient-light { background: white; border: 1px solid #eee; }

        /* Form Generator */
        .card-form { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-top: 20px; background: white; }
        .form-label { font-size: 0.8rem; font-weight: 700; color: #6c757d; text-transform: uppercase; }

        /* History List */
        .history-item { 
            background: white; border-radius: 12px; padding: 12px; margin-bottom: 8px; 
            border-left: 4px solid #dee2e6; transition: .2s; 
        }
        .history-item.recent { border-left-color: #198754; } /* Hijau untuk terbaru */
        
        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: white; border-top: 1px solid #eee; display: flex; justify-content: space-around; padding: 10px 0; z-index: 1050; }
        .nav-item-mobile { text-align: center; color: #adb5bd; text-decoration: none; font-size: 0.7rem; flex: 1; }
        .nav-item-mobile i { font-size: 1.4rem; display: block; margin-bottom: 2px; }
        .nav-item-mobile.active { color: #0d6efd; font-weight: 700; }
    </style>
</head>
<body>

    <!-- 1. HEADER & FILTER TANGGAL (STICKY) -->
    <div class="header-control">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Laporan Penjualan</h6>
                <small class="text-muted"><?php echo $label_bulan; ?></small>
            </div>
            <form action="" method="GET">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-0"><i class="far fa-calendar-alt"></i></span>
                    <input type="month" name="filter_bulan" class="form-control form-control-sm border-0 bg-light fw-bold" 
                           value="<?php echo $input_bulan; ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <div class="container mt-3">

        <!-- 2. REKAP PENDAPATAN (POSISI DI ATAS) -->
        <div class="row g-3 mb-4">
            <!-- Kartu Uang -->
            <div class="col-7">
                <div class="card card-stat bg-gradient-primary text-white h-100">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <small class="text-white-50 fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Total Pendapatan</small>
                        <h4 class="fw-bold mb-0"><?php echo rp($total_uang); ?></h4>
                    </div>
                    <i class="fas fa-wallet position-absolute text-white opacity-25" style="font-size: 3rem; right: -10px; bottom: -10px;"></i>
                </div>
            </div>
            <!-- Kartu Jumlah -->
            <div class="col-5">
                <div class="card card-stat bg-gradient-light h-100">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <small class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Terjual</small>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $total_pcs; ?> <span class="fs-6 text-muted fw-normal">Pcs</span></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. FORM GENERATOR (COLLAPSIBLE / CARD) -->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-secondary mb-0 text-uppercase small ls-1"><i class="fas fa-plus-circle me-1"></i> Generator Voucher</h6>
            <?php if($status_mikrotik): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">MikroTik ON</span>
            <?php else: ?>
                <span class="badge bg-danger rounded-pill px-2">MikroTik OFF</span>
            <?php endif; ?>
        </div>

        <div class="card card-form mb-4">
            <div class="card-body">
                <form action="proses_voucher.php" method="POST">
                    
                    <!-- Profile & Qty -->
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label">Profile</label>
                            <select name="profile" class="form-select form-select-sm bg-light" required>
                                <?php if(empty($profiles)) echo "<option value=''>Data Kosong</option>"; ?>
                                <?php foreach ($profiles as $p) { echo "<option value='".$p['name']."'>".$p['name']."</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Jumlah</label>
                            <input type="number" name="qty" class="form-control form-control-sm bg-light" value="10" min="1" max="200" required>
                        </div>
                    </div>

                    <!-- Limit Time -->
                    <div class="mb-3">
                        <label class="form-label text-danger">Limit Waktu (Uptime)</label>
                        <select name="limit_uptime" class="form-select form-select-sm border-danger text-danger fw-bold bg-danger bg-opacity-10">
                            <option value="">-- UNLIMITED / IKUT PROFIL --</option>
                            <option value="2h">2 Jam</option>
                            <option value="6h">6 Jam</option>
                            <option value="1d">1 Hari</option>
                            <option value="3d">3 Hari</option>
                            <option value="7d">1 Minggu</option>
                            <option value="4w2d">30 Hari</option>
                        </select>
                    </div>

                    <!-- Label & Harga -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Label Cetak</label>
                            <input type="text" name="durasi" class="form-control form-control-sm" placeholder="Cth: 3 Jam" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Harga Jual</label>
                            <input type="number" name="harga" class="form-control form-control-sm" placeholder="3000" required>
                        </div>
                    </div>

                    <!-- Config Code -->
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-4">
                            <label class="form-label">Panjang</label>
                            <select name="length" class="form-select form-select-sm text-center">
                            	 <option value="3">3</option>
                                <option value="4" selected>4</option>
                                 <option value="5">5</option>
                                  <option value="6">6</option>
                                <option value="8">8</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Prefix</label>
                            <input type="text" name="prefix" class="form-control form-control-sm text-uppercase" placeholder="VC-" maxlength="3">
                        </div>
                        <div class="col-4">
                            <div class="form-check mb-1">
                                <input type="checkbox" class="form-check-input" name="user_same_pass" value="1" id="checkPass" checked>
                                <label class="form-check-label small" for="checkPass" style="font-size: 0.65rem;">User=Pass</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                        GENERATE & PRINT <i class="fas fa-print ms-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- 4. RIWAYAT (DI BAWAH) -->
        <h6 class="fw-bold text-secondary mb-3 text-uppercase small ls-1"><i class="fas fa-history me-1"></i> Log Riwayat</h6>
        
        <div class="history-list">
            <?php
            $q_list = mysqli_query($koneksi, "
                SELECT * FROM riwayat_voucher 
                WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_sql' 
                ORDER BY id DESC LIMIT 20
            ");

            if(mysqli_num_rows($q_list) == 0){
                echo "<div class='text-center py-4 text-muted small bg-white rounded shadow-sm'>Belum ada data bulan ini.</div>";
            }

            while($r = mysqli_fetch_array($q_list)) {
                $tgl = date('d/m H:i', strtotime($r['tanggal']));
                // Highlight jika baru dibuat hari ini
                $is_today = (date('Y-m-d') == date('Y-m-d', strtotime($r['tanggal']))) ? 'recent' : '';
            ?>
            <div class="history-item <?php echo $is_today; ?> d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">
                        <?php echo $r['qty']; ?> Pcs <span class="text-muted fw-normal small">x <?php echo $r['profile']; ?></span>
                    </div>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <i class="far fa-clock me-1"></i> <?php echo $tgl; ?> &bull; <?php echo $r['admin']; ?>
                    </small>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-success" style="font-size: 0.9rem;"><?php echo rp($r['total_nominal']); ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">@ <?php echo rp($r['harga_satuan']); ?></small>
                </div>
            </div>
            <?php } ?>
        </div>

    </div>

    <!-- BOTTOM NAV -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item-mobile">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="billing.php" class="nav-item-mobile">
            <i class="fas fa-file-invoice-dollar"></i> Tagihan
        </a>
        <div style="width: 20px;"></div> <!-- Space untuk tombol FAB jika ada -->
        <a href="voucher.php" class="nav-item-mobile active">
            <i class="fas fa-ticket-alt"></i> Voucher
        </a>
        <a href="semua_pelanggan.php" class="nav-item-mobile">
            <i class="fas fa-users"></i> Users
        </a>
    </nav>

    <!-- FAB BUTTON KEMBALI KE ADD USER -->
    <div style="position: fixed; bottom: 35px; left: 50%; transform: translateX(-50%); z-index: 1060;">
        <a href="tambah_user.php" style="width: 60px; height: 60px; border-radius: 50%; background: #6c757d; color: white; border: 4px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            <i class="fas fa-plus"></i>
        </a>
    </div>

</body>
</html>