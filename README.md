# Pistachio Prestige 🍓

Website pemesanan dessert **Pistachio Prestige** — Strawberry Chocolate Kunafa Artisanal Boutique.

Dibuat khusus untuk lingkungan sekolah dengan sistem pemesanan, pembayaran (QRIS, Transfer Bank, COD), dan riwayat beli.

## Fitur
- 🛒 Katalog 4 menu produk dengan filter kategori
- 🔐 Sistem login & register (wajib sebelum checkout)
- 💳 Checkout dengan 3 metode pembayaran (QRIS, Transfer, COD di Sekolah)
- 📸 Upload bukti pembayaran
- 📋 Riwayat pembelian per akun
- 📍 Alamat pengantaran khusus lingkungan sekolah (Kelas / Ruangan)
- 🛡️ Panel Admin & Dapur (`admin.php`) untuk kelola pesanan, bukti transfer, dan cetak struk

## Teknologi
- PHP (vanilla)
- Tailwind CSS (CDN)
- Font Awesome 6
- MySQL (via XAMPP)
- localStorage untuk keranjang belanja

## Setup Lokal
1. Clone repo ini ke folder `htdocs` XAMPP:
   ```bash
   git clone https://github.com/USERNAME/pistachio-prestige.git ekskul
   ```
2. Buat file `koneksi.php` di root folder:
   ```php
   <?php
   $host = "localhost";
   $user = "root";
   $pass = "";  // sesuaikan password MySQL Anda
   $db   = "login";
   mysqli_report(MYSQLI_REPORT_OFF);
   $conn = @mysqli_connect($host, $user, $pass, $db);
   $koneksi = $conn;
   ?>
   ```
3. Buat database `login` di phpMyAdmin dan tabel `users`:
   ```sql
   CREATE TABLE users (
     id INT AUTO_INCREMENT PRIMARY KEY,
     username VARCHAR(100) NOT NULL UNIQUE,
     password VARCHAR(255) NOT NULL,
     nama_lengkap VARCHAR(100),
     no_telp VARCHAR(30),
     lokasi_sekolah VARCHAR(255),
     catatan_lokasi VARCHAR(255)
   );
   ```
4. Buka `http://localhost/ekskul/` di browser.

## Catatan
- File `koneksi.php` tidak disertakan di repo karena berisi kredensial database.
- Folder `uploads/proofs/` dan `uploads/orders.json` tidak disertakan karena berisi data pribadi pengguna.
