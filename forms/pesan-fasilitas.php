<?php
require_once '../config.php';
ensureUsersTable();

if (!isUserLoggedIn()) {
    header("Location: ../login.php?wajib_login=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pesan-fasilitas.php");
    exit();
}

$conn = getConnection();
$user = currentUser();
$hotelId = (int)($_POST['hotel_id'] ?? 0);
$fasilitasId = (int)($_POST['fasilitas_id'] ?? 0);
$jumlahPax = max(1, (int)($_POST['jumlah_pax'] ?? 1));
$tanggal = $_POST['tanggal_kunjungan'] ?? '';
$namaTamu = trim($_POST['nama_tamu'] ?? ($user['nama_lengkap'] ?? ''));
$noHp = trim($_POST['no_hp'] ?? ($user['no_hp'] ?? ''));
$metodeBayar = $_POST['metode_bayar'] ?? 'QRIS';
$catatan = trim($_POST['catatan'] ?? '');
if (!in_array($metodeBayar, ['QRIS', 'Transfer Bank', 'Bayar di Lokasi'], true)) {
    die("<script>alert('Metode pembayaran tidak valid.'); history.back();</script>");
}

if ($hotelId <= 0 || $fasilitasId <= 0 || $tanggal === '' || $namaTamu === '' || $noHp === '' || $jumlahPax > 50) {
    die("<script>alert('Mohon lengkapi semua data pemesanan.'); history.back();</script>");
}

$visitDate = DateTime::createFromFormat('!Y-m-d', $tanggal);
if (!$visitDate || $visitDate->format('Y-m-d') !== $tanggal || $tanggal < date('Y-m-d')) {
    die("<script>alert('Tanggal kunjungan tidak valid.'); history.back();</script>");
}

$stmt = $conn->prepare("SELECT f.harga_fasilitas, f.nama_fasilitas FROM fasilitas f JOIN hotels h ON f.hotel_id=h.id
    WHERE f.id=? AND f.hotel_id=? AND h.status='aktif'
    AND h.id=(SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel=h.nama_hotel AND h2.status='aktif')");
$stmt->bind_param("ii", $fasilitasId, $hotelId);
$stmt->execute();
$facility = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$facility) {
    die("<script>alert('Fasilitas tidak ditemukan untuk hotel tersebut.'); history.back();</script>");
}

$totalHarga = (float)$facility['harga_fasilitas'] * $jumlahPax;
$kodeBooking = generateKodeBooking();

$insert = $conn->prepare("INSERT INTO pemesanan_fasilitas
    (kode_booking, user_id, hotel_id, fasilitas_id, nama_tamu, no_hp, tanggal_kunjungan, jumlah_pax, total_harga, metode_bayar, catatan)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insert->bind_param("siiisssidss", $kodeBooking, $user['id'], $hotelId, $fasilitasId, $namaTamu, $noHp, $tanggal, $jumlahPax, $totalHarga, $metodeBayar, $catatan);

if ($insert->execute()) {
    $insert->close();
    if ($metodeBayar === 'QRIS') {
        try {
            createMidtransQrisPayment('facility', $kodeBooking, $totalHarga, [
                'name' => $namaTamu,
                'email' => $user['email'] ?? '',
                'phone' => $noHp
            ]);
        } catch (RuntimeException $error) {
            error_log('Midtrans facility payment setup failed: ' . $error->getMessage());
            header("Location: ../track-fasilitas.php?kode=" . urlencode($kodeBooking) . "&qris=error");
            exit();
        }
    }
    header("Location: ../track-fasilitas.php?kode=" . urlencode($kodeBooking));
    exit();
}

$error = $insert->error;
$insert->close();
die("Gagal menyimpan pemesanan fasilitas: " . htmlspecialchars($error));
