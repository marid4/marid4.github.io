<?php
require('routeros_api.class.php');

$ip_mikrotik   = "192.168.88.1"; // Sesuaikan IP Router (Gateway)
$user_mikrotik = "admin";        // User login Winbox
$pass_mikrotik = "";  // Password login Winbox

$API = new RouterosAPI();
$API->debug = false; // Ubah ke true jika ingin melihat error log
?>
