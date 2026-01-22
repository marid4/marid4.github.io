<?php
header('Content-Type: application/json');
require('routeros_api.class.php');

// Konfigurasi Manual di sini (biar cepat loadnya)
$ip = "sin3.tunnel.id:3157";
$user = "AwraNet";
$pass = "miekoclok"; // Sesuaikan password Anda
$interface = "LAN1-ISP"; // Pastikan nama interface SAMA PERSIS dengan di Winbox (huruf besar/kecil pengaruh)

$API = new RouterosAPI();
$API->debug = false;

if ($API->connect($ip, $user, $pass)) {
    // Ambil traffic sekali saja
    $rows = $API->comm("/interface/monitor-traffic", array(
        "interface" => $interface,
        "once" => "",
    ));

    $rx = $rows[0]['rx-bits-per-second'];
    $tx = $rows[0]['tx-bits-per-second'];

    // Kirim data JSON ke Dashboard
    echo json_encode([
        "rx" => $rx, 
        "tx" => $tx
    ]);

    $API->disconnect();
} else {
    echo json_encode(["rx" => 0, "tx" => 0]);
}
?>