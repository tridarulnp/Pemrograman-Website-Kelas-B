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
