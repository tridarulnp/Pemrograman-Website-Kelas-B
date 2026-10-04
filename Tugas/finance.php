<?php

declare(strict_types=1);

// ## 3 Hubungkan kelas Transaction dan siapkan sesi
require_once './Transaction.php';

// Waktu riwayat transaksi mengikuti WITA
date_default_timezone_set('Asia/Makassar');

session_start();

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}

if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [];
}
