<?php
include 'koneksi.php';

// --- LOGIKA SINKRONISASI TANGGAL/BULAN ---

// 1. Ambil input dari URL (jika ada filter bulan yang dipilih user)
if (isset($_GET['filter_bulan'])) {
    $input_bulan = $_GET['filter_bulan'];
    // Ubah ke format database (F Y -> January 2026)
    $bulan_tagihan = date('F Y', strtotime($input_bulan));
} else {
    // Default ke bulan saat ini jika tidak ada filter
    $input_bulan = date('Y-m');
    $bulan_tagihan = date('F Y');
}

/// FUNGSI KHUSUS: Format Nomor WA (Anti Gagal)
function formatWA($nomor) {
    // 1. Hapus semua karakter selain angka (spasi, strip, +, dll)
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    // 2. Cek awalan nomor
    if (substr($nomor, 0, 1) == '0') {
        // Jika berawalan 0 (Cth: 0812...), ganti 0 jadi 62
        $nomor = '62' . substr($nomor, 1);
    } 
    elseif (substr($nomor, 0, 1) == '8') {
        // Jika berawalan 8 (Cth: 812... lupa nulis 0), tambahkan 62 di depan
        $nomor = '62' . $nomor;
    }
    // Jika sudah berawalan 62, biarkan saja.

    return $nomor;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Billing <?php echo $bulan_tagihan; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }
        .navbar-sticky { position: sticky; top: 0; z-index: 1000; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn-filter { border-radius: 20px; font-size: 0.85rem; font-weight: 600; padding: 5px 15px; border: 1px solid #dee2e6; color: #6c757d; background: white; }
        .btn-filter.active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .card-bill { border: none; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s; margin-bottom: 15px; }
        .card-bill:active { transform: scale(0.98); }
        .card-lunas { border-left: 5px solid #198754; }
        .card-belum { border-left: 5px solid #dc3545; }
        .price-tag { font-size: 1.2rem; font-weight: 800; color: #212529; }
        .customer-name { font-size: 1rem; font-weight: 700; color: #0d6efd; }
    </style>
</head>
<body>

    <div class="navbar-sticky pb-3 pt-2">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="text-decoration-none text-dark fw-bold" href="index.php">
                    <i class="fas fa-arrow-left me-2"></i> Dashboard
                </a>
                
                <form action="" method="GET" class="d-flex align-items-center">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
                        <input type="month" name="filter_bulan" class="form-control fw-bold" 
                               value="<?php echo $input_bulan; ?>" 
                               onchange="this.form.submit()">
                    </div>
                </form>
            </div>
            
            <div class="row g-2">
                <div class="col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 bg-light" placeholder="Cari nama pelanggan..." onkeyup="filterList()">
                    </div>
                </div>
                <div class="col-12 d-flex gap-2 overflow-auto pb-1">
                    <button class="btn btn-filter active" onclick="filterStatus('all', this)">Semua</button>
                    <button class="btn btn-filter" onclick="filterStatus('belum', this)">Belum Bayar</button>
                    <button class="btn btn-filter" onclick="filterStatus('lunas', this)">Lunas</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-3">
        
        <div class="alert alert-info py-2 small mb-3 border-0 shadow-sm d-flex justify-content-between align-items-center">
            <span><i class="fas fa-info-circle"></i> Data Tagihan: <strong><?php echo $bulan_tagihan; ?></strong></span>
        </div>

        <div class="row" id="billContainer">
            <?php
            // --- QUERY UTAMA (INI YANG KEMUNGKINAN HILANG SEBELUMNYA) ---
            $query = mysqli_query($koneksi, "
                SELECT p.id_pelanggan, p.nama, p.no_wa, p.alamat, 
                       pk.nama_paket, pk.harga, 
                       t.id_tagihan, t.tgl_bayar, t.id_tagihan 
                FROM pelanggan p
                JOIN paket pk ON p.id_paket = pk.id_paket
                LEFT JOIN tagihan t ON p.id_pelanggan = t.id_pelanggan AND t.bulan = '$bulan_tagihan'
                WHERE p.status = 'aktif'
                ORDER BY p.nama ASC
            ");

            while($row = mysqli_fetch_array($query)) {
                $is_lunas = ($row['id_tagihan'] != NULL);
                $status_class = $is_lunas ? 'lunas' : 'belum';
                $no_wa = formatWA($row['no_wa']);
                
                // FORMAT PESAN TAGIHAN (BELUM BAYAR)
                $pesan_tagih  = "*TAGIHAN INTERNET - $bulan_tagihan*\n";
                $pesan_tagih .= "--------------------------------\n";
                $pesan_tagih .= "Kepada Yth,\n";
                $pesan_tagih .= "*{$row['nama']}*\n\n";
                $pesan_tagih .= "Detail Tagihan:\n";
                $pesan_tagih .= "Paket     : {$row['nama_paket']}\n";
                $pesan_tagih .= "Periode   : $bulan_tagihan\n";
                $pesan_tagih .= "*Total     : Rp ".number_format($row['harga'], 0, ',', '.')."*\n\n";
                $pesan_tagih .= "Mohon untuk segera melakukan pembayaran agar layanan tetap aktif. Terima kasih 🙏";
                
                $link_wa_tagih = "https://wa.me/$no_wa?text=".urlencode($pesan_tagih);

                // FORMAT PESAN LUNAS (ALA MITRA BUKALAPAK)
                $tgl_lunas_indo = ($row['tgl_bayar']) ? date('d/m/Y H:i', strtotime($row['tgl_bayar'])) : date('d/m/Y');
                
                $pesan_lunas  = "*============@w�@===========*\n";
                $pesan_lunas .=                          "AWRANET\n"; // Ganti nama ISP Anda
                $pesan_lunas .= "==============================\n";
                $pesan_lunas .= "Tanggal       : $tgl_lunas_indo\n";
                $pesan_lunas .= "No. Ref       : #INV-{$row['id_tagihan']}\n";
                $pesan_lunas .= "Pelanggan     : {$row['nama']}\n\n";
                $pesan_lunas .= "*DETAIL PEMBAYARAN*\n";
                $pesan_lunas .= "Produk        : {$row['nama_paket']}\n";
                $pesan_lunas .= "Periode       : $bulan_tagihan\n";
                $pesan_lunas .= "--------------------------------\n";
                $pesan_lunas .= "Nominal       : Rp ".number_format($row['harga'], 0, ',', '.')."\n";
                $pesan_lunas .= "Biaya Admin   : Rp 0\n";
                $pesan_lunas .= "--------------------------------\n";
                $pesan_lunas .= "*TOTAL BAYAR  : Rp ".number_format($row['harga'], 0, ',', '.')."*\n";
                $pesan_lunas .= "================================\n";
                $pesan_lunas .= "Status        : *LUNAS* ✅\n\n";
                $pesan_lunas .= "_Terima kasih telah melakukan pembayaran._";

                $link_wa_lunas = "https://wa.me/$no_wa?text=".urlencode($pesan_lunas);
            ?>

            <div class="col-12 col-md-6 col-lg-4 item-bill" data-name="<?php echo strtolower($row['nama']); ?>" data-status="<?php echo $status_class; ?>">
                <div class="card card-bill card-<?php echo $status_class; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="customer-name"><?php echo $row['nama']; ?></div>
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    <i class="fas fa-wifi me-1"></i> <?php echo $row['nama_paket']; ?>
                                </small>
                            </div>
                            <?php if($is_lunas): ?>
                                <i class="fas fa-check-circle text-success fs-4"></i>
                            <?php else: ?>
                                <i class="fas fa-clock text-danger fs-4"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div class="price-tag">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></div>
                            <div class="text-end">
                                <?php if($is_lunas): ?>
                                    <small class="text-success fw-bold d-block" style="font-size: 0.7rem;">LUNAS</small>
                                    <small class="text-muted" style="font-size: 0.65rem;">
                                        Tgl Bayar: <br>
                                        <?php echo date('d/m/y H:i', strtotime($row['tgl_bayar'])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-danger fw-bold" style="font-size: 0.7rem;">BELUM BAYAR</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-flex">
                            <?php if(!$is_lunas): ?>
                                <a href="<?php echo $link_wa_tagih; ?>" target="_blank" class="btn btn-outline-success btn-sm w-50">
                                    <i class="fab fa-whatsapp"></i> Tagih
                                </a>
                                
                                <a href="proses_bayar.php?id=<?php echo $row['id_pelanggan']; ?>&bulan=<?php echo $bulan_tagihan; ?>" 
                                   class="btn btn-primary btn-sm w-50"
                                   onclick="return confirm('Terima pembayaran untuk bulan <?php echo $bulan_tagihan; ?>?')">
                                    <i class="fas fa-money-bill-wave"></i> Bayar
                                </a>
                            <?php else: ?>
                                <a href="cetak_struk.php?id=<?php echo $row['id_tagihan']; ?>" target="_blank" class="btn btn-dark btn-sm w-50">
                                    <i class="fas fa-print"></i> Struk
                                </a>
                                <a href="<?php echo $link_wa_lunas; ?>" target="_blank" class="btn btn-success btn-sm w-50">
                                    <i class="fab fa-whatsapp"></i> WA Lunas
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <?php } ?>
            
            <div id="noData" class="col-12 text-center text-muted mt-5 d-none">
                <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                <p>Data tidak ditemukan.</p>
            </div>

        </div>
    </div>

    <script>
        function filterList() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let items = document.getElementsByClassName('item-bill');
            let visibleCount = 0;
            for (let i = 0; i < items.length; i++) {
                let name = items[i].getAttribute('data-name');
                if (name.includes(input)) {
                    items[i].style.display = ""; 
                    checkTabFilter(items[i]);
                    if(items[i].style.display !== 'none') visibleCount++;
                } else { items[i].style.display = "none"; }
            }
            document.getElementById('noData').classList.toggle('d-none', visibleCount > 0);
        }
        function filterStatus(status, btn) {
            let buttons = document.getElementsByClassName('btn-filter');
            for(let b of buttons) b.classList.remove('active');
            btn.classList.add('active');
            let items = document.getElementsByClassName('item-bill');
            let input = document.getElementById('searchInput').value.toLowerCase();
            for (let i = 0; i < items.length; i++) {
                let itemStatus = items[i].getAttribute('data-status');
                let name = items[i].getAttribute('data-name');
                let matchStatus = (status === 'all') || (itemStatus === status);
                let matchSearch = name.includes(input);
                items[i].style.display = (matchStatus && matchSearch) ? "" : "none";
            }
        }
        function checkTabFilter(item) {
            let activeBtn = document.querySelector('.btn-filter.active').innerText;
            let status = item.getAttribute('data-status');
            if (activeBtn === 'Belum Bayar' && status !== 'belum') item.style.display = 'none';
            if (activeBtn === 'Lunas' && status !== 'lunas') item.style.display = 'none';
        }
    </script>
</body>
</html>