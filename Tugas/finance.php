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

// ## 5 Bangkitkan token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ## 4 Proses POST dan validasi input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $successMessage = '';

    // ## 5 Verifikasi token CSRF
    // hash_equals membandingkan dalam waktu konstan untuk mencegah timing attack
    $postToken = $_POST['csrf_token'] ?? '';

    if (!is_string($postToken) || !hash_equals($_SESSION['csrf_token'], $postToken)) {
        $errors[] = 'Kesalahan Keamanan: token CSRF tidak cocok. Muat ulang halaman lalu coba lagi.';
    } else {
        // ## 4
        $type = trim((string) ($_POST['type'] ?? ''));
        $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);

        if (!in_array($type, Transaction::ALLOWED_TYPES, true)) {
            $errors[] = 'Jenis transaksi wajib dipilih antara deposit atau penarikan.';
        }

        if ($amount === false || round($amount, 2) <= 0) {
            $errors[] = 'Jumlah transaksi harus berupa angka desimal positif minimal Rp 0,01.';
        } elseif ($amount > Transaction::MAX_AMOUNT) {
            $errors[] = sprintf(
                'Jumlah transaksi maksimal Rp %s.',
                number_format(Transaction::MAX_AMOUNT, 2, ',', '.')
            );
        }

        if (empty($errors)) {
            try {
                $transaction = new Transaction(
                    'TRX-' . strtoupper(bin2hex(random_bytes(4))),
                    $type,
                    (float) $amount
                );

                $newBalance = $transaction->process();

                $successMessage = sprintf(
                    'Transaksi %s sebesar Rp %s berhasil diproses. Saldo terkini Rp %s.',
                    $transaction->getType() === 'deposit' ? 'deposit' : 'penarikan',
                    number_format($transaction->getAmount(), 2, ',', '.'),
                    number_format($newBalance, 2, ',', '.')
                );

                // ## 5 Regenerasi token setelah berhasil
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                // ## 4
            } catch (RuntimeException | InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }
    // ## 5 Penutup else verifikasi token
    }