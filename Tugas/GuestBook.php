<?php
class GuestBook {
    private PDO $pdo;

    /**
     * Konstruktor untuk inisialisasi koneksi PDO
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Menyimpan data pesan baru ke tabel buku_tamu menggunakan Prepared Statements.
     * Mencegah SQL Injection dengan parameter binding.
     *
     * @param string $nama
     * @param string $email
     * @param string $pesan
     * @return bool
     */
    public function tambahPesan(string $nama, string $email, string $pesan): bool {
        $sql = "INSERT INTO buku_tamu (nama, email, pesan, tanggal_kirim) VALUES (:nama, :email, :pesan, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':pesan' => $pesan
        ]);
    }

    /**
     * Mengambil seluruh daftar pesan buku tamu terurut dari yang terbaru.
     * Menggunakan Prepared Statements untuk query SELECT.
     *
     * @return array
     */
   
    public function ambilSemuaPesan(): array {
        $sql = "SELECT id, nama, email, pesan, tanggal_kirim FROM buku_tamu ORDER BY tanggal_kirim DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
