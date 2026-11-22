USE `if0_43504821_sangatta_stay_db`;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kata Sandi default: admin123
INSERT IGNORE INTO admin_users (username, password, nama_lengkap) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator SangattaStay');

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    no_hp VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS testimoni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    testimoni TEXT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    tanggal DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO testimoni (nama, testimoni, rating, tanggal)
SELECT 'Rina Pratiwi', 'Proses memilih hotel dan fasilitas sangat mudah. Informasi kamar, lokasi, dan harga ditampilkan dengan jelas.', 5, '2026-11-20'
WHERE NOT EXISTS (SELECT 1 FROM testimoni WHERE nama = 'Rina Pratiwi');
INSERT INTO testimoni (nama, testimoni, rating, tanggal)
SELECT 'Andi Saputra', 'Pilihan fasilitas berbayar membantu saya menyesuaikan anggaran perjalanan tanpa harus membayar seluruh paket.', 5, '2026-11-18'
WHERE NOT EXISTS (SELECT 1 FROM testimoni WHERE nama = 'Andi Saputra');
INSERT INTO testimoni (nama, testimoni, rating, tanggal)
SELECT 'Dewi Lestari', 'SangattaStay memudahkan saya membandingkan hotel dan langsung melanjutkan ke proses pemesanan.', 5, '2026-11-15'
WHERE NOT EXISTS (SELECT 1 FROM testimoni WHERE nama = 'Dewi Lestari');

CREATE TABLE IF NOT EXISTS kontak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    whatsapp VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    instagram VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO kontak (whatsapp, email, instagram)
SELECT '(0098) 5674', 'sangattastay1890@gmail.com', 'https://instagram.com/sangattastay'
WHERE NOT EXISTS (SELECT 1 FROM kontak);

CREATE TABLE IF NOT EXISTS hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_hotel VARCHAR(150) NOT NULL,
    kecamatan ENUM('Sangatta Utara', 'Sangatta Selatan', 'Teluk Lingga', 'Swarga Bara') NOT NULL,
    alamat TEXT NOT NULL,
    bintang TINYINT DEFAULT 3,
    jarak_pusat VARCHAR(60) DEFAULT '1.2 km dari Pusat Kota',
    deskripsi TEXT,
    foto_utama VARCHAR(255) DEFAULT 'assets/img/home.jpeg',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS kamar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    nama_kamar VARCHAR(100) NOT NULL,
    harga_per_malam DECIMAL(12,2) NOT NULL,
    kapasitas INT DEFAULT 2,
    stok_kamar INT DEFAULT 5,
    fasilitas_kamar TEXT,
    extra_bed_tersedia TINYINT(1) NOT NULL DEFAULT 1,
    harga_extra_bed DECIMAL(12,2) NOT NULL DEFAULT 150000,
    foto VARCHAR(255) DEFAULT 'assets/kamar/deluxe-king.jpg',
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Fasilitas berbayar opsional yang dipilih saat menginap (kolam renang, sarapan, gym, playground, spa, dll.)
CREATE TABLE IF NOT EXISTS fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    nama_fasilitas VARCHAR(100) NOT NULL,
    kategori VARCHAR(80) NOT NULL,
    harga_fasilitas DECIMAL(12,2) NOT NULL,
    satuan VARCHAR(30) DEFAULT 'per orang',
    jam_operasional VARCHAR(50) DEFAULT '07:00 - 21:00 WITA',
    deskripsi TEXT,
    foto VARCHAR(255) DEFAULT 'assets/fasilitas/meeting-room.jpg',
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Reservasi menginap + fasilitas berbayar pilihan tamu
CREATE TABLE IF NOT EXISTS reservasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_booking VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    nama_tamu VARCHAR(120) NOT NULL,
    no_hp VARCHAR(25) NOT NULL,
    email VARCHAR(120) NULL,
    kamar_id INT NOT NULL,
    jumlah_kamar INT NOT NULL DEFAULT 1,
    tanggal_checkin DATE NOT NULL,
    tanggal_checkout DATE NOT NULL,
    total_malam INT NOT NULL DEFAULT 1,
    subtotal_kamar DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal_fasilitas DECIMAL(12,2) NOT NULL DEFAULT 0,
    jumlah_extra_bed INT NOT NULL DEFAULT 0,
    subtotal_extra_bed DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_harga DECIMAL(12,2) NOT NULL,
    metode_bayar ENUM('QRIS', 'Transfer Bank', 'Bayar di Resepsionis') DEFAULT 'QRIS',
    status_pembayaran ENUM('pending', 'lunas', 'selesai', 'batal') DEFAULT 'pending',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reservasi_fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservasi_id INT NOT NULL,
    fasilitas_id INT NOT NULL,
    jumlah_pax INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (reservasi_id) REFERENCES reservasi(id) ON DELETE CASCADE,
    FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pemesanan_fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_booking VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    fasilitas_id INT NOT NULL,
    nama_tamu VARCHAR(120) NOT NULL,
    no_hp VARCHAR(25) NOT NULL,
    tanggal_kunjungan DATE NOT NULL,
    jumlah_pax INT NOT NULL DEFAULT 1,
    total_harga DECIMAL(12,2) NOT NULL,
    metode_bayar ENUM('QRIS', 'Transfer Bank', 'Bayar di Lokasi') DEFAULT 'QRIS',
    status_pembayaran ENUM('pending', 'lunas', 'batal') DEFAULT 'pending',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_type ENUM('reservation', 'facility') NOT NULL,
    booking_code VARCHAR(30) NOT NULL,
    order_id VARCHAR(64) NOT NULL UNIQUE,
    transaction_id VARCHAR(100) NULL,
    gross_amount DECIMAL(12,2) NOT NULL,
    qr_code_url TEXT NOT NULL,
    transaction_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    expiry_time DATETIME NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payment_booking (order_type, booking_code),
    KEY idx_payment_status (transaction_status)
);

