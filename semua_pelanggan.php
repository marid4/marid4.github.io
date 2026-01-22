<?php
include 'koneksi.php';

// FUNGSI BUTTON ISOLIR
// Jika tombol ditekan, arahkan ke script proses
if(isset($_POST['run_auto_isolir'])){
    header("Location: proses_isolir.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Semua Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .card-user { transition: transform 0.2s; border: none; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card-user:hover { transform: translateY(-3px); }
        .status-aktif { border-left: 5px solid #198754; }
        .status-isolir { border-left: 5px solid #dc3545; background-color: #fff5f5; }
        .badge-tipe { position: absolute; top: 10px; right: 10px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white shadow-sm sticky-top mb-4">
        <div class="container">
            <a class="btn btn-outline-secondary btn-sm rounded-circle" href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span class="navbar-brand mb-0 h1 fs-6 fw-bold mx-auto">Database Pelanggan</span>
        </div>
    </nav>

    <div class="container pb-5">
        
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <h5 class="fw-bold text-primary mb-0"><i class="fas fa-users"></i> Semua User</h5>
                        <small class="text-muted">Total database pelanggan terdaftar</small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <form method="POST" onsubmit="return confirm('Yakin jalankan Auto Isolir? Semua yang telat bayar akan diputus koneksinya!');">
                            <button type="submit" name="run_auto_isolir" class="btn btn-danger fw-bold">
                                <i class="fas fa-user-lock me-1"></i> Jalankan Auto Isolir
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="mt-3">
                    <input type="text" id="searchInput" class="form-control bg-light" placeholder="Cari nama, username, atau alamat..." onkeyup="filterUser()">
                </div>
            </div>
        </div>

        <div class="row" id="userContainer">
            <?php
            // Ambil data pelanggan + Info Paket
            $query = mysqli_query($koneksi, "SELECT p.*, pk.nama_paket, pk.tipe, pk.harga 
                                             FROM pelanggan p 
                                             JOIN paket pk ON p.id_paket = pk.id_paket 
                                             ORDER BY p.status ASC, p.nama ASC");
            
            while($row = mysqli_fetch_array($query)) {
                $status_class = ($row['status'] == 'aktif') ? 'status-aktif' : 'status-isolir';
                $badge_color  = ($row['status'] == 'aktif') ? 'bg-success' : 'bg-danger';
                $tipe_badge   = ($row['tipe'] == 'pppoe') ? 'bg-primary' : 'bg-warning text-dark';
                $tgl_jt       = $row['tgl_jatuh_tempo']; // Tanggal (1-31)
            ?>
            <div class="col-12 col-md-6 col-lg-4 mb-3 item-user" data-name="<?php echo strtolower($row['nama'] . ' ' . $row['username_pppoe']); ?>">
                <div class="card card-user h-100 <?php echo $status_class; ?>">
                    <div class="card-body position-relative">
                        <span class="badge <?php echo $tipe_badge; ?> badge-tipe"><?php echo strtoupper($row['tipe']); ?></span>
                        
                        <h6 class="fw-bold mb-1"><?php echo $row['nama']; ?></h6>
                        <div class="small text-muted mb-2"><i class="fas fa-user-circle"></i> <?php echo $row['username_pppoe']; ?></div>
                        
                        <div class="d-flex justify-content-between small mb-2 border-bottom pb-2">
                            <span>Paket:</span>
                            <span class="fw-bold"><?php echo $row['nama_paket']; ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Jatuh Tempo:</div>
                                <div class="fw-bold text-dark">Tgl <?php echo $tgl_jt; ?></div>
                            </div>
                            <span class="badge <?php echo $badge_color; ?> rounded-pill px-3">
                                <?php echo strtoupper($row['status']); ?>
                                	
                                <div class="mt-3 border-top pt-2 d-flex justify-content-between">
   <a href="detail_pelanggan.php?id=<?php echo $row['id_pelanggan']; ?>" class="btn btn-sm btn-primary text-white">Detail</a>

    <?php if($row['status'] == 'aktif') { ?>
        
        <a href="proses_manual_isolir.php?id=<?php echo $row['id_pelanggan']; ?>" 
           class="btn btn-sm btn-outline-danger fw-bold"
           onclick="return confirm('Yakin ingin MENGISOLIR pelanggan <?php echo $row['nama']; ?>?')">
           <i class="fas fa-lock"></i> Isolir
        </a>

    <?php } else { ?>
        
        <a href="proses_buka_isolir.php?id=<?php echo $row['id_pelanggan']; ?>" 
           class="btn btn-sm btn-success fw-bold"
           onclick="return confirm('Buka Isolir pelanggan ini?')">
           <i class="fas fa-lock-open"></i> Buka
        </a>

    <?php } ?>
</div>
                                
                            </span>
                        </div>

                        <?php if($row['status'] == 'isolir') { ?>
                            <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small text-center">
                                <i class="fas fa-ban"></i> Internet Diblokir
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

    </div>
    
    <script>
        function filterUser() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let items = document.getElementsByClassName('item-user');
            for (let i = 0; i < items.length; i++) {
                let text = items[i].getAttribute('data-name');
                items[i].style.display = text.includes(input) ? "" : "none";
            }
        }
    </script>
</body>
</html>