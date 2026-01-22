<?php
session_start();

// Cek apakah ada data yang dipilih
if (!isset($_POST['pilih']) || !isset($_POST['data'])) {
    echo "<script>alert('Tidak ada user yang dipilih!'); window.history.back();</script>";
    exit();
}

$selected_indices = $_POST['pilih']; // Array index yang dicentang (0, 3, 5...)
$all_data = $_POST['data'];          // Array semua data

$vouchers_to_print = [];

foreach ($selected_indices as $index) {
    // Ambil data berdasarkan index yang dipilih
    if (isset($all_data[$index])) {
        $u = $all_data[$index];
        
        // Parsing Komentar untuk mendapatkan Durasi/Harga (Jika formatnya VC | Durasi | Tanggal)
        // Format comment generator biasanya: "VC 3 Jam | 20/01/26"
        $comment = $u['comment'];
        $durasi_label = $comment; 
        $harga_estimasi = 0; // Default 0 karena kita tidak simpan harga di mikrotik secara eksplisit

        // Coba rapikan durasi dari comment
        if (strpos($comment, "|") !== false) {
            $parts = explode("|", $comment);
            $durasi_label = trim($parts[0]); // Ambil bagian pertama (VC xxx)
            $durasi_label = str_replace("VC", "", $durasi_label); // Hilangkan kata VC
        }

        // Masukkan ke array format cetak
        $vouchers_to_print[] = [
            'username' => $u['username'],
            'password' => $u['password'],
            'harga'    => $harga_estimasi, // Harga bisa diset 0 atau default
            'durasi'   => trim($durasi_label),
            'profile'  => $u['profile']
        ];
    }
}

// Simpan ke Session (Menimpa session voucher generator yang lama)
$_SESSION['vouchers'] = $vouchers_to_print;

// Redirect ke halaman cetak yang sudah Anda miliki
header("Location: cetak_voucher.php");
exit();
?>