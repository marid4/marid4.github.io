<?php
require('routeros_api.class.php');

$API = new RouterosAPI();
$API->debug = false;

// Konfigurasi Database
$conn = new mysqli("localhost", "root", "", "billing_rtrw");

// Konfigurasi MikroTik
$mikrotik_ip = "sin3.tunnel.id:3157";
$mikrotik_user = "AwraNet";
$mikrotik_pass = "miekoclok";

function connectMikrotik() {
    global $API, $mikrotik_ip, $mikrotik_user, $mikrotik_pass;
    if ($API->connect($mikrotik_ip, $mikrotik_user, $mikrotik_pass)) {
        return true;
    }
    return false;
}
?>