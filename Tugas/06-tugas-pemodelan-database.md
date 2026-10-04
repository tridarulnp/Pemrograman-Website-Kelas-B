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

<!-- 2. Identifikasi Seluruh Atribut, Primary Key (PK), dan Foreign Key (FK) -->

# 2. Identifikasi Entitas, Atribut, Primary Key (PK), dan Foreign Key (FK)

Pemberian skema atribut disusun menggunakan konvensi penamaan standar database relasional (`snake_case`) dengan klasifikasi integritas kunci:

### 2.1 Entitas: `mahasiswa`
* **Deskripsi**: Menyimpan data identitas akademik anggota perpustakaan.
* **Atribut**:
  - `nim` (**Primary Key**): Nomor Induk Mahasiswa unik sebagai pengidentifikasi tunggal.
  - `nama_mahasiswa`: Nama lengkap mahasiswa.
  - `jurusan`: Program studi / jurusan akademik mahasiswa.
  - `no_telepon`: Nomor kontak aktif mahasiswa untuk keperluan notifikasi peminjaman/keterlambatan.

### 2.2 Entitas: `penerbit`
* **Deskripsi**: Menyimpan data identitas dan kontak legal penerbit buku.
* **Atribut**:
  - `id_penerbit` (**Primary Key**): Kode unik pengenal penerbit (misal: `PUB-001`).
  - `nama_penerbit`: Nama badan hukum / lembaga penerbitan.
  - `alamat_penerbit`: Alamat kantor operasional penerbit.
  - `email_penerbit`: Alamat surat elektronik resmi penerbit.

### 2.3 Entitas: `buku`
* **Deskripsi**: Menyimpan katalog master pustaka buku.
* **Atribut**:
  - `id_buku` (**Primary Key**): Kode identifikasi buku / barcode / ISBN unik.
  - `id_penerbit` (**Foreign Key** merujuk ke `penerbit.id_penerbit`): Kunci relasi ke tabel penerbit.
  - `judul_buku`: Judul literatur / buku lengkap.
  - `tahun_terbit`: Tahun publikasi edisi buku.
  - `stok`: Jumlah kuantitas eksemplar fisik yang tersedia di perpustakaan.

### 2.4 Entitas: `peminjaman`
* **Deskripsi**: Menyimpan informasi umum faktur / nota peminjaman (*Header Transaction*).
* **Atribut**:
  - `id_peminjaman` (**Primary Key**): Nomor registrasi transaksi peminjaman (misal: `TRX-2026-0001`).
  - `nim` (**Foreign Key** merujuk ke `mahasiswa.nim`): Kunci relasi mahasiswa peminjam.
  - `tgl_pinjam`: Tanggal efektif peminjaman dilakukan.
  - `tgl_jatuh_tempo`: Batas waktu tanggal pengembalian buku yang disepakati.

### 2.5 Entitas: `detail_peminjaman`
* **Deskripsi**: Menyimpan item detail buku yang dipinjam serta melacak status pengembalian (*Line Item Transaction*).
* **Atribut**:
  - `id_peminjaman` (**Composite Primary Key**, **Foreign Key** merujuk ke `peminjaman.id_peminjaman`): Referensi transaksi induk.
  - `id_buku` (**Composite Primary Key**, **Foreign Key** merujuk ke `buku.id_buku`): Referensi katalog buku yang dipinjam.
  - `tgl_kembali`: Tanggal riil buku dikembalikan secara fisik (bernilai `NULL` selama status buku masih dipinjam).
  - `denda`: Nominal denda finansial yang dihitung jika `tgl_kembali > tgl_jatuh_tempo` (default `0.00`).

--- 

