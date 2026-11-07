<?php 
include 'koneksi.php';

// 1. Hitung Total Barang
$q_barang = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang");
$total_barang = mysqli_fetch_assoc($q_barang)['total'];

// 2. Hitung Total Vendor
$q_vendor = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM vendor");
$total_vendor = mysqli_fetch_assoc($q_vendor)['total'];

// 3. Hitung Total Transaksi Penjualan & Omset
$q_penjualan = mysqli_query($koneksi, "SELECT COUNT(*) AS total_transaksi, SUM(total_nilai) AS total_omset FROM penjualan");
$d_penjualan = mysqli_fetch_assoc($q_penjualan);
$total_penjualan =$d_penjualan['total_transaksi'];
$total_omset =$d_penjualan['total_omset'] ?? 0;

// 4. Hitung Total Pengadaan
$q_pengadaan = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pengadaan");
$total_pengadaan = mysqli_fetch_assoc($q_pengadaan)['total'];

// 5. Ambil Data 5 Barang Terbaru dengan Stok dari View
$q_stok = mysqli_query($koneksi, "SELECT * FROM v_master_barang_stok ORDER BY idbarang DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventori PBD</title>
    <!-- Bootstrap 5 CSS & FontAwesome Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-boxes-stacked me-2"></i>Inventori PBD
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-menu navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="fa-solid fa-gauge me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fa-solid fa-box me-1"></i> Data Barang</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Header -->
        <div class="d-flex justify-