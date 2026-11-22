<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sangatta_stay_db');

function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Koneksi database gagal: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

$conn = getConnection();

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireMasuk() {
    if (!isLoggedIn()) {
        header("Location: admin-login.php");
        exit();
    }
}

function setError($msg) {
    $_SESSION['error_message'] = $msg;
}

function setSuccess($msg) {
    $_SESSION['success_message'] = $msg;
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function assetUrl($path) {
    $path = str_replace('\\', '/', trim((string)$path));
    if (!isLocalImageUsable($path)) {
        return '';
    }
    $mtime = @filemtime(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    return htmlspecialchars($path) . ($mtime ? '?v=' . $mtime : '');
}

function ensureAssetMappings() {
    $db = getConnection();

    $db->query("UPDATE hotels SET foto_utama = CASE
        WHEN nama_hotel LIKE '%Royal Victoria%' THEN 'assets/hotels/royal-victoria.jpg'
        WHEN nama_hotel LIKE '%Mesfa Mulia%' THEN 'assets/hotels/mesfa-mulia.jpg'
        WHEN nama_hotel LIKE '%Q Hotel%' THEN 'assets/hotels/q-hotel.jpg'
        WHEN nama_hotel LIKE '%Hotel Amar%' THEN 'assets/hotels/hotel-amar.jpg'
        ELSE foto_utama
    END");

    $db->query("UPDATE kamar SET foto = CASE
        WHEN nama_kamar LIKE '%Presidential%' THEN 'assets/kamar/deluxe-king.jpg'
        WHEN nama_kamar LIKE '%Deluxe King%' OR nama_kamar LIKE '%Superior King%' THEN 'assets/kamar/deluxe-king.jpg'
        WHEN nama_kamar LIKE '%Executive Suite%' OR nama_kamar LIKE '%Suite Eksekutif%' THEN 'assets/kamar/executive-suite.jpg'
        WHEN nama_kamar LIKE '%Superior Twin%' OR nama_kamar LIKE '%Deluxe Twin%' OR nama_kamar LIKE '%Twin%' THEN 'assets/kamar/superior-twin.jpg'
        WHEN nama_kamar LIKE '%Bisnis%' OR nama_kamar LIKE '%Business%' THEN 'assets/kamar/deluxe-king.jpg'
        WHEN nama_kamar LIKE '%Standar%' OR nama_kamar LIKE '%Standard%' THEN 'assets/kamar/superior-twin.jpg'
        ELSE foto
    END");

    $db->query("UPDATE fasilitas SET foto = CASE
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
    END");
}

function ensureKontakTable() {
    $db = getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS kontak (
        id INT AUTO_INCREMENT PRIMARY KEY,
        whatsapp VARCHAR(50) NOT NULL,
        email VARCHAR(150) NOT NULL,
        instagram VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$db->query($sql)) {
        die("Gagal menyiapkan tabel kontak: " . $db->error);
    }

    $count = $db->query("SELECT COUNT(*) AS total FROM kontak")->fetch_assoc()['total'];
    if ((int)$count === 0) {
        $stmt = $db->prepare("INSERT INTO kontak (whatsapp, email, instagram) VALUES (?, ?, ?)");
        $whatsapp = '(0098) 5674';
        $email = 'sangattastay1890@gmail.com';
        $instagram = 'https://instagram.com/sangattastay';
        $stmt->bind_param('sss', $whatsapp, $email, $instagram);
        $stmt->execute();
        $stmt->close();
    }
}

function ensureTestimoniTable() {
    $db = getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS testimoni (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(120) NOT NULL,
        testimoni TEXT NOT NULL,
        tanggal DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$db->query($sql)) {
        die("Gagal menyiapkan tabel testimoni: " . $db->error);
    }

    $ratingColumn = $db->query("SHOW COLUMNS FROM testimoni LIKE 'rating'");
    if ($ratingColumn && $ratingColumn->num_rows === 0) {
        $db->query("ALTER TABLE testimoni ADD COLUMN rating TINYINT NOT NULL DEFAULT 5 AFTER testimoni");
    }

    $fotoColumn = $db->query("SHOW COLUMNS FROM testimoni LIKE 'foto'");
    if ($fotoColumn && $fotoColumn->num_rows > 0) {
        $db->query("ALTER TABLE testimoni MODIFY COLUMN foto VARCHAR(255) NULL DEFAULT NULL");
    }

    $count = $db->query("SELECT COUNT(*) AS total FROM testimoni")->fetch_assoc()['total'];
    if ((int)$count === 0) {
        $db->query("
            INSERT INTO testimoni (nama, testimoni, tanggal) VALUES
            ('Rina Pratiwi', 'Proses memilih hotel dan fasilitas sangat mudah. Informasi kamar, lokasi, dan harga ditampilkan dengan jelas.', '2026-11-20'),
            ('Andi Saputra', 'Pilihan fasilitas berbayar membantu saya menyesuaikan anggaran perjalanan tanpa harus membayar seluruh paket.', '2026-11-18'),
            ('Dewi Lestari', 'SangattaStay memudahkan saya membandingkan hotel dan langsung melanjutkan ke proses pemesanan.', '2026-11-15')
        ");
    }
}

function isLocalImageUsable($path) {
    $path = str_replace('\\', '/', trim((string)$path));
    if ($path === '' || strpos($path, 'assets/') !== 0 || strpos($path, '..') !== false
        || !preg_match('/\.(jpe?g|png|webp|svg)$/i', $path)) {
        return false;
    }

    $assetsRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'assets');
    $imagePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    if ($assetsRoot === false || $imagePath === false
        || strpos($imagePath, $assetsRoot . DIRECTORY_SEPARATOR) !== 0
        || !is_file($imagePath)) {
        return false;
    }

    return filesize($imagePath) > 0;
}

function generateKodeBooking() {
    return 'SGT-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

/* Fungsi akun pengguna */
function ensureUsersTable() {
    $db = getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_lengkap VARCHAR(120) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        no_hp VARCHAR(30) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$db->query($sql)) {
        die("Gagal menyiapkan tabel pengguna: " . $db->error);
    }

    ensureReservasiUserColumn();
    ensurePemesananFasilitasTable();
    ensurePaymentTransactionsTable();
    ensureExtraBedColumns();
    ensureDefaultRoomTypes();
    ensureFacilityCategoryColumn();
}

function ensureReservasiUserColumn() {
    $db = getConnection();
    $result = $db->query("SHOW COLUMNS FROM reservasi LIKE 'user_id'");

    if ($result && $result->num_rows === 0) {
        if (!$db->query("ALTER TABLE reservasi ADD COLUMN user_id INT NULL AFTER kode_booking")) {
            die("Gagal menambahkan kolom user_id pada tabel reservasi: " . $db->error);
        }

        $db->query("UPDATE reservasi r
                    JOIN users u ON u.email = r.email
                    SET r.user_id = u.id");
    }
}

function ensureExtraBedColumns() {
    $db = getConnection();

    $kamarColumns = [
        'extra_bed_tersedia' => "TINYINT(1) NOT NULL DEFAULT 1",
        'harga_extra_bed' => "DECIMAL(12,2) NOT NULL DEFAULT 150000"
    ];
    foreach ($kamarColumns as $column => $definition) {
        $result = $db->query("SHOW COLUMNS FROM kamar LIKE '$column'");
        if ($result && $result->num_rows === 0) {
            $db->query("ALTER TABLE kamar ADD COLUMN $column $definition");
        }
    }

    $reservasiColumns = [
        'jumlah_extra_bed' => "INT NOT NULL DEFAULT 0",
        'subtotal_extra_bed' => "DECIMAL(12,2) NOT NULL DEFAULT 0"
    ];
    foreach ($reservasiColumns as $column => $definition) {
        $result = $db->query("SHOW COLUMNS FROM reservasi LIKE '$column'");
        if ($result && $result->num_rows === 0) {
            $db->query("ALTER TABLE reservasi ADD COLUMN $column $definition");
        }
    }
}

function ensurePemesananFasilitasTable() {
    $db = getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS pemesanan_fasilitas (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$db->query($sql)) {
        die("Gagal menyiapkan tabel pemesanan fasilitas: " . $db->error);
    }
}

function ensurePaymentTransactionsTable() {
    $db = getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS payment_transactions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$db->query($sql)) {
        die("Gagal menyiapkan tabel transaksi pembayaran: " . $db->error);
    }
}

function midtransApiRequest($method, $path, $payload = null) {
    $serverKey = getenv('MIDTRANS_SERVER_KEY');
    if (!$serverKey) {
        throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi pada server.');
    }

    $isProduction = filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    $baseUrl = $isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    $curl = curl_init($baseUrl . $path);
    if ($curl === false) {
        throw new RuntimeException('Tidak dapat memulai koneksi ke Midtrans.');
    }

    $headers = ['Accept: application/json'];
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_USERPWD => $serverKey . ':',
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => $headers
    ]);

    if ($payload !== null) {
        $json = json_encode($payload);
        if ($json === false) {
            curl_close($curl);
            throw new RuntimeException('Data transaksi tidak dapat disiapkan untuk Midtrans.');
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($curl);
    $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        throw new RuntimeException('Koneksi Midtrans gagal: ' . $curlError);
    }

    $data = json_decode($response, true);
    if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($data)) {
        $message = is_array($data) ? ($data['status_message'] ?? 'Permintaan ditolak.') : 'Respons Midtrans tidak valid.';
        throw new RuntimeException('Midtrans mengembalikan HTTP ' . $httpStatus . ': ' . $message);
    }

    return $data;
}

function createMidtransQrisPayment($orderType, $bookingCode, $amount, $customer) {
    if (!in_array($orderType, ['reservation', 'facility'], true)) {
        throw new InvalidArgumentException('Jenis pesanan tidak valid.');
    }

    ensurePaymentTransactionsTable();
    $db = getConnection();
    $lookup = $db->prepare("SELECT * FROM payment_transactions WHERE order_type = ? AND booking_code = ? LIMIT 1");
    $lookup->bind_param("ss", $orderType, $bookingCode);
    $lookup->execute();
    $existing = $lookup->get_result()->fetch_assoc();
    $lookup->close();
    if ($existing) return $existing;

    $orderId = 'SST-' . ($orderType === 'reservation' ? 'R-' : 'F-') . $bookingCode;
    $customerDetails = [
        'first_name' => $customer['name'] ?? 'Tamu SangattaStay'
    ];
    foreach (['email', 'phone'] as $field) {
        $value = trim((string)($customer[$field] ?? ''));
        if ($value !== '') {
            $customerDetails[$field] = $value;
        }
    }

    $payload = [
        'payment_type' => 'qris',
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int)round((float)$amount)
        ],
        'customer_details' => $customerDetails,
        'qris' => ['acquirer' => getenv('MIDTRANS_QRIS_ACQUIRER') ?: 'gopay']
    ];
    $response = midtransApiRequest('POST', '/v2/charge', $payload);
    if (($response['order_id'] ?? '') !== $orderId
        || abs((float)($response['gross_amount'] ?? 0) - (float)$amount) > 0.01) {
        throw new RuntimeException('Detail transaksi QRIS dari Midtrans tidak cocok dengan pesanan.');
    }

    $qrCodeUrl = '';
    foreach (($response['actions'] ?? []) as $action) {
        if (in_array($action['name'] ?? '', ['generate-qr-code-v2', 'generate-qr-code'], true)) {
            $qrCodeUrl = $action['url'] ?? '';
            if ($qrCodeUrl !== '') break;
        }
    }
    if ($qrCodeUrl === '' || empty($response['order_id'])) {
        throw new RuntimeException('Midtrans tidak mengembalikan QRIS untuk transaksi ini.');
    }

    $expiryTime = null;
    if (!empty($response['expiry_time'])) {
        $expiry = strtotime($response['expiry_time']);
        $expiryTime = $expiry ? date('Y-m-d H:i:s', $expiry) : null;
    }
    $transactionId = $response['transaction_id'] ?? null;
    $transactionStatus = $response['transaction_status'] ?? 'pending';
    $grossAmount = (float)($response['gross_amount'] ?? $amount);

    $insert = $db->prepare("INSERT INTO payment_transactions
        (order_type, booking_code, order_id, transaction_id, gross_amount, qr_code_url, transaction_status, expiry_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param(
        "ssssdsss",
        $orderType,
        $bookingCode,
        $orderId,
        $transactionId,
        $grossAmount,
        $qrCodeUrl,
        $transactionStatus,
        $expiryTime
    );
    if (!$insert->execute()) {
        $error = $insert->error;
        $insert->close();
        throw new RuntimeException('Gagal menyimpan transaksi QRIS: ' . $error);
    }
    $insert->close();

    return [
        'order_type' => $orderType,
        'booking_code' => $bookingCode,
        'order_id' => $orderId,
        'transaction_id' => $transactionId,
        'gross_amount' => $grossAmount,
        'qr_code_url' => $qrCodeUrl,
        'transaction_status' => $transactionStatus,
        'expiry_time' => $expiryTime
    ];
}

function getPaymentTransaction($orderType, $bookingCode) {
    $db = getConnection();
    $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE order_type = ? AND booking_code = ? LIMIT 1");
    $stmt->bind_param("ss", $orderType, $bookingCode);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $payment;
}

function syncMidtransPaymentStatus($orderId) {
    $db = getConnection();
    $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$payment) return null;

    $response = midtransApiRequest('GET', '/v2/' . rawurlencode($orderId) . '/status');
    if (($response['order_id'] ?? '') !== $orderId
        || ($response['payment_type'] ?? '') !== 'qris'
        || abs((float)($response['gross_amount'] ?? 0) - (float)$payment['gross_amount']) > 0.01) {
        throw new RuntimeException('Detail transaksi Midtrans tidak cocok dengan pesanan.');
    }

    $status = strtolower($response['transaction_status'] ?? 'pending');
    $transactionId = $response['transaction_id'] ?? $payment['transaction_id'];
    $paidAt = $status === 'settlement' ? date('Y-m-d H:i:s') : null;
    $update = $db->prepare("UPDATE payment_transactions
        SET transaction_id = ?, transaction_status = ?, paid_at = COALESCE(paid_at, ?)
        WHERE order_id = ?");
    $update->bind_param("ssss", $transactionId, $status, $paidAt, $orderId);
    $update->execute();
    $update->close();

    if ($status === 'settlement') {
        updateBookingPaymentStatus($payment['order_type'], $payment['booking_code'], 'lunas');
    } elseif (in_array($status, ['expire', 'deny', 'cancel'], true)) {
        updateBookingPaymentStatus($payment['order_type'], $payment['booking_code'], 'batal');
    }

    $payment['transaction_id'] = $transactionId;
    $payment['transaction_status'] = $status;
    $payment['paid_at'] = $paidAt ?: $payment['paid_at'];
    return $payment;
}

function updateBookingPaymentStatus($orderType, $bookingCode, $status) {
    $tables = [
        'reservation' => 'reservasi',
        'facility' => 'pemesanan_fasilitas'
    ];
    if (!isset($tables[$orderType]) || !in_array($status, ['lunas', 'batal'], true)) {
        throw new InvalidArgumentException('Pembaruan status pembayaran tidak valid.');
    }

    $db = getConnection();
    $table = $tables[$orderType];
    $stmt = $db->prepare("UPDATE $table SET status_pembayaran = ? WHERE kode_booking = ? AND status_pembayaran = 'pending'");
    $stmt->bind_param("ss", $status, $bookingCode);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Gagal memperbarui status pesanan: ' . $error);
    }
    $stmt->close();
}

function ensureDefaultRoomTypes() {
    $db = getConnection();
    $hotels = $db->query("SELECT h.id FROM hotels h
        WHERE h.status = 'aktif'
        AND h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')");

    $roomTypes = [
        ['Presidential Suite', 1800000, 4, 'Ruang tamu terpisah, bathtub, balkon, AC, Wi-Fi'],
        ['Executive Suite', 1150000, 3, 'Ruang kerja, sofa lounge, bathtub, AC, Wi-Fi'],
        ['Deluxe King', 750000, 2, 'Tempat tidur king, AC, TV, minibar, Wi-Fi'],
        ['Superior King', 550000, 2, 'Tempat tidur king, AC, TV, Wi-Fi'],
        ['Deluxe Twin', 650000, 2, 'Dua tempat tidur, AC, TV, meja kerja, Wi-Fi'],
        ['Superior Twin', 450000, 2, 'Dua tempat tidur, AC, TV, Wi-Fi']
    ];

    while ($hotel = $hotels->fetch_assoc()) {
        $hotelId = (int)$hotel['id'];
        foreach ($roomTypes as $room) {
            $check = $db->prepare("SELECT id FROM kamar WHERE hotel_id = ? AND nama_kamar = ? LIMIT 1");
            $check->bind_param("is", $hotelId, $room[0]);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) continue;

            $insert = $db->prepare("INSERT INTO kamar
                (hotel_id, nama_kamar, harga_per_malam, kapasitas, stok_kamar, fasilitas_kamar, extra_bed_tersedia, harga_extra_bed)
                VALUES (?, ?, ?, ?, 5, ?, 1, 150000)");
            $insert->bind_param("isdis", $hotelId, $room[0], $room[1], $room[2], $room[3]);
            $insert->execute();
            $insert->close();
        }
    }
}

function ensureFacilityCategoryColumn() {
    $db = getConnection();
    $result = $db->query("SHOW COLUMNS FROM fasilitas LIKE 'kategori'");
    $column = $result ? $result->fetch_assoc() : null;

    if ($column && stripos($column['Type'], 'enum(') === 0) {
        if (!$db->query("ALTER TABLE fasilitas MODIFY kategori VARCHAR(80) NOT NULL")) {
            die("Gagal memperbarui kategori fasilitas: " . $db->error);
        }
    }
}

function seedDefaultFacilitiesForHotel($hotelId) {
    $db = getConnection();
    ensureFacilityCategoryColumn();

    $facilities = [
        ['Breakfast', 'Food & Beverage', 85000, 'per orang', '06:30 - 10:00 WITA', 'Pilihan sarapan untuk satu orang.', 'assets/fasilitas/breakfast-buffet.jpg'],
        ['Lunch', 'Food & Beverage', 100000, 'per orang', '11:00 - 14:00 WITA', 'Paket makan siang untuk satu orang.', 'assets/fasilitas/breakfast-buffet.jpg'],
        ['Dinner', 'Food & Beverage', 125000, 'per orang', '18:00 - 21:00 WITA', 'Paket makan malam untuk satu orang.', 'assets/fasilitas/breakfast-buffet.jpg'],
        ['Swimming Pool', 'Kolam Renang', 50000, 'per orang', '07:00 - 19:00 WITA', 'Akses kolam renang untuk satu orang.', 'assets/fasilitas/pool-daypass.jpg'],
        ['Paket Kolam + Makan & Minum', 'Paket Kolam Renang', 100000, 'per orang', '07:00 - 19:00 WITA', 'Paket akses kolam renang termasuk satu porsi makan dan minum.', 'assets/fasilitas/pool-daypass.jpg'],
        ['Gym / Fitness Center', 'Kebugaran', 50000, 'per orang', '06:00 - 21:00 WITA', 'Akses gym dan fitness center untuk satu orang.', 'assets/fasilitas/sauna.jpg'],
        ['Spa & Massage', 'Spa & Relaksasi', 250000, 'per sesi', '10:00 - 22:00 WITA', 'Sesi spa dan massage relaksasi.', 'assets/fasilitas/spa-massage.jpg'],
        ['Meeting Room', 'Meeting & Acara', 500000, 'per jam', '08:00 - 22:00 WITA', 'Sewa ruang meeting, tarif awal per jam.', 'assets/fasilitas/meeting-room.jpg'],
        ['Laundry', 'Laundry', 25000, 'per item', '08:00 - 20:00 WITA', 'Layanan laundry, tarif awal per item.', 'assets/fasilitas/meeting-room.jpg'],
        ['Romantic Dinner', 'Makan Romantis', 350000, 'per pasangan', '18:00 - 22:00 WITA', 'Paket makan malam romantis untuk satu pasangan.', 'assets/fasilitas/breakfast-buffet.jpg'],
        ['Ballroom / Gedung Acara', 'Meeting & Acara', 5000000, 'per acara', '08:00 - 23:00 WITA', 'Sewa ballroom atau gedung acara, tarif awal per acara.', 'assets/fasilitas/meeting-room.jpg'],
        ['Sauna', 'Spa & Relaksasi', 75000, 'per orang', '10:00 - 21:00 WITA', 'Akses sauna untuk satu orang.', 'assets/fasilitas/sauna.jpg']
    ];

    $hotelId = (int)$hotelId;
    $check = $db->prepare("SELECT id FROM hotels WHERE id = ? AND status = 'aktif' LIMIT 1");
    $check->bind_param("i", $hotelId);
    $check->execute();
    $hotelExists = $check->get_result()->num_rows > 0;
    $check->close();
    if (!$hotelExists) return;

    foreach ($facilities as $facility) {
        $check = $db->prepare("SELECT id FROM fasilitas WHERE hotel_id = ? AND nama_fasilitas = ? LIMIT 1");
        $check->bind_param("is", $hotelId, $facility[0]);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
        if ($exists) continue;

        $insert = $db->prepare("INSERT INTO fasilitas
            (hotel_id, nama_fasilitas, kategori, harga_fasilitas, satuan, jam_operasional, deskripsi, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param(
            "issdssss",
            $hotelId,
            $facility[0],
            $facility[1],
            $facility[2],
            $facility[3],
            $facility[4],
            $facility[5],
            $facility[6]
        );
        if (!$insert->execute()) {
            die("Gagal menambahkan fasilitas default: " . $insert->error);
        }
        $insert->close();
    }
}

function isUserLoggedIn() {
    return isset($_SESSION['user_logged_in'], $_SESSION['user_id'])
        && $_SESSION['user_logged_in'] === true;
}

function requireUserMasuk($redirectAfter = 'booking.php') {
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_after_login'] = $redirectAfter;
        header("Location: login.php?wajib_login=1");
        exit();
    }
}

function currentUser() {
    if (!isUserLoggedIn()) return null;
    $db = getConnection();
    $id = (int)$_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id, nama_lengkap, email, no_hp FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $user;
}

?>
