# SangattaStay — Platform Reservasi Hotel & Fasilitas Berbayar di Sangatta

**SangattaStay** adalah aplikasi web direktori dan sistem pemesanan akomodasi hotel serta akses fasilitas tambahan (*day-pass*) di kawasan Sangatta, Kabupaten Kutai Timur, Kalimantan Timur. Aplikasi ini memungkinkan pengguna untuk memesan kamar sekaligus memilih fasilitas tambahan sesuai kebutuhan (*Pay-As-You-Choose*), maupun memesan fasilitas hotel secara mandiri tanpa menginap.

🌐 **Live Demo:** [https://sangattastay.lovestoblog.com/](https://sangattastay.lovestoblog.com/)

---

## ✨ Fitur Utama

### 👤 Fitur Pengguna (Tamu & Pengunjung)
* **Direktori Hotel Sangatta:** Menampilkan daftar hotel mitra lengkap dengan lokasi, jarak dari pusat aktivitas, kategori bintang, rentang harga kamar, serta galeri foto (`index.php`, `detail-hotel.php`).
* **Reservasi Kamar Fleksibel:** Pemesanan kamar dengan opsi mencentang fasilitas berbayar tambahan yang hanya ingin digunakan selama menginap (`booking.php`).
* **Pemesanan Fasilitas Mandiri (*Day-Pass*):** Akses pemesanan fasilitas hotel (seperti kolam renang, sarapan *buffet*, *gym*, *spa & massage*, ruang pertemuan, dan *kids zone*) tanpa harus melakukan *check-in* kamar (`pesan-fasilitas.php`).
* **Sistem Pembayaran Digital (QRIS & Midtrans):** Mendukung pembuatan kode QRIS otomatis serta integrasi notifikasi pembayaran *real-time* (`qris-image.php`, `midtrans-notification.php`, `status-pembayaran.php`).
* **Pelacakan Pesanan (*Order Tracking*):** Pengecekan status reservasi kamar dan tiket fasilitas secara mandiri (`track-reservasi.php`, `track-fasilitas.php`).
* **Autentikasi Pengguna:** Fitur registrasi, masuk (*login*), dan keluar (*logout*) untuk mengelola riwayat transaksi (`register.php`, `login.php`, `logout.php`).

### 🛠️ Fitur Administrator (Panel Admin)
* **Dashboard & Statistik:** Ringkasan performa transaksi, pendapatan, dan pemesanan kamar maupun fasilitas (`admin-dashboard.php`, `admin-stats.php`, `admin-transaksi-stats.php`).
* **Manajemen Data Hotel & Kamar:** Pengelolaan profil hotel, tipe kamar, dan paket menginap (`admin-hotel.php`, `admin-paket.php`).
* **Manajemen Fasilitas Berbayar:** Pengaturan katalog fasilitas tambahan beserta tarif dan jam operasionalnya (`admin-fasilitas.php`).
* **Manajemen Transaksi & Reservasi:** Verifikasi pembayaran, pembaruan status reservasi kamar, pemesanan fasilitas, serta dukungan aksi massal (*bulk action*) (`admin-reservasi.php`, `admin-pemesanan-fasilitas.php`, `admin-transaksi.php`, `admin-bulk-action.php`).
* **Manajemen Konten (CMS):** Pengaturan bagian *Hero*, galeri foto, testimoni pelanggan, FAQ, informasi kontak, halaman tentang kami, dan *footer* (`admin-hero.php`, `admin-galeri.php`, `admin-testimoni.php`, `admin-faq.php`, `admin-kontak.php`, `admin-tentang.php`, `admin-footer.php`).

---

## 🏨 Daftar Hotel Mitra Terdaftar

1. **Royal Victoria Hotel Sangatta** — Jl. Pendidikan No. 1, Teluk Lingga, Sangatta Utara *(Mulai Rp 450.000/malam | 15 fasilitas opsional)*
2. **Hotel Amar Sangatta** — Jl. Poros Kabo Jaya, Swarga Bara, Sangatta Utara *(Mulai Rp 380.000/malam | 13 fasilitas opsional)*
3. **Hotel Mesfa Mulia Sangatta** — Jl. Yos Sudarso II No. 18, Sangatta Utara *(Mulai Rp 450.000/malam | 13 fasilitas opsional)*
4. **Q Hotel Sangatta** — Jl. APT Pranoto, Sangatta Utara, Kutai Timur *(Mulai Rp 450.000/malam | 14 fasilitas opsional)*

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** PHP Native
* **Database:** MySQL / MariaDB (`database.sql`)
* **Frontend:** HTML5, CSS3 (`luxury-theme.css`, `sangatta-stay.css`), JavaScript (`admin-crud.js`, `luxury-motion.js`)
* **UI Framework & Library:** Bootstrap 5, Bootstrap Icons, AOS (Animate On Scroll), Swiper.js, GLightbox, PureCounter
* **Payment Gateway:** Integrasi Midtrans & Generator QRIS

---

## 📁 Struktur Direktori

```text
SangattaStay/
├── assets/
│   ├── css/                # Stylesheet kustom (luxury-theme, sangatta-stay, main)
│   ├── fasilitas/          # Aset gambar fasilitas (pool, breakfast, gym, ballroom, spa, dll.)
│   ├── hotels/             # Aset gambar utama masing-masing hotel mitra
│   ├── img/                # Logo & identitas visual aplikasi
│   ├── js/                 # Skrip interaksi frontend & CRUD admin
│   ├── kamar/              # Aset gambar tipe kamar (Superior Twin, Deluxe King, Executive Suite)
│   └── vendor/             # Library pihak ketiga (Bootstrap, AOS, Swiper, GLightbox, dll.)
├── forms/                  # Handler pemrosesan formulir (reservasi, pesan-fasilitas, buat-qris)
├── admin-*.php             # Modul halaman dan aksi panel administrator
├── booking.php             # Halaman proses pemesanan kamar
├── config.php              # Konfigurasi koneksi database & pengaturan sistem
├── database.sql            # Skema dan data awal database MySQL
├── detail-hotel.php        # Halaman rincian hotel, kamar, dan fasilitas
├── index.php               # Halaman utama (Landing Page)
├── login.php               # Halaman masuk pengguna
├── midtrans-notification.php # Webhook notifikasi transaksi Midtrans
├── pesan-fasilitas.php     # Halaman pemesanan fasilitas mandiri
├── qris-image.php          # Generator tampilan QRIS pembayaran
├── register.php            # Halaman pendaftaran akun pengguna
├── status-pembayaran.php   # Halaman pengecekan status pembayaran
├── track-fasilitas.php     # Pelacakan tiket pemesanan fasilitas
└── track-reservasi.php     # Pelacakan kode reservasi kamar