INSERT INTO hotels (nama_hotel, kecamatan, alamat, bintang, jarak_pusat, deskripsi, foto_utama) VALUES
('Royal Victoria Hotel Sangatta', 'Teluk Lingga', 'Jl. Pendidikan No. 1, Teluk Lingga, Sangatta Utara', 4, '0.8 km dari Pusat Kota', 'Hotel bintang 4 di Sangatta dengan kamar modern dan pilihan fasilitas berbayar seperti kolam renang, sarapan buffet, dan gym.', 'assets/hotels/royal-victoria.jpg'),
('Hotel Mesfa Mulia Sangatta', 'Sangatta Utara', 'Jl. Yos Sudarso II No. 18, Sangatta Utara', 3, '0.5 km dari Pusat Bisnis', 'Akomodasi strategis di pusat keramaian Yos Sudarso dengan kamar nyaman serta paket fasilitas sarapan & area pertemuan.', 'assets/hotels/mesfa-mulia.jpg'),
('Q Hotel Sangatta', 'Sangatta Utara', 'Jl. APT Pranoto, Sangatta Utara, Kutai Timur', 3, '1.2 km dari Taman Bersemi', 'Hotel modern di Sangatta dengan tipe kamar lengkap dan fasilitas berbayar seperti gym, kids play zone, dan lounge.', 'assets/hotels/q-hotel.jpg'),
('Hotel Amar Sangatta', 'Swarga Bara', 'Jl. Poros Kabo Jaya, Swarga Bara, Sangatta Utara', 3, '2.1 km dari Area KPC', 'Penginapan asri di kawasan Swarga Bara dengan akses fasilitas kolam renang, sarapan, dan relaksasi.', 'assets/hotels/hotel-amar.jpg');

INSERT INTO kamar (hotel_id, nama_kamar, harga_per_malam, kapasitas, stok_kamar, fasilitas_kamar, foto) VALUES
(1, 'Kamar Deluxe King', 650000, 2, 12, 'AC, WiFi 100Mbps, Smart TV 43", Coffee Maker, Air Hangat', 'assets/kamar/deluxe-king.jpg'),
(1, 'Suite Eksekutif', 1150000, 4, 4, 'Ruang Tamu Terpisah, Bathtub, Balcony, Smart TV, WiFi', 'assets/kamar/executive-suite.jpg'),
(2, 'Kamar Superior Twin', 450000, 2, 15, '2 Single Bed, AC, WiFi, Water Heater, Meja Kerja', 'assets/kamar/superior-twin.jpg'),
(3, 'Kamar Bisnis', 490000, 2, 10, 'Queen Bed, Meja Kerja Ergonomis, High-Speed WiFi, Smart TV', 'assets/kamar/deluxe-king.jpg'),
(4, 'Kamar Standar Nyaman', 380000, 2, 8, 'AC, TV Kabel, WiFi, Parkir Luas', 'assets/kamar/superior-twin.jpg');

INSERT INTO fasilitas (hotel_id, nama_fasilitas, kategori, harga_fasilitas, satuan, jam_operasional, deskripsi, foto) VALUES
(1, 'Swimming Pool', 'Kolam Renang', 50000, 'per orang', '07:00 - 19:00 WITA', 'Akses kolam renang termasuk handuk & loker.', 'assets/fasilitas/pool.jpg'),
(1, 'Breakfast', 'Food & Beverage', 85000, 'per orang', '06:30 - 10:00 WITA', 'Sarapan buffet Nusantara & Western di restoran hotel.', 'assets/fasilitas/breakfast.jpg'),
(1, 'Gym / Fitness Center', 'Kebugaran', 50000, 'per orang', '06:00 - 21:00 WITA', 'Akses treadmill, beban, dan peralatan kardio.', 'assets/fasilitas/gym.jpg'),
(2, 'Breakfast', 'Food & Beverage', 55000, 'per orang', '06:30 - 09:30 WITA', 'Paket sarapan menu lokal dan kopi atau teh.', 'assets/fasilitas/breakfast.jpg'),
(3, 'Tiket Area Bermain Anak (Kids Zone)', 'Area Bermain', 45000, 'per anak', '08:00 - 18:00 WITA', 'Area bermain indoor aman dan edukatif untuk anak.', 'assets/fasilitas/kids.jpg'),
(4, 'Swimming Pool', 'Kolam Renang', 50000, 'per orang', '07:30 - 18:00 WITA', 'Akses kolam renang dan area duduk tepi kolam.', 'assets/fasilitas/pool.jpg');

INSERT INTO fasilitas (hotel_id, nama_fasilitas, kategori, harga_fasilitas, satuan, jam_operasional, deskripsi, foto)
SELECT h.id, d.nama_fasilitas, d.kategori, d.harga_fasilitas, d.satuan, d.jam_operasional, d.deskripsi, d.foto
FROM hotels h
CROSS JOIN (
   SELECT 'Breakfast' AS nama_fasilitas, 'Food & Beverage' AS kategori, 85000 AS harga_fasilitas, 'per orang' AS satuan, '06:30 - 10:00 WITA' AS jam_operasional, 'Pilihan sarapan untuk satu orang.' AS deskripsi, 'assets/fasilitas/breakfast.jpg' AS foto
   UNION ALL SELECT 'Lunch', 'Food & Beverage', 100000, 'per orang', '11:00 - 14:00 WITA', 'Paket makan siang untuk satu orang.', 'assets/fasilitas/breakfast.jpg'
   UNION ALL SELECT 'Dinner', 'Food & Beverage', 125000, 'per orang', '18:00 - 21:00 WITA', 'Paket makan malam untuk satu orang.', 'assets/fasilitas/breakfast.jpg'
   UNION ALL SELECT 'Swimming Pool', 'Kolam Renang', 50000, 'per orang', '07:00 - 19:00 WITA', 'Akses kolam renang untuk satu orang.', 'assets/fasilitas/pool.jpg'
   UNION ALL SELECT 'Paket Kolam + Makan & Minum', 'Paket Kolam Renang', 100000, 'per orang', '07:00 - 19:00 WITA', 'Akses kolam renang termasuk satu porsi makan dan minum.', 'assets/fasilitas/pool.jpg'
   UNION ALL SELECT 'Gym / Fitness Center', 'Kebugaran', 50000, 'per orang', '06:00 - 21:00 WITA', 'Akses gym dan fitness center untuk satu orang.', 'assets/fasilitas/gym.jpg'
   UNION ALL SELECT 'Spa & Massage', 'Spa & Relaksasi', 250000, 'per sesi', '10:00 - 22:00 WITA', 'Sesi spa dan massage relaksasi.', 'assets/fasilitas/spa-massage.jpg'
   UNION ALL SELECT 'Meeting Room', 'Meeting & Acara', 500000, 'per jam', '08:00 - 22:00 WITA', 'Sewa ruang meeting, tarif awal per jam.', 'assets/fasilitas/meeting-room.jpg'
   UNION ALL SELECT 'Laundry', 'Laundry', 25000, 'per item', '08:00 - 20:00 WITA', 'Layanan laundry, tarif awal per item.', 'assets/fasilitas/meeting-room.jpg'
   UNION ALL SELECT 'Romantic Dinner', 'Makan Romantis', 350000, 'per pasangan', '18:00 - 22:00 WITA', 'Paket makan malam romantis untuk satu pasangan.', 'assets/fasilitas/breakfast.jpg'
   UNION ALL SELECT 'Ballroom / Gedung Acara', 'Meeting & Acara', 5000000, 'per acara', '08:00 - 23:00 WITA', 'Sewa ballroom atau gedung acara, tarif awal per acara.', 'assets/fasilitas/ballroom.jpg'
   UNION ALL SELECT 'Sauna', 'Spa & Relaksasi', 75000, 'per orang', '10:00 - 21:00 WITA', 'Akses sauna untuk satu orang.', 'assets/fasilitas/sauna.jpg'
) d
WHERE h.status = 'aktif'
 AND h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')
 AND NOT EXISTS (
     SELECT 1 FROM fasilitas f
     WHERE f.hotel_id = h.id AND f.nama_fasilitas = d.nama_fasilitas
 );

 -- Sinkronisasi final nama aset untuk database lama maupun instalasi baru.
 UPDATE hotels SET foto_utama = CASE
   WHEN nama_hotel LIKE '%Royal Victoria%' THEN 'assets/hotels/royal-victoria.jpg'
   WHEN nama_hotel LIKE '%Mesfa Mulia%' THEN 'assets/hotels/mesfa-mulia.jpg'
   WHEN nama_hotel LIKE '%Q Hotel%' THEN 'assets/hotels/q-hotel.jpg'
   WHEN nama_hotel LIKE '%Hotel Amar%' THEN 'assets/hotels/hotel-amar.jpg'
   ELSE foto_utama
 END;

 UPDATE kamar SET foto = CASE
   WHEN nama_kamar LIKE '%Presidential%' THEN 'assets/kamar/deluxe-king.jpg'
   WHEN nama_kamar LIKE '%Deluxe King%' OR nama_kamar LIKE '%Superior King%' THEN 'assets/kamar/deluxe-king.jpg'
   WHEN nama_kamar LIKE '%Executive Suite%' OR nama_kamar LIKE '%Suite Eksekutif%' THEN 'assets/kamar/executive-suite.jpg'
   WHEN nama_kamar LIKE '%Superior Twin%' OR nama_kamar LIKE '%Deluxe Twin%' OR nama_kamar LIKE '%Twin%' THEN 'assets/kamar/superior-twin.jpg'
   WHEN nama_kamar LIKE '%Bisnis%' OR nama_kamar LIKE '%Business%' THEN 'assets/kamar/deluxe-king.jpg'
   WHEN nama_kamar LIKE '%Standar%' OR nama_kamar LIKE '%Standard%' THEN 'assets/kamar/superior-twin.jpg'
   ELSE foto
 END;

 UPDATE fasilitas SET foto = CASE
   WHEN nama_fasilitas LIKE '%Ballroom%' THEN 'assets/fasilitas/ballroom.jpg'
   WHEN nama_fasilitas LIKE '%Meeting Room%' THEN 'assets/fasilitas/meeting-room.jpg'
   WHEN nama_fasilitas LIKE '%Laundry%' THEN 'assets/fasilitas/meeting-room.jpg'
   WHEN nama_fasilitas LIKE '%Sauna%' THEN 'assets/fasilitas/sauna.jpg'
   WHEN nama_fasilitas LIKE '%Spa%' OR nama_fasilitas LIKE '%Massage%' THEN 'assets/fasilitas/spa-massage.jpg'
   WHEN nama_fasilitas LIKE '%Gym%' OR nama_fasilitas LIKE '%Fitness%' THEN 'assets/fasilitas/gym.jpg'
   WHEN nama_fasilitas LIKE '%Kids%' OR nama_fasilitas LIKE '%Bermain Anak%' THEN 'assets/fasilitas/kids.jpg'
   WHEN nama_fasilitas LIKE '%Swimming%' OR nama_fasilitas LIKE '%Kolam%' THEN 'assets/fasilitas/pool.jpg'
   WHEN nama_fasilitas LIKE '%Breakfast%' OR nama_fasilitas LIKE '%Lunch%' OR nama_fasilitas LIKE '%Dinner%' OR nama_fasilitas LIKE '%Makan%' THEN 'assets/fasilitas/breakfast.jpg'
   ELSE foto
 END;
