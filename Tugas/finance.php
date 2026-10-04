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

     // ## 4
    // Post/Redirect/Get: mencegah transaksi terkirim ulang saat halaman di-refresh
    $_SESSION['flash'] = ['errors' => $errors, 'success' => $successMessage];
    header('Location: finance.php');
    exit;
}

$flash = $_SESSION['flash'] ?? ['errors' => [], 'success' => ''];
unset($_SESSION['flash']);

$errors = $flash['errors'];
$successMessage = $flash['success'];
// ## 3 Tampilan halaman, saldo, dan formulir
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Keuangan Sederhana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>

<body class="bg-light p-5">

    <div class="container" style="max-width: 720px;">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h4 mb-0">Sistem Manajemen Keuangan Sederhana</h1>
            </div>
            <div class="card-body">

                <div class="mb-4">
                    <span class="text-secondary">Sisa Saldo</span>
                    <p class="h2 mb-0">
                        Rp <?= htmlspecialchars(number_format((float) $_SESSION['balance'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <!-- ## 4 Pesan error dan sukses -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- ## 3 -->
                <form action="./finance.php" method="POST">
                    <!-- ## 5 Input token CSRF -->
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- ## 3 -->
                    <div class="mb-3">
                        <label for="type" class="form-label">Jenis Transaksi</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="deposit">Deposit (menambah saldo)</option>
                            <option value="withdraw">Penarikan (mengurangi saldo)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Jumlah Transaksi (Rp)</label>
                        <input type="number" class="form-control" id="amount" name="amount"
                            step="0.01" min="0.01" required placeholder="Contoh: 150000.50">
                        <div class="form-text">Wajib angka desimal positif.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Proses Transaksi</button>
                </form>

            </div>
        </div>

        <!-- ## 6 Riwayat transaksi -->
        <?php if (!empty($_SESSION['transactions'])): ?>
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h2 class="h5 mb-0">Riwayat Transaksi</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Jenis</th>
                                    <th scope="col" class="text-end">Jumlah</th>
                                    <th scope="col" class="text-end">Saldo Akhir</th>
                                    <th scope="col">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($_SESSION['transactions']) as $trx): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($trx['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td>
                                            <?php if ($trx['type'] === 'deposit'): ?>
                                                <span class="badge text-bg-success">Deposit</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-warning">Penarikan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            Rp <?= htmlspecialchars(number_format((float) $trx['amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="text-end">
                                            Rp <?= htmlspecialchars(number_format((float) $trx['balance_after'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars($trx['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ## 3 -->
    </div>

</body>

</html>