<?php
session_start();
include 'koneksi.php';

// Filter Bulan (Default Bulan Ini)
if (isset($_GET['filter_bulan'])) {
    $input_bulan = $_GET['filter_bulan'];
    $bulan_sql = date('Y-m', strtotime($input_bulan));
} else {
    $input_bulan = date('Y-m');
    $bulan_sql = date('Y-m');
}

// FORMAT RUPIAH
function rp($angka){ return "Rp " . number_format($angka, 0, ',', '.'); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Voucher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 80px; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .icon-box { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .list-group-item { border: none; margin-bottom: 5px; border-radius: 8px !important; }
        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: white; border-top: 1px solid #eee; display: flex; justify-content: space-around; padding: 10px 0; z-index: 1050; }
        .nav-item-mobile { text-align: center; color: #adb5bd; text-decoration: none; font-size: 0.7rem; flex: 1; }
        .nav-item-mobile i { font-size: 1.4rem; display: block; margin-bottom: 2px; }
        .nav-item-mobile.active { color: #0d6efd; font-weight: 700; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="bg-white shadow-sm sticky-top p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-chart-line me-2"></i>Laporan Voucher</h5>
            <a href="voucher.php" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-plus"></i> Buat Baru</a>
        </div>
        <form action="" method="GET">
            <input type="month" name="filter_bulan" class="form-control form-control-sm fw-bold bg-light border-0" 
                   value="<?php echo $input_bulan; ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="container">
        
        <?php
        // --- 1. HITUNG TOTAL BULAN INI ---
        $q_sum = mysqli_query($koneksi, "
            SELECT 
                SUM(qty) as total_pcs, 
                SUM(total_nominal) as total_uang 
            FROM riwayat_voucher 
            WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_sql'
        ");
        $d_sum = mysqli_fetch_assoc($q_sum);
        $total_pcs = $d_sum['total_pcs'] ?? 0;
        $total_uang = $d_sum['total_uang'] ?? 0;
        ?>

        <!-- KARTU STATISTIK -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="card card-stat bg-primary text-white h-100">
                    <div class="card-body p-3">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.7rem;">Total Pendapatan</small>
                        <h5 class="fw-bold mb-0 mt-1"><?php echo rp($total_uang); ?></h5>
                        <small class="text-white-50">Bulan Ini</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card card-stat bg-white h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Voucher Terjual</small>
                        <h4 class="fw-bold mb-0 mt-1 text-dark"><?php echo $total_pcs; ?> <span class="fs-6 text-muted">Pcs</span></h4>
                        <small class="text-muted">Generated</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIWAYAT TRANSAKSI -->
        <h6 class="fw-bold text-muted mb-3 small text-uppercase">Riwayat Pembuatan (Log)</h6>
        
        <div class="list-group">
            <?php
            $q_list = mysqli_query($koneksi, "
                SELECT * FROM riwayat_voucher 
                WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_sql' 
                ORDER BY tanggal DESC
            ");

            if(mysqli_num_rows($q_list) == 0){
                echo "<div class='text-center py-5 text-muted'><i class='fas fa-box-open fa-3x mb-2 opacity-50'></i><br>Belum ada data bulan ini.</div>";
            }

            while($r = mysqli_fetch_array($q_list)) {
                $tgl = date('d M H:i', strtotime($r['tanggal']));
            ?>
            <div class="list-group-item shadow-sm p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-light text-primary me-3">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-0">
                            <?php echo $r['qty']; ?> Pcs <span class="text-muted fw-normal">x Profile <?php echo $r['profile']; ?></span>
                        </div>
                        <small class="text-muted">
                            <i class="far fa-clock me-1"></i> <?php echo $tgl; ?> &bull; Oleh: <?php echo $r['admin']; ?>
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-success"><?php echo rp($r['total_nominal']); ?></div>
                    <small class="text-muted" style="font-size: 0.7rem;">@ <?php echo rp($r['harga_satuan']); ?></small>
                </div>
            </div>
            <?php } ?>
        </div>

    </div>

    <!-- BOTTOM NAV -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item-mobile"><i class="fas fa-home"></i> Home</a>
        <a href="billing.php" class="nav-item-mobile"><i class="fas fa-file-invoice-dollar"></i> Tagihan</a>
        <div style="width: 50px;"></div> <!-- Spacer FAB -->
        <a href="rekap_voucher.php" class="nav-item-mobile active"><i class="fas fa-chart-bar"></i> Laporan</a>
        <a href="semua_pelanggan.php" class="nav-item-mobile"><i class="fas fa-users"></i> Users</a>
    </nav>

    <!-- FAB -->
    <div style="position: fixed; bottom: 35px; left: 50%; transform: translateX(-50%); z-index: 1060;">
        <a href="voucher.php" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #0043a8); color: white; border: 4px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            <i class="fas fa-plus"></i>
        </a>
    </div>

</body>
</html>