<?php
include 'koneksi_mikrotik.php';

// --- LOGIKA KICK USER (JIKA TOMBOL DITEKAN) ---
if (isset($_GET['kick_id']) && isset($_GET['type'])) {
    $id = $_GET['kick_id'];
    $type = $_GET['type'];
    
    if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
        if ($type == 'pppoe') {
            $API->comm("/ppp/active/remove", array(".id" => $id));
        } elseif ($type == 'hotspot') {
            $API->comm("/ip/hotspot/active/remove", array(".id" => $id));
        }
        $API->disconnect();
        // Refresh halaman agar bersih URL-nya
        header("Location: user_aktif.php");
    }
}

// --- AMBIL DATA LIVE ---
$pppoe_list = [];
$hotspot_list = [];
$mikrotik_status = "Offline";

if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    // 1. Ambil PPPoE Active
    $pppoe_list = $API->comm("/ppp/active/print");
    
    // 2. Ambil Hotspot Active
    $hotspot_list = $API->comm("/ip/hotspot/active/print");
    
    $mikrotik_status = "Online";
    $API->disconnect();
}

// Fungsi Format Bytes (Agar jadi KB/MB/GB)
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor User Aktif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .nav-pills .nav-link.active { background-color: #0d6efd; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.3); }
        .nav-pills .nav-link { color: #6c757d; font-weight: 600; border-radius: 10px; }
        .card-user { border: none; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 10px; }
        .uptime-badge { font-size: 0.75rem; background: #e9ecef; color: #495057; padding: 3px 8px; border-radius: 4px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="btn btn-outline-secondary btn-sm rounded-circle" href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span class="navbar-brand mb-0 h1 fs-6 fw-bold mx-auto">Monitor User Aktif</span>
            <span class="badge <?php echo ($mikrotik_status=='Online')?'bg-success':'bg-danger';?> rounded-pill">
                <?php echo $mikrotik_status; ?>
            </span>
        </div>
    </nav>

    <div class="container" style="margin-top: 80px; padding-bottom: 50px;">

        <ul class="nav nav-pills nav-fill mb-4 p-1 bg-white rounded-3 shadow-sm" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-pppoe-tab" data-bs-toggle="pill" data-bs-target="#pills-pppoe" type="button" role="tab">
                    <i class="fas fa-network-wired me-1"></i> PPPoE (<?php echo count($pppoe_list); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-hotspot-tab" data-bs-toggle="pill" data-bs-target="#pills-hotspot" type="button" role="tab">
                    <i class="fas fa-wifi me-1"></i> Hotspot (<?php echo count($hotspot_list); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="pills-pppoe" role="tabpanel">
                <?php if(count($pppoe_list) > 0) { ?>
                    <?php foreach ($pppoe_list as $user) { ?>
                        <div class="card card-user bg-white">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-primary mb-1">
                                            <i class="fas fa-user-circle"></i> <?php echo $user['name']; ?>
                                        </h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-map-marker-alt text-warning"></i> <?php echo $user['address']; ?> 
                                            <span class="mx-1">|</span> 
                                            <i class="fas fa-clock text-info"></i> <?php echo $user['uptime']; ?>
                                        </div>
                                        <small class="text-secondary" style="font-size: 0.75rem;">
                                            Caller ID: <?php echo $user['caller-id']; ?>
                                        </small>
                                    </div>
                                    <a href="user_aktif.php?kick_id=<?php echo $user['.id']; ?>&type=pppoe" 
                                       class="btn btn-danger btn-sm rounded-circle shadow-sm"
                                       onclick="return confirm('Disconnect user <?php echo $user['name']; ?>?')">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-plug fa-3x mb-3 opacity-25"></i>
                        <p>Tidak ada user PPPoE aktif.</p>
                    </div>
                <?php } ?>
            </div>

            <div class="tab-pane fade" id="pills-hotspot" role="tabpanel">
                <?php if(count($hotspot_list) > 0) { ?>
                    <?php foreach ($hotspot_list as $h) { 
                        // Hitung Traffic (Bytes In + Out)
                        $traffic = formatBytes($h['bytes-in'] + $h['bytes-out']);
                    ?>
                        <div class="card card-user bg-white">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-warning mb-1">
                                            <i class="fas fa-wifi"></i> <?php echo $h['user']; ?>
                                        </h6>
                                        <div class="text-muted small">
                                            <span class="badge bg-light text-dark border"><?php echo $h['address']; ?></span>
                                            <span class="mx-1"></span>
                                            <i class="fas fa-exchange-alt text-success"></i> <?php echo $traffic; ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="uptime-badge"><i class="fas fa-clock"></i> <?php echo $h['uptime']; ?></span>
                                        </div>
                                    </div>
                                    <a href="user_aktif.php?kick_id=<?php echo $h['.id']; ?>&type=hotspot" 
                                       class="btn btn-outline-danger btn-sm rounded-circle"
                                       onclick="return confirm('Disconnect hotspot user <?php echo $h['user']; ?>?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-wifi fa-3x mb-3 opacity-25"></i>
                        <p>Tidak ada user Hotspot aktif.</p>
                    </div>
                <?php } ?>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>