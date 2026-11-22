# Catatan perbaikan gambar SangattaStay

Paket ini memperbaiki file gambar kosong (0 byte) dan mengarahkan seed data serta migrasi database ke aset lokal yang tersedia.

## Penting tentang keaslian foto

Foto eksterior hotel dan foto kamar/fasilitas yang ditambahkan ke folder `assets/` adalah **gambar ilustrasi hasil generasi**, bukan dokumentasi foto resmi masing-masing properti. Karena itu, jangan menganggap gambar tersebut sebagai foto aktual Royal Victoria, Mesfa Mulia, Q Hotel, atau Hotel Amar. Ganti dengan foto resmi/berizin dari masing-masing hotel sebelum website dipublikasikan untuk pengguna umum.

Referensi pencarian foto aktual yang ditemukan:
- Royal Victoria Hotel: https://www.hotelroyalgroup.com/ dan https://www.traveloka.com/id-id/hotel/indonesia/royal-victoria-hotel-3000010028235
- Q Hotel Sangatta: https://www.traveloka.com/id-id/hotel/indonesia/q-hotel-9000005833836
- Untuk Mesfa Mulia, pencarian publik belum menghasilkan galeri foto resmi yang cukup jelas; untuk Hotel Amar juga belum ditemukan sumber foto resmi yang dapat diverifikasi.

## Pemasangan

1. Ekstrak ZIP ini dan salin folder `www` ke lokasi proyek (buat cadangan folder lama terlebih dahulu).
2. Jika database sudah terisi, buka phpMyAdmin, pilih database `sangatta_stay_db`, lalu impor `perbaikan-gambar.sql` satu kali.
3. Jika membuat database baru dari nol, gunakan `database.sql` yang sudah diperbarui.
4. Buka website dan periksa halaman daftar hotel, detail hotel, kamar, dan fasilitas.

File `perbaikan-gambar.sql` hanya memperbarui kolom foto, tidak menghapus atau mengubah data reservasi, pembayaran, maupun akun.
