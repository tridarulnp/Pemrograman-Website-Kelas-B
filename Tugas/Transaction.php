<?php

declare(strict_types=1);

// ## 1 Kelas Transaction: konstanta, constructor property promotion, getter
final class Transaction
{
    public const ALLOWED_TYPES = ['deposit', 'withdraw'];

    // Batas atas agar konversi ke sen tetap akurat
    public const MAX_AMOUNT = 1_000_000_000_000.0;

    public function __construct(
        private string $id,
        private string $type,
        private float $amount
    ) {
        // Dibulatkan sebelum dicek agar nilai seperti 0.004 tidak lolos lalu menjadi 0
        $this->amount = round($this->amount, 2);

        if ($this->amount <= 0) {
            throw new InvalidArgumentException(
                'Jumlah transaksi harus berupa angka desimal positif minimal Rp 0,01.'
            );
        }

        if ($this->amount > self::MAX_AMOUNT) {
            throw new InvalidArgumentException(sprintf(
                'Jumlah transaksi maksimal Rp %s.',
                number_format(self::MAX_AMOUNT, 2, ',', '.')
            ));
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    } 

// ## 2 Metode process: deposit, penarikan, dan cek saldo
    public function process(): float
    {
        $this->startSession();

        if (!isset($_SESSION['balance'])) {
            $_SESSION['balance'] = 0.0;
        }

        // Dihitung dalam sen (integer) karena float tidak presisi, misalnya 0.3 - 0.1 = 0.19999999999999998
        $currentCents = self::toCents((float) $_SESSION['balance']);
        $amountCents = self::toCents($this->amount);

        $newCents = match ($this->type) {
            'deposit'  => $currentCents + $amountCents,
            'withdraw' => $this->withdrawFrom($currentCents, $amountCents),
            default    => throw new InvalidArgumentException(
                'Jenis transaksi tidak dikenali.'
            ),
        };

        $newBalance = $newCents / 100;

        $_SESSION['balance'] = $newBalance;
        $_SESSION['transactions'][] = [
            'id'            => $this->id,
            'type'          => $this->type,
            'amount'        => $this->amount,
            'balance_after' => $newBalance,
            'created_at'    => date('d/m/Y H:i:s'),
        ];

        return $newBalance;
    }

    private function withdrawFrom(int $currentCents, int $amountCents): int
    {
        if ($amountCents > $currentCents) {
            throw new RuntimeException(sprintf(
                'Penarikan ditolak: saldo tersisa Rp %s, sedangkan jumlah yang diminta Rp %s.',
                number_format($currentCents / 100, 2, ',', '.'),
                number_format($amountCents / 100, 2, ',', '.')
            ));
        }

        return $currentCents - $amountCents;
    }

    private static function toCents(float $value): int
    {
        return (int) round($value * 100);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
// ## 1
}
