<?php
include 'koneksi_mikrotik.php';

// Ambil Daftar Profile dari MikroTik
$ppp_profiles = [];
$hs_profiles = [];

if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    $ppp_profiles = $API->comm("/ppp/profile/print");
    $hs_profiles  = $API->comm("/ip/hotspot/user/profile/print");
    $API->disconnect();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: var(--bs-body-bg); color: var(--bs-body-color); }
        .form-label { font-size: 0.85rem; font-weight: bold; color: #6c757d; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-light shadow-sm sticky-top mb-4">
        <div class="container">
            <a class="btn btn-secondary btn-sm rounded-circle" href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span class="navbar-brand mb-0 h1 fs-6 fw-bold mx-auto">Tambah Pelanggan Pro (Custom)</span>
        </div>
    </nav>

    <div class="container pb-5">
        
        <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded shadow-sm" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold" id="tab-pppoe" data-bs-toggle="pill" data-bs-target="#content-pppoe" type="button">
                    <i class="fas fa-network-wired me-2"></i>PPPoE Pro
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-warning" id="tab-hotspot" data-bs-toggle="pill" data-bs-target="#content-hotspot" type="button">
                    <i class="fas fa-wifi me-2"></i>Hotspot Pro
                </button>
            </li>
        </ul>

        <div class="tab-content" id="tabContent">
            
            <div class="tab-pane fade show active" id="content-pppoe">
                <div class="card">
                    <div class="card-body">
                        <form action="proses_tambah_custom.php" method="POST">
                            <input type="hidden" name="service" value="pppoe">
                            
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="fas fa-user"></i> Data Pelanggan</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No. WhatsApp</label>
                                    <input type="number" name="no_wa" class="form-control" placeholder="62xxx">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Alamat</label>
                                    <input type="text" name="alamat" class="form-control">
                                </div>
                            </div>

                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2 mt-2"><i class="fas fa-cogs"></i> Setting Koneksi</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username PPPoE</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="text" name="password" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-danger">Pilih Profile (MikroTik)</label>
                                    <select name="profile" class="form-select" required>
                                        <?php foreach ($ppp_profiles as $p) { echo "<option value='".$p['name']."'>".$p['name']."</option>"; } ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-success">Harga Langganan (Rp)</label>
                                    <input type="number" name="harga" class="form-control" required placeholder="Contoh: 150000">
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">*Wajib diisi agar muncul di Billing.</small>
                                </div>
                            </div>

                            <div class="accordion mb-3" id="accPPPoE">
                                <div class="accordion-item border-0 bg-light">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2 bg-light text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#advPPPoE">
                                            <i class="fas fa-sliders-h me-2"></i> Opsi Lanjutan (Override Limit & IP)
                                        </button>
                                    </h2>
                                    <div id="advPPPoE" class="accordion-collapse collapse" data-bs-parent="#accPPPoE">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label class="form-label">Rate Limit (Speed)</label>
                                                    <input type="text" name="rate_limit" class="form-control form-control-sm" placeholder="Ex: 10M/10M">
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label">Kuota (Bytes)</label>
                                                    <input type="text" name="limit_bytes" class="form-control form-control-sm" placeholder="Ex: 50G">
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label">Remote Address (IP Statis)</label>
                                                    <input type="text" name="remote_address" class="form-control form-control-sm" placeholder="10.5.50.x">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">SIMPAN PPPOE & DATABASE</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="content-hotspot">
                <div class="card">
                    <div class="card-body">
                        <form action="proses_tambah_custom.php" method="POST">
                            <input type="hidden" name="service" value="hotspot">

                            <h6 class="text-warning fw-bold mb-3 border-bottom pb-2"><i class="fas fa-user"></i> Data Pelanggan</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">No. WA</label><input type="number" name="no_wa" class="form-control" placeholder="62xxx"></div>
                                <div class="col-12 mb-3"><label class="form-label">Alamat</label><input type="text" name="alamat" class="form-control"></div>
                            </div>

                            <h6 class="text-warning fw-bold mb-3 border-bottom pb-2 mt-2"><i class="fas fa-cogs"></i> Setting Koneksi</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Password</label><input type="text" name="password" class="form-control" required></div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profile</label>
                                    <select name="profile" class="form-select" required>
                                        <?php foreach ($hs_profiles as $h) { echo "<option value='".$h['name']."'>".$h['name']."</option>"; } ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-success">Harga (Rp)</label>
                                    <input type="number" name="harga" class="form-control" required placeholder="0">
                                </div>
                            </div>

                            <div class="accordion mb-3" id="accHS">
                                <div class="accordion-item border-0 bg-light">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2 bg-light text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#advHS">
                                            <i class="fas fa-sliders-h me-2"></i> Opsi Lanjutan
                                        </button>
                                    </h2>
                                    <div id="advHS" class="accordion-collapse collapse" data-bs-parent="#accHS">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label class="form-label">Waktu (Uptime)</label>
                                                    <input type="text" name="limit_uptime" class="form-control form-control-sm" placeholder="Ex: 30d">
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label">Kuota (Bytes)</label>
                                                    <input type="text" name="limit_bytes" class="form-control form-control-sm" placeholder="Ex: 5G">
                                                </div>
                                                <div class="col-12 mb-2">
                                                    <label class="form-label">Mac Address (Binding)</label>
                                                    <input type="text" name="mac_address" class="form-control form-control-sm" placeholder="AA:BB:CC:DD:EE:FF">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning text-white w-100 fw-bold py-2">SIMPAN HOTSPOT & DATABASE</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="theme.js"></script>
</body>
</html>