<?php
require('routeros_api.class.php');

$ip_mikrotik   = "sin3.tunnel.id:3157"; // Sesuaikan IP Router (Gateway)
$user_mikrotik = "AwraNet";        // User login Winbox
$pass_mikrotik = "miekoclok";  // Password login Winbox

$API = new RouterosAPI();
$API->debug = false; // Ubah ke true jika ingin melihat error log
?>