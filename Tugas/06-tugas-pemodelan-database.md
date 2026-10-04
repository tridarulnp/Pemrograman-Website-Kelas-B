# DOKUMEN PERANCANGAN BASIS DATA (DATABASE DESIGN DOCUMENT)
**Sistem Informasi Pengelolaan Perpustakaan Terintegrasi (SIPUSTAKA)**

---

<!-- 1. Desain ERD Logis -->

# 1. Desain ERD Logis (Logical Entity Relationship Design)

## 1.1 Deskripsi Entitas Utama
Perancangan basis data logis sistem perpustakaan mengadopsi model relasional dengan entitas terstruktur untuk menjamin integritas data referensial dan efisiensi transaksi:

1. **Mahasiswa (`mahasiswa`)**: Entitas master pengguna (anggota perpustakaan) yang memiliki hak akses untuk melakukan transaksi peminjaman buku.
2. **Penerbit (`penerbit`)**: Entitas master yang mengelola informasi badan usaha atau lembaga yang menerbitkan karya literatur/buku.
3. **Buku (`buku`)**: Entitas master katalog literatur yang mencakup metadata bibliografi, identitas buku, serta kapasitas inventaris fisik (stok).
4. **Peminjaman (`peminjaman`)**: Entitas transaksi (*Header/Master Transaction*) yang merekam satu sesi peminjaman oleh seorang mahasiswa pada waktu tertentu.
5. **Detail Peminjaman (`detail_peminjaman`)**: Entitas asosiatif/transaksi (*Detail/Line Item Transaction*) yang menyelesaikan dekomposisi relasi *Many-to-Many* (1:N dan M:N) antara entitas `peminjaman` dan `buku`. Entitas ini melacak status individual tiap eksemplar buku, waktu pengembalian riil, serta kalkulasi sanksi denda keterlambatan.

---

## 1.2 Kardinalitas & Analisis Hubungan Antar Entitas (Relationship Analysis)

| No | Entitas Asal | Hubungan | Entitas Tujuan | Kardinalitas | Partisipasi (Modality) | Deskripsi Semantik Bisnis |
| :---: | :--- | :---: | :--- | :---: | :---: | :--- |
| **1** | `penerbit` | *menerbitkan* | `buku` | **1 : N (One-to-Many)** | Mandatory ke Optional | Satu penerbit dapat menerbitkan banyak judul buku (0..N). Setiap buku wajib diterbitkan oleh tepat satu penerbit (1..1). |
| **2** | `mahasiswa` | *melakukan* | `peminjaman` | **1 : N (One-to-Many)** | Optional ke Mandatory | Satu mahasiswa dapat memiliki 0 atau banyak riwayat transaksi peminjaman (0..N). Setiap transaksi peminjaman wajib dimiliki oleh tepat satu mahasiswa (1..1). |
| **3** | `peminjaman` | *memuat* | `detail_peminjaman` | **1 : N (One-to-Many)** | Mandatory ke Mandatory | Satu nota transaksi peminjaman wajib memuat minimal 1 atau beberapa eksemplar buku (1..N). Setiap record detail merujuk pada tepat satu nota peminjaman induk (1..1). |
| **4** | `buku` | *dicatat pada* | `detail_peminjaman` | **1 : N (One-to-Many)** | Optional ke Mandatory | Satu judul buku dapat dipinjam berkali-kali pada transaksi berbeda (0..N). Setiap entri detail wajib mereferensikan satu katalog buku tertentu (1..1). |

---

