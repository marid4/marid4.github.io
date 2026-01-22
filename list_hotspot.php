<?php
session_start();
include 'koneksi.php';
include 'koneksi_mikrotik.php';

// Ambil Data User Hotspot dari MikroTik
$hotspot_users = [];
$total_user = 0;

if ($API->connect($ip_mikrotik, $user_mikrotik, $pass_mikrotik)) {
    // Ambil semua user
    $hotspot_users = $API->comm("/ip/hotspot/user/print");
    // Balik urutan agar yang baru dibuat ada di atas
    $hotspot_users = array_reverse($hotspot_users); 
    $total_user = count($hotspot_users);
    $API->disconnect();
}

// Config DNS untuk Link Login di WA
$dns_name = "awranet.net"; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>List User Hotspot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 100px; }
        
        /* Sticky Search Header */
        .header-search {
            position: sticky; top: 0; z-index: 100;
            background: white; padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* Card User */
        .user-card {
            background: white; border-radius: 12px; border: 1px solid #eee;
            padding: 15px; margin-bottom: 10px; position: relative;
            transition: 0.2s; cursor: pointer;
        }
        .user-card:active { background-color: #f0f8ff; border-color: #0d6efd; }
        
        /* Checkbox Styling */
        .form-check-input.custom-check {
            transform: scale(1.5); margin-right: 10px; cursor: pointer;
        }
        
        /* Highlight jika dipilih */
        .user-card.selected { background-color: #e7f1ff; border-color: #0d6efd; }

        /* Floating Bottom Action */
        .bottom-action {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: white; border-top: 1px solid #ddd;
            padding: 15px; z-index: 1050;
            display: flex; gap: 10px; box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-light bg-primary shadow-sm mb-0">
        <div class="container">
            <a class="btn btn-primary btn-sm border-white" href="index.php"><i class="fas fa-arrow-left"></i> Kembali</a>
            <span class="fw-bold text-white">Database Hotspot (<?php echo $total_user; ?>)</span>
        </div>
    </nav>

    <!-- SEARCH & TOOLS -->
    <div class="header-search">
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="Cari username / komentar..." onkeyup="filterUsers()">
        </div>
        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAll(this)">
            <label class="form-check-label small fw-bold text-secondary" for="selectAll">Pilih Semua</label>
        </div>
    </div>

    <!-- FORM UNTUK KIRIM DATA KE CETAK -->
    <form action="proses_print_seleksi.php" method="POST" id="formCetak">
        <div class="container mt-3" id="userContainer">
            
            <?php foreach ($hotspot_users as $index => $u) { 
                // Parsing Data (Kadang ada user tanpa comment)
                $username = $u['name'];
                $password = $u['password'] ?? "";
                $profile  = $u['profile'] ?? "default";
                $comment  = $u['comment'] ?? "-";
                
                // Coba ambil harga/durasi dari comment jika formatnya "VC | Durasi | Tanggal"
                $parts = explode("|", $comment);
                $durasi_display = isset($parts[1]) ? trim($parts[1]) : $comment;
                
                // Format Pesan WA
                $wa_msg = "Detail Akun WiFi:\nUser: *$username*\nPass: *$password*\nPaket: $profile\nLogin: http://$dns_name/login";
                $wa_link = "https://wa.me/?text=" . urlencode($wa_msg);
            ?>

            <!-- Item User -->
            <div class="user-card d-flex align-items-center" id="card-<?php echo $index; ?>" onclick="toggleCard(<?php echo $index; ?>)">
                
                <!-- Checkbox (Value = Index Array) -->
                <div class="me-2">
                    <input type="checkbox" name="pilih[]" value="<?php echo $index; ?>" 
                           class="form-check-input custom-check user-checkbox" 
                           id="check-<?php echo $index; ?>" onclick="event.stopPropagation(); toggleCard(<?php echo $index; ?>)">
                </div>

                <!-- Hidden Data (Untuk dikirim ke proses cetak) -->
                <input type="hidden" name="data[<?php echo $index; ?>][username]" value="<?php echo $username; ?>">
                <input type="hidden" name="data[<?php echo $index; ?>][password]" value="<?php echo $password; ?>">
                <input type="hidden" name="data[<?php echo $index; ?>][profile]" value="<?php echo $profile; ?>">
                <input type="hidden" name="data[<?php echo $index; ?>][comment]" value="<?php echo $comment; ?>">

                <!-- Tampilan Info -->
                <div class="flex-grow-1 ms-2 search-target" data-filter="<?php echo strtolower($username . ' ' . $comment . ' ' . $profile); ?>">
                    <h6 class="fw-bold mb-0 text-primary"><?php echo $username; ?></h6>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        <i class="fas fa-key me-1"></i> <?php echo $password; ?> &bull; 
                        <span class="badge bg-light text-dark border"><?php echo $profile; ?></span>
                    </small>
                    <small class="text-secondary" style="font-size: 0.7rem;">
                        <i class="far fa-comment-dots"></i> <?php echo $durasi_display; ?>
                    </small>
                </div>

                <!-- Tombol Share WA -->
                <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success btn-sm rounded-circle shadow-sm" 
                   style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"
                   onclick="event.stopPropagation();">
                    <i class="fab fa-whatsapp fs-5"></i>
                </a>

            </div>

            <?php } ?>

            <?php if(empty($hotspot_users)) { echo "<div class='text-center py-5'>Data Kosong / Gagal Konek Mikrotik</div>"; } ?>

        </div>
    </form>

    <!-- FLOATING ACTION BAR -->
    <div class="bottom-action">
        <div class="flex-grow-1">
            <span class="fw-bold d-block text-dark" id="countSelected">0 Terpilih</span>
            <small class="text-muted">Siap dicetak ulang</small>
        </div>
        <button type="button" onclick="submitCetak()" class="btn btn-dark fw-bold px-4 rounded-pill">
            <i class="fas fa-print me-2"></i> CETAK
        </button>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function toggleCard(index) {
            let checkbox = document.getElementById('check-' + index);
            let card = document.getElementById('card-' + index);
            
            // Toggle logic
            checkbox.checked = !checkbox.checked;
            
            if(checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            updateCount();
        }

        function toggleAll(source) {
            let checkboxes = document.querySelectorAll('.user-checkbox');
            let visibleCards = document.querySelectorAll('.user-card:not([style*="display: none"])'); // Hanya yang tampil
            
            visibleCards.forEach(card => {
                let cb = card.querySelector('.user-checkbox');
                cb.checked = source.checked;
                if(source.checked) card.classList.add('selected');
                else card.classList.remove('selected');
            });
            updateCount();
        }

        function updateCount() {
            let count = document.querySelectorAll('.user-checkbox:checked').length;
            document.getElementById('countSelected').innerText = count + " Terpilih";
        }

        function filterUsers() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let cards = document.getElementsByClassName('user-card');
            
            for (let i = 0; i < cards.length; i++) {
                let text = cards[i].querySelector('.search-target').getAttribute('data-filter');
                if (text.includes(input)) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }

        function submitCetak() {
            let count = document.querySelectorAll('.user-checkbox:checked').length;
            if(count === 0) {
                alert("Pilih minimal satu user untuk dicetak!");
                return;
            }
            document.getElementById('formCetak').submit();
        }
    </script>

</body>
</html>