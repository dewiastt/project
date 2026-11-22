<?php
require_once 'config.php';
requireMasuk();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - SangattaStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/sangatta-stay.css">
    <link rel="stylesheet" href="assets/css/luxury-theme.css">
</head>
<body class="admin-body">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-building-check"></i>
            <span>SANGATTA STAY</span>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?= ($currentPage === 'admin-dashboard.php') ? 'active' : '' ?>" href="admin-dashboard.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dasbor</span>
            </a>
            <a class="nav-link <?= ($currentPage === 'admin-reservasi.php') ? 'active' : '' ?>" href="admin-reservasi.php">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Reservasi</span>
            </a>
            <a class="nav-link <?= ($currentPage === 'admin-pemesanan-fasilitas.php') ? 'active' : '' ?>" href="admin-pemesanan-fasilitas.php">
                <i class="bi bi-ticket-perforated-fill"></i>
                <span>Pesanan Fasilitas</span>
            </a>
            <a class="nav-link <?= ($currentPage === 'admin-hotel.php') ? 'active' : '' ?>" href="admin-hotel.php">
                <i class="bi bi-buildings-fill"></i>
                <span>Hotel Sangatta</span>
            </a>
            <a class="nav-link <?= ($currentPage === 'admin-paket.php') ? 'active' : '' ?>" href="admin-paket.php">
                <i class="bi bi-door-open-fill"></i>
                <span>Tipe Kamar</span>
            </a>
            <a class="nav-link <?= ($currentPage === 'admin-fasilitas.php') ? 'active' : '' ?>" href="admin-fasilitas.php">
                <i class="bi bi-water"></i>
                <span>Fasilitas Tambahan</span>
            </a>
            <a class="nav-link mt-4 text-warning" href="admin-logout.php">
                <i class="bi bi-box-arrow-left"></i>
                <span>Keluar</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-4 bg-white border shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <div>
                    <small class="text-muted">SangattaStay / <?= $pageBreadcrumb ?? 'Admin' ?></small>
                    <h4 class="fw-bold mb-0"><?= $pageTitle ?? 'Dasbor' ?></h4>
                </div>
            </div>
            <a href="index.php" target="_blank" class="btn btn-sm btn-royal rounded-pill px-3">
                <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Website
            </a>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4">
                <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4">
                <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
