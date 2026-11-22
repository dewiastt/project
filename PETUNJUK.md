# SangattaStay

Aplikasi web informasi hotel, pemesanan kamar, dan fasilitas di Sangatta.

## Teknologi
- PHP 8 atau lebih baru
- MySQL/MariaDB
- Bootstrap 5 dan aset antarmuka yang tersedia di folder `assets/`

## Cara pemasangan di XAMPP
1. Salin folder `www` ke `C:\xampp\htdocs\www`.
2. Jalankan Apache dan MySQL melalui XAMPP.
3. Buka phpMyAdmin, lalu impor `database.sql`.
4. Periksa pengaturan basis data pada `config.php` (nama basis data bawaan: `sangatta_stay_db`, pengguna: `root`, kata sandi kosong).
5. Buka `http://localhost/www/`.

## Akun administrator bawaan
- Nama pengguna: `admin`
- Kata sandi awal: `admin123`

Segera ubah kata sandi administrator setelah berhasil masuk.

## Akun pengguna
Pengunjung dapat membuat akun melalui `register.php` atau masuk melalui `login.php`. Pengajuan reservasi mengharuskan pengguna masuk terlebih dahulu.

## Catatan
- Informasi hotel, harga, ketersediaan kamar, dan fasilitas harus diverifikasi sebelum dipublikasikan.
- Pembayaran QRIS memakai Midtrans Core API. Atur variabel lingkungan server `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION` (`false` untuk sandbox), dan `MIDTRANS_QRIS_ACQUIRER` (`gopay` sebagai nilai bawaan). Jangan menaruh Server Key di kode atau membagikannya melalui chat.
- Aktifkan QRIS pada akun Midtrans dan atur Payment Notification URL ke `https://domain-anda/midtrans-notification.php`. URL callback harus dapat diakses dari internet; Midtrans tidak dapat mengirim webhook ke `localhost`. Untuk uji lokal, gunakan tunnel HTTPS sementara.
- Status pembayaran QRIS hanya berubah menjadi lunas setelah notifikasi terverifikasi dan status transaksi dikonfirmasi kembali melalui API Midtrans. Nota/e-ticket dengan kode check-in tersedia setelah pembayaran lunas.
- Metode non-QRIS tetap mengikuti proses konfirmasi administrator.
- Gunakan HTTPS dan kata sandi administrator yang kuat saat aplikasi dipasang pada server publik.
