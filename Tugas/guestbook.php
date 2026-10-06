<?php
/**
 * Halaman Utama Buku Tamu (guestbook.php)
 */

session_start();

// ==========================================
// 1. KONEKSI BASIS DATA & CSRF (PDO)
// ==========================================
$host = '127.0.0.1';
$db   = 'perpustakaan';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/GuestBook.php';
$guestbook = new GuestBook($pdo);

$errors = [];
$successMessage = '';
$inputNama = '';
$inputEmail = '';
$inputPesan = '';

// ==========================================
// 2. PEMROSESAN FORMULIR & VALIDASI
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errors[] = "Validasi token CSRF gagal. Permintaan tidak sah.";
    } else {
        $inputNama = trim($_POST['nama'] ?? '');
        $inputEmail = trim($_POST['email'] ?? '');
        $inputPesan = trim($_POST['pesan'] ?? '');

        // Validasi masukan
        if ($inputNama === '') {
            $errors[] = "Nama tidak boleh kosong.";
        }
        if ($inputEmail === '' || !filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid.";
        }
        if (mb_strlen($inputPesan) < 5) {
            $errors[] = "Pesan minimal lima karakter.";
        }

        // Simpan data jika lolos validasi
        if (empty($errors)) {
            if ($guestbook->tambahPesan($inputNama, $inputEmail, $inputPesan)) {
                $successMessage = "Pesan Anda berhasil dikirim dan disimpan ke basis data.";
                $inputNama = '';
                $inputEmail = '';
                $inputPesan = '';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $errors[] = "Terjadi kegagalan saat menyimpan data.";
            }
        }
    }
}

// Ambil daftar pesan buku tamu
$daftarPesan = [];
try {
    $daftarPesan = $guestbook->ambilSemuaPesan();
} catch (PDOException $e) {
    $errors[] = "Gagal memuat daftar buku tamu: " . $e->getMessage();
}

// ==========================================
// 3. TAMPILAN ANTARMUKA (FORM & TABEL)
// ==========================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Perpustakaan</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.5;
            padding: 30px 15px;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 24px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .header p {
            font-size: 14px;
            color: #64748b;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 6px;
        }

        .required {
            color: #dc2626;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 9px 12px;
            font-size: 14px;
            font-family: inherit;
            color: #1e293b;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .btn-submit {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 18px;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-error ul {
            margin-left: 20px;
            margin-top: 4px;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .meta-nama {
            font-weight: 600;
            color: #0f172a;
        }

        .meta-email {
            font-size: 13px;
            color: #64748b;
        }

        .meta-pesan {
            color: #334155;
            white-space: pre-line;
            word-break: break-word;
        }

        .meta-date {
            font-size: 13px;
            color: #64748b;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Buku Tamu Perpustakaan</h1>
            <p>Silakan isi formulir di bawah ini untuk mencatat kunjungan dan pesan Anda.</p>
        </div>

        <!-- Formulir Penulisan Pesan -->
        <div class="card">
            <h2 class="card-title">Formulir Buku Tamu</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Terdapat kesalahan:</strong>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="guestbook.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="nama" name="nama" required value="<?= htmlspecialchars($inputNama, ENT_QUOTES, 'UTF-8') ?>" placeholder="Masukkan nama lengkap">
                </div>

                <div class="form-group">
                    <label for="email">Alamat Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($inputEmail, ENT_QUOTES, 'UTF-8') ?>" placeholder="nama@email.com">
                </div>

                <div class="form-group">
                    <label for="pesan">Pesan / Kesan <span class="required">*</span></label>
                    <textarea id="pesan" name="pesan" rows="3" required placeholder="Tuliskan pesan minimal 5 karakter..."><?= htmlspecialchars($inputPesan, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Kirim Pesan</button>
            </form>
        </div>
        <!-- Daftar Pesan / Kunjungan -->
        <div class="card">
            <h2 class="card-title">Daftar Pesan Masuk</h2>
            <div class="table-responsive">
                <?php if (empty($daftarPesan)): ?>
                    <div class="empty-state">Belum ada data buku tamu yang tercatat.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 30%;">Pengirim</th>
                                <th style="width: 45%;">Pesan</th>
                                <th style="width: 20%;">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftarPesan as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="meta-nama"><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="meta-email"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <div class="meta-pesan"><?= nl2br(htmlspecialchars($row['pesan'], ENT_QUOTES, 'UTF-8')) ?></div>
                                    </td>
                                    <td>
                                        <span class="meta-date"><?= htmlspecialchars($row['tanggal_kirim'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
