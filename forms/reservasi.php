<?php
require_once '../config.php';
ensureUsersTable();

$hotel_id_req = (int)($_REQUEST['hotel_id'] ?? 0);
$fas_id_req   = (int)($_REQUEST['fas_id'] ?? 0);
$returnBooking = '../booking.php' . ($hotel_id_req > 0 ? '?hotel_id=' . $hotel_id_req . ($fas_id_req > 0 ? '&fas_id=' . $fas_id_req : '') : '');

// Wajib login dulu sebelum bisa melakukan reservasi
if (!isUserLoggedIn()) {
    $_SESSION['redirect_after_login'] = $returnBooking;
    header("Location: ../login.php?wajib_login=1");
    exit();
}

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../booking.php");
    exit;
}

$user         = currentUser();
$user_id      = (int)$user['id'];
$email_user   = $user['email'] ?? '';
$hotel_id     = (int)($_POST['hotel_id'] ?? 0);
$kamar_id     = (int)($_POST['kamar_id'] ?? 0);
$jumlah_kamar = max(1, (int)($_POST['jumlah_kamar'] ?? 1));
$jumlah_extra_bed = max(0, (int)($_POST['jumlah_extra_bed'] ?? 0));
$tgl_checkin  = $_POST['tanggal_checkin'] ?? '';
$tgl_checkout = $_POST['tanggal_checkout'] ?? '';
$nama_tamu    = trim($_POST['nama_tamu'] ?? ($user['nama_lengkap'] ?? ''));
$no_hp        = trim($_POST['no_hp'] ?? ($user['no_hp'] ?? ''));
$metode_bayar = $_POST['metode_bayar'] ?? 'QRIS';
$catatan      = trim($_POST['catatan'] ?? '');
if (!in_array($metode_bayar, ['QRIS', 'Transfer Bank', 'Bayar di Resepsionis'], true)) {
    die("<script>alert('Metode pembayaran tidak valid.'); history.back();</script>");
}

if ($hotel_id <= 0 || $kamar_id <= 0 || $nama_tamu === '' || $no_hp === '' || $tgl_checkin === '' || $tgl_checkout === '') {
    die("<script>alert('Mohon lengkapi pilihan hotel, kamar, tanggal check-in/out, dan data tamu.'); history.back();</script>");
}

if ($tgl_checkout <= $tgl_checkin) {
    die("<script>alert('Tanggal check-out harus setelah tanggal check-in.'); history.back();</script>");
}

$stmtK = $conn->prepare("SELECT harga_per_malam, extra_bed_tersedia, harga_extra_bed FROM kamar WHERE id = ? AND hotel_id = ?");
$stmtK->bind_param("ii", $kamar_id, $hotel_id);
$stmtK->execute();
$dKamar = $stmtK->get_result()->fetch_assoc();
$stmtK->close();

if (!$dKamar) {
    die("<script>alert('Tipe kamar tidak ditemukan pada hotel tersebut.'); history.back();</script>");
}

$total_malam = max(1, (int)round((strtotime($tgl_checkout) - strtotime($tgl_checkin)) / 86400));
$subtotal_kamar = (float)$dKamar['harga_per_malam'] * $jumlah_kamar * $total_malam;
$jumlah_extra_bed = (int)$dKamar['extra_bed_tersedia'] === 1 ? $jumlah_extra_bed : 0;
$subtotal_extra_bed = $jumlah_extra_bed * (float)$dKamar['harga_extra_bed'] * $total_malam;

$fasIds = $_POST['fasilitas_ids'] ?? [];
$fasPax = $_POST['fasilitas_pax'] ?? [];
$itemsFasilitas = [];
$subtotal_fasilitas = 0;

if (is_array($fasIds)) {
    foreach ($fasIds as $fid) {
        $fid = (int)$fid;
        $pax = max(1, (int)($fasPax[$fid] ?? 1));

        $stmtF = $conn->prepare("SELECT harga_fasilitas FROM fasilitas WHERE id = ? AND hotel_id = ?");
        $stmtF->bind_param("ii", $fid, $hotel_id);
        $stmtF->execute();
        $dFas = $stmtF->get_result()->fetch_assoc();
        $stmtF->close();

        if ($dFas) {
            $sub = (float)$dFas['harga_fasilitas'] * $pax;
            $subtotal_fasilitas += $sub;
            $itemsFasilitas[] = [
                'fasilitas_id' => $fid,
                'jumlah_pax'   => $pax,
                'harga_satuan' => (float)$dFas['harga_fasilitas'],
                'subtotal'     => $sub
            ];
        }
    }
}

$total_harga = $subtotal_kamar + $subtotal_fasilitas + $subtotal_extra_bed;
$kode_booking = generateKodeBooking();

$stmt = $conn->prepare("INSERT INTO reservasi (kode_booking, user_id, hotel_id, nama_tamu, no_hp, email, kamar_id, jumlah_kamar, tanggal_checkin, tanggal_checkout, total_malam, subtotal_kamar, subtotal_fasilitas, jumlah_extra_bed, subtotal_extra_bed, total_harga, metode_bayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("siisssiissiddiddss", $kode_booking, $user_id, $hotel_id, $nama_tamu, $no_hp, $email_user, $kamar_id, $jumlah_kamar, $tgl_checkin, $tgl_checkout, $total_malam, $subtotal_kamar, $subtotal_fasilitas, $jumlah_extra_bed, $subtotal_extra_bed, $total_harga, $metode_bayar, $catatan);

if ($stmt->execute()) {
    $reservasi_id = $stmt->insert_id;
    $stmt->close();

    if (count($itemsFasilitas) > 0) {
        $stmtDet = $conn->prepare("INSERT INTO reservasi_fasilitas (reservasi_id, fasilitas_id, jumlah_pax, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($itemsFasilitas as $it) {
            $stmtDet->bind_param("iiidd", $reservasi_id, $it['fasilitas_id'], $it['jumlah_pax'], $it['harga_satuan'], $it['subtotal']);
            $stmtDet->execute();
        }
        $stmtDet->close();
    }

    if ($metode_bayar === 'QRIS') {
        try {
            createMidtransQrisPayment('reservation', $kode_booking, $total_harga, [
                'name' => $nama_tamu,
                'email' => $email_user,
                'phone' => $no_hp
            ]);
        } catch (RuntimeException $error) {
            error_log('Midtrans reservation payment setup failed: ' . $error->getMessage());
            header("Location: ../track-reservasi.php?kode=" . urlencode($kode_booking) . "&qris=error");
            exit;
        }
    }

    header("Location: ../track-reservasi.php?status=sukses&kode=" . urlencode($kode_booking));
    exit;
} else {
    die("Gagal menyimpan reservasi: " . $conn->error);
}
?>