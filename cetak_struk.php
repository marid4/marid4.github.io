<?php
include 'koneksi.php';

// FUNGSI FORMAT WA (Anti Gagal)
if (!function_exists('formatWA')) {
    function formatWA($nomor) {
        // 1. Bersihkan input
        $nomor = preg_replace('/[^0-9]/', '', $nomor);

        // 2. Logika perbaikan awalan
        if(substr($nomor, 0, 1) == '0'){ 
            $nomor = '62' . substr($nomor, 1); 
        }
        elseif(substr($nomor, 0, 1) == '8'){
            $nomor = '62' . $nomor;
        }
        
        return $nomor;
    }
}

$id_tagihan = $_GET['id'];

// AMBIL DATA DETIL
$query = mysqli_query($koneksi, "
    SELECT t.*, p.nama, p.no_wa, p.alamat, pk.nama_paket, pk.harga 
    FROM tagihan t
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    JOIN paket pk ON p.id_paket = pk.id_paket
    WHERE t.id_tagihan = '$id_tagihan'
");

$data = mysqli_fetch_assoc($query);

if(!$data) { die("Data tagihan tidak ditemukan"); }

// CONFIG
$nama_isp = "AWRANET";
$alamat_isp = "Jl. Sekar kemuning no. 55";

// --- PERSIAPAN PESAN WHATSAPP ---
$tgl_bayar = date('d/m/Y H:i', strtotime($data['tgl_bayar']));
$total_rp = number_format($data['harga'], 0, ',', '.');
$no_wa_pelanggan = formatWA($data['no_wa']);

// Format Teks Struk WA
// ... (Kode koneksi dan query di atas TETAP SAMA jangan diubah) ...

// --- BAGIAN INI YANG DIUBAH (FORMAT WA ALA MITRA BUKALAPAK) ---
$tgl_bayar = date('d/m/Y H:i', strtotime($data['tgl_bayar']));
$total_rp = number_format($data['harga'], 0, ',', '.');
$no_wa_pelanggan = formatWA($data['no_wa']);

// Header
$wa_text  = "*STRUK PEMBAYARAN TAGIHAN*\n";
$wa_text .= "$nama_isp\n";
$wa_text .= "================================\n";

// Info Transaksi
$wa_text .= "Tanggal       : $tgl_bayar\n";
$wa_text .= "No. Transaksi : #INV-{$data['id_tagihan']}\n";
$wa_text .= "Pelanggan     : {$data['nama']}\n\n";

// Detail
$wa_text .= "*DETAIL PEMBAYARAN*\n";
$wa_text .= "Produk        : Internet - {$data['nama_paket']}\n";
$wa_text .= "Keterangan    : Tagihan Bln {$data['bulan']}\n";
$wa_text .= "--------------------------------\n";

// Rincian Biaya
$wa_text .= "Nominal       : Rp $total_rp\n";
$wa_text .= "Biaya Admin   : Rp 0\n";
$wa_text .= "--------------------------------\n";
$wa_text .= "*TOTAL BAYAR  : Rp $total_rp*\n";
$wa_text .= "================================\n";

// Footer & Status
$wa_text .= "Status        : *LUNAS* ✅\n\n";
$wa_text .= "_Terima kasih telah melakukan pembayaran._\n";
$wa_text .= "_Simpan struk ini sebagai bukti transaksi yang sah._";

$link_share = "https://wa.me/$no_wa_pelanggan?text=" . urlencode($wa_text);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?php echo $id_tagihan; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        /* CSS RESET & PREVIEW MODE */
        body {
            background-color: #525659; /* Abu-abu gelap (Viewer Mode) */
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px 0 100px 0; /* Padding bawah lebih besar untuk tombol */
            display: flex;
            justify-content: center;
        }

        /* AREA KERTAS STRUK (Target Screenshot) */
        .paper {
            background: #fff;
            width: 58mm; /* Ukuran Thermal 58mm */
            padding: 15px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            min-height: 200px;
            color: #000;
            position: relative;
        }

        /* TYPOGRAPHY */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        .flex { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .small { font-size: 10px; }
        .normal { font-size: 12px; }
        .big { font-size: 14px; }

        /* ACTION BAR (FIXED BOTTOM) */
        .action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            padding: 10px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr; /* 4 Kolom Tombol */
            gap: 5px;
            z-index: 999;
        }

        .btn {
            padding: 10px 5px;
            border: none;
            border-radius: 8px;
            font-family: sans-serif;
            font-weight: bold;
            font-size: 11px; /* Font kecil agar muat 4 tombol */
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: flex;
            flex-direction: column; /* Ikon di atas teks */
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn i { font-size: 16px; }

        .btn-back { background-color: #e9ecef; color: #333; }
        .btn-png { background-color: #0d6efd; color: white; } /* Biru */
        .btn-wa { background-color: #25D366; color: white; }   /* Hijau */
        .btn-print { background-color: #212529; color: white; } /* Hitam */

        /* PRINT MODE (HANYA KERTAS) */
        @media print {
            body { background: none; padding: 0; display: block; }
            .action-bar, .no-print { display: none !important; }
            .paper { width: 100%; box-shadow: none; padding: 0; margin: 0; }
            @page { margin: 0; size: 58mm auto; }
        }
    </style>
</head>
<body>

    <div class="paper" id="strukArea">
        <div class="text-center">
            <div class="big bold"><?php echo $nama_isp; ?></div>
            <div class="small"><?php echo $alamat_isp; ?></div>
        </div>

        <div class="line"></div>

        <div class="normal">
            <div class="flex"><span>Tgl:</span> <span><?php echo $tgl_bayar; ?></span></div>
            <div class="flex"><span>Reff:</span> <span>#INV-<?php echo $data['id_tagihan']; ?></span></div>
            <div class="flex"><span>Cust:</span> <span class="bold"><?php echo substr($data['nama'], 0, 15); ?></span></div>
        </div>

        <div class="line"></div>

        <div class="normal">
            <div class="bold"><?php echo $data['nama_paket']; ?></div>
            <div class="flex"><span>Periode:</span> <span><?php echo $data['bulan']; ?></span></div>
        </div>

        <div class="line"></div>

        <div class="big flex" style="margin-top: 5px;">
            <span class="bold">TOTAL:</span>
            <span class="bold">Rp <?php echo $total_rp; ?></span>
        </div>

        <div class="text-center" style="margin-top: 10px; border: 1px solid #000; padding: 5px; border-radius: 4px;">
            <span class="bold big">LUNAS ✅</span>
        </div>

        <div class="line"></div>

        <div class="text-center small" style="margin-top: 10px;">
            Terima kasih atas pembayaran Anda.<br>
            Simpan struk ini sebagai bukti sah.
        </div>
        
        <br>
        <div class="text-center small">--- <?php echo $nama_isp; ?> ---</div>
    </div>

    <div class="action-bar no-print">
        <a href="billing.php?filter_bulan=<?php echo date('Y-m', strtotime($data['bulan'])); ?>" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        
        <button onclick="savePNG()" class="btn btn-png">
            <i class="fas fa-image"></i> Save PNG
        </button>
        
        <a href="<?php echo $link_share; ?>" target="_blank" class="btn btn-wa">
            <i class="fab fa-whatsapp"></i> Share WA
        </a>

        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> Cetak
        </button>
    </div>

    <script>
        function savePNG() {
            // Target elemen kertas struk
            var element = document.getElementById("strukArea");

            // Gunakan html2canvas