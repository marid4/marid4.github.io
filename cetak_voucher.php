<?php
session_start();
// Cek apakah ada data voucher
if (!isset($_SESSION['vouchers'])) {
    die("<div style='text-align:center; padding:20px; font-family:sans-serif;'>Tidak ada data voucher. <br><br><a href='voucher.php' style='background:#0d6efd; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Kembali ke Generator</a></div>");
}

$vouchers = $_SESSION['vouchers'];
$nama_wifi = "AWRANET HOTSPOT"; 
$dns_name  = "http://awra.net";  
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Voucher</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- LIBRARY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        body { background-color: #e0e5ec; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding-bottom: 80px; }

        /* --- UI PANEL --- */
        .control-panel { background: white; padding: 15px; margin-bottom: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .layout-btn { border: 2px solid #dee2e6; background: #fff; color: #333; font-weight: bold; width: 100%; padding: 10px; border-radius: 8px; transition: .2s; }
        .layout-btn:hover { background: #f8f9fa; border-color: #aaa; }
        .layout-btn.active { border-color: #0d6efd; background: #e7f1ff; color: #0d6efd; }
        .layout-btn i { font-size: 1.2rem; display: block; margin-bottom: 5px; }

        /* --- UI SELEKSI --- */
        .voucher-wrapper { position: relative; cursor: pointer; transition: transform 0.2s; }
        .voucher-wrapper:hover { transform: translateY(-3px); }
        .card-preview { background: white; border-radius: 12px; padding: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 2px solid transparent; position: relative; overflow: hidden; }
        .voucher-wrapper.selected .card-preview { border-color: #0d6efd; background-color: #f0f7ff; }
        .voucher-checkbox { position: absolute; top: 10px; right: 10px; transform: scale(1.3); z-index: 10; cursor: pointer; }
        .btn-wa-share { position: absolute; bottom: 10px; right: 10px; z-index: 20; background-color: #25D366; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: .2s; text-decoration: none; }
        .btn-wa-share:hover { transform: scale(1.1); background-color: #128C7E; color: white; }

        /* --- CORE TICKET STYLE --- */
        .print-container { position: absolute; top: -9999px; left: -9999px; } 
        
        .ticket {
            width: 48mm; margin: 0 auto 15px auto; overflow: hidden; position: relative; color: #333;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
            page-break-inside: avoid;
        }
        
        /* ----------------------------------------------------------- */
        /* MODE 1: DEFAULT (PREMIUM) - Background & Styles Lengkap */
        /* ----------------------------------------------------------- */
        .ticket.mode-default {
            border: 1px solid #333; border-radius: 8px;
            background-color: #fff;
            background-image: radial-gradient(#999 1px, transparent 1px);
            background-size: 10px 10px;
        }
        .ticket.mode-default .ticket-header { background: #000; color: #fff; padding: 6px 0; text-align: center; font-weight: 800; font-size: 12px; text-transform: uppercase; }
        .ticket.mode-default .ticket-body { padding: 8px 5px; text-align: center; position: relative; z-index: 2; }
        .ticket.mode-default .ticket-overlay { background: rgba(255,255,255,0.85); position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .ticket.mode-default .ticket-price { background: #000; color: #fff; display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 14px; font-weight: bold; margin-bottom: 8px; }
        .ticket.mode-default .code-box { border: 2px dashed #000; background: rgba(255,255,255,0.9); padding: 5px; border-radius: 6px; }
        .ticket.mode-default .ticket-code { font-family: 'Courier New', monospace; font-size: 20px; font-weight: 900; letter-spacing: 2px; color: #000; display: block; }
        .ticket.mode-default .qr-area { display: flex; justify-content: center; margin: 8px 0; }
        .ticket.mode-default .qr-area img { border: 4px solid #fff; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); width: 75px; height: 75px; }
        .ticket.mode-default .ticket-footer { font-size: 9px; display: flex; justify-content: space-between; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; font-weight: bold; }

        /* ----------------------------------------------------------- */
        /* MODE 2: SMALL (HEMAT KERTAS) - Hitam Putih, Compact */
        /* ----------------------------------------------------------- */
        .ticket.mode-small {
            border: 1px dashed #000; border-radius: 0; padding: 5px;
            background: white !important; /* Paksa Putih */
        }
        .ticket.mode-small .ticket-header { font-weight: bold; text-align: center; font-size: 14px; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 5px; text-transform: uppercase; }
        .ticket.mode-small .ticket-body { text-align: center; }
        .ticket.mode-small .ticket-overlay { display: none; }
        .ticket.mode-small .ticket-price { font-size: 12px; font-weight: bold; }
        .ticket.mode-small .code-box { margin: 2px 0; }
        .ticket.mode-small .ticket-code { font-family: monospace; font-size: 18px; font-weight: bold; border: 1px solid #000; display: inline-block; padding: 2px 10px; margin: 2px 0; }
        .ticket.mode-small .qr-area { display: none; } 
        .ticket.mode-small .scan-label { display: none; }
        .ticket.mode-small .ticket-footer { font-size: 9px; margin-top: 5px; border-top: 1px dashed #000; padding-top: 2px; display: flex; justify-content: space-between; }

        /* ----------------------------------------------------------- */
        /* MODE 3: QR ONLY - Fokus QR Besar */
        /* ----------------------------------------------------------- */
        .ticket.mode-qr {
            border: 2px solid #000; border-radius: 10px; padding: 10px;
            background: white !important; text-align: center;
        }
        .ticket.mode-qr .ticket-header { font-weight: bold; font-size: 14px; margin-bottom: 5px; }
        .ticket.mode-qr .ticket-overlay { display: none; }
        .ticket.mode-qr .ticket-price { font-size: 12px; font-weight: bold; background: #eee; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-bottom: 5px; }
        .ticket.mode-qr .qr-area { display: flex; justify-content: center; margin: 5px 0; }
        .ticket.mode-qr .qr-area img { width: 100px !important; height: 100px !important; }
        .ticket.mode-qr .ticket-code { font-family: monospace; font-size: 14px; font-weight: bold; margin-top: 5px; }
        .ticket.mode-qr .code-box { border: none; padding: 0; }
        .ticket.mode-qr .scan-label { display: none; }
        .ticket.mode-qr .ticket-footer { display: none; }

        /* --- MODE PRINT --- */
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print, nav, .control-panel, .selection-area { display: none !important; }
            .print-container { display: block; position: static; width: 100%; margin: 0; }
            .ticket { margin-bottom: 5mm; margin-left: auto; margin-right: auto; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-light bg-white shadow-sm sticky-top no-print">
        <div class="container">
            <a class="btn btn-outline-secondary btn-sm rounded-pill px-3" href="voucher.php"><i class="fas fa-arrow-left"></i> Kembali</a>
            <div class="d-flex gap-2">
                <button onclick="downloadSelectedPNG()" class="btn btn-primary btn-sm rounded-pill fw-bold shadow-sm"><i class="fas fa-image"></i> Save PNG</button>
                <button onclick="printSelected()" class="btn btn-dark btn-sm rounded-pill fw-bold shadow-sm"><i class="fas fa-print"></i> Cetak</button>
            </div>
        </div>
    </nav>

    <!-- PILIHAN LAYOUT -->
    <div class="container mt-3 no-print">
        <div class="control-panel">
            <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-layer-group text-primary"></i> Pilih Tampilan Struk</h6>
            <div class="row g-2">
                <div class="col-4">
                    <button class="layout-btn active" onclick="changeLayout('default', this)">
                        <i class="fas fa-star"></i> Default (Premium)
                    </button>
                </div>
                <div class="col-4">
                    <button class="layout-btn" onclick="changeLayout('small', this)">
                        <i class="fas fa-compress-alt"></i> Small (Hemat)
                    </button>
                </div>
                <div class="col-4">
                    <button class="layout-btn" onclick="changeLayout('qr', this)">
                        <i class="fas fa-qrcode"></i> QR Only
                    </button>
                </div>
            </div>
            
            <!-- CUSTOM BACKGROUND (Hanya muncul di mode Default) -->
            <div id="bgControl" class="mt-3 pt-3 border-top">
                <div class="row align-items-end g-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Upload Background</label>
                        <input type="file" class="form-control form-control-sm" accept="image/*" onchange="changeBgImage(this)">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Warna</label>
                        <input type="color" class="form-control form-control-color w-100" value="#ffffff" oninput="changeBgColor(this.value)">
                    </div>
                    <!-- SLIDER TRANSPARANSI (YANG ANDA CARI) -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Transparansi Overlay</label>
                        <input type="range" class="form-range" min="0" max="1" step="0.1" value="0.85" oninput="changeOpacity(this.value)">
                        <div class="form-text small" style="margin-top:-5px">Geser agar tulisan terbaca</div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-danger btn-sm w-100" onclick="resetBg()">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AREA SELEKSI VOUCHER -->
    <div class="container selection-area no-print">
        <div class="form-check mb-3 ms-2 bg-white p-2 rounded shadow-sm d-inline-block">
            <input class="form-check-input ms-1" type="checkbox" id="selectAll" onclick="toggleAll(this)" checked>
            <label class="form-check-label fw-bold ms-2 pe-2" for="selectAll">Pilih Semua</label>
        </div>

        <div class="row g-3">
            <?php foreach ($vouchers as $index => $v) { 
                $loginUrl = "http://{$dns_name}/login?username={$v['username']}&password={$v['password']}";
                $wa_msg = "Voucher WiFi: $nama_wifi\nKode: *{$v['username']}*\nHarga: Rp ".number_format($v['harga'])."\nLogin: $loginUrl";
                $wa_link = "https://wa.me/?text=".urlencode($wa_msg);
            ?>
                <div class="col-6 col-md-4 col-lg-3 voucher-wrapper selected" id="wrap-<?php echo $index; ?>">
                    <input type="checkbox" class="voucher-checkbox form-check-input" id="check-<?php echo $index; ?>" checked onclick="toggleCheck(<?php echo $index; ?>)">
                    <div class="card-preview" onclick="toggleCheck(<?php echo $index; ?>)">
                        <h5 class="fw-bold mb-1 text-dark"><?php echo $v['username']; ?></h5>
                        <div class="mb-2"><i class="fas fa-qrcode fa-2x text-secondary"></i></div>
                        <span class="badge bg-dark mt-2">Rp <?php echo number_format($v['harga']); ?></span>
                    </div>
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-wa-share" title="Share WA" onclick="event.stopPropagation();"><i class="fab fa-whatsapp"></i></a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- AREA OUTPUT PRINT -->
    <div class="print-container" id="printArea">
        <?php foreach ($vouchers as $index => $v) { 
            $qrData = "http://{$dns_name}/login?username={$v['username']}&password={$v['password']}";
        ?>
            <!-- Default Class: mode-default -->
            <div class="ticket mode-default" id="ticket-<?php echo $index; ?>">
                <div class="ticket-overlay"></div>
                <div class="ticket-content">
                    <div class="ticket-header"><?php echo $nama_wifi; ?></div>
                    <div class="ticket-body">
                        <div class="ticket-price">Rp <?php echo number_format($v['harga'], 0, ',', '.'); ?></div>
                        
                        <div class="code-box">
                            <span class="ticket-code"><?php echo $v['username']; ?></span>
                        </div>

                        <?php if($v['username'] != $v['password']) { ?>
                            <div style="font-size: 10px;">Pass: <b><?php echo $v['password']; ?></b></div>
                        <?php } ?>

                        <div class="qr-area" id="qr-<?php echo $index; ?>"></div>
                        <div class="scan-label" style="font-size: 8px; margin-top: -2px; font-weight:bold;">Scan Login</div>

                        <div class="ticket-footer">
                            <span><?php echo $v['durasi']; ?></span>
                            <span><?php echo $dns_name; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <script>
        // --- 1. QR GENERATOR ---
        window.onload = function() {
            <?php foreach ($vouchers as $index => $v) { 
                $qrData = "http://{$dns_name}/login?username={$v['username']}&password={$v['password']}";
            ?>
                new QRCode(document.getElementById("qr-<?php echo $index; ?>"), {
                    text: "<?php echo $qrData; ?>", width: 100, height: 100,
                    colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.L
                });
            <?php } ?>
        };

        // --- 2. GANTI LAYOUT ---
        function changeLayout(mode, btn) {
            let btns = document.querySelectorAll('.layout-btn');
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            let tickets = document.querySelectorAll('.ticket');
            tickets.forEach(ticket => { ticket.className = 'ticket mode-' + mode; });

            let bgCtrl = document.getElementById('bgControl');
            if(mode === 'default') bgCtrl.style.display = 'block';
            else bgCtrl.style.display = 'none';
        }

        // --- 3. SELEKSI ---
        function toggleCheck(id) {
            let cb = document.getElementById('check-' + id);
            let wr = document.getElementById('wrap-' + id);
            if (event.target !== cb) cb.checked = !cb.checked;
            if(cb.checked) wr.classList.add('selected'); else wr.classList.remove('selected');
        }
        function toggleAll(src) {
            let cbs = document.querySelectorAll('.voucher-checkbox');
            let wrs = document.querySelectorAll('.voucher-wrapper');
            cbs.forEach((cb, i) => { cb.checked = src.checked; if(src.checked) wrs[i].classList.add('selected'); else wrs[i].classList.remove('selected'); });
        }

        // --- 4. BACKGROUND & TRANSPARENCY ---
        function applyStyle(style, val) { document.querySelectorAll('.ticket').forEach(t => t.style[style] = val); }
        function changeBgImage(input) { if (input.files[0]) { let r = new FileReader(); r.onload = function (e) { applyStyle('backgroundImage', 'url(' + e.target.result + ')'); applyStyle('backgroundSize', '100% 100%'); applyStyle('backgroundRepeat', 'no-repeat'); }; r.readAsDataURL(input.files[0]); } }
        function changeBgColor(c) { applyStyle('backgroundImage', 'none'); applyStyle('backgroundColor', c); }
        
        // FUNGSI UBAH TRANSPARANSI OVERLAY
        function changeOpacity(v) { 
            document.querySelectorAll('.ticket-overlay').forEach(o => o.style.backgroundColor = `rgba(255,255,255,${v})`); 
        }

        function resetBg() { applyStyle('backgroundImage', 'radial-gradient(#999 1px, transparent 1px)'); applyStyle('backgroundSize', '10px 10px'); applyStyle('backgroundColor', '#fff'); document.getElementById('bgInput').value = ''; }

        // --- 5. PRINT & SAVE ---
        function prepare() { let c = 0; <?php foreach ($vouchers as $i => $v) { ?> if(document.getElementById('check-<?php echo $i; ?>').checked){ document.getElementById('ticket-<?php echo $i; ?>').style.display='block'; c++; } else { document.getElementById('ticket-<?php echo $i; ?>').style.display='none'; } <?php } ?> return c; }
        function printSelected() { if(prepare() > 0) window.print(); else alert("Pilih minimal satu voucher!"); }
        async function downloadSelectedPNG() { if(prepare() === 0) { alert("Pilih voucher!"); return; } const pc = document.querySelector('.print-container'); pc.style.position = 'static'; const ts = document.querySelectorAll('.ticket'); for (let t of ts) { if(t.style.display !== 'none') { await html2canvas(t, { scale: 3 }).then(c => { let l = document.createElement('a'); l.download = 'Voucher-' + Math.random().toString(36).substr(2, 5) + '.png'; l.href = c.toDataURL("image/png"); l.click(); }); } } pc.style.position = 'absolute'; pc.style.top = '-9999px'; }
    </script>
</body>
</html>