<?php 
include 'koneksi.php';
$query = mysqli_query($koneksi, "SELECT * FROM v_master_barang");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>View Master Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar Navigasi 6 View Master -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Inventori PBD</a>
            <div class="navbar-nav">
                <a class="nav-link active" href="view_barang.php">1. Barang</a>
                <a class="nav-link" href="view_user.php">2. User</a>
                <a class="nav-link" href="view_vendor.php">3. Vendor</a>
                <a class="nav-link" href="view_satuan.php">4. Satuan</a>
                <a class="nav-link" href="view_margin.php">5. Margin</a>
                <a class="nav-link" href="view_stok.php">6. Stok Barang</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h4 class="mb-3 fw-bold">View 1: Master Barang (v_master_barang)</h4>
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Barang</th>
                            <th>Nama Barang</th>
                            <th>Jenis</th>
                            <th>Harga</th>
                            <th>Satuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $row['idbarang']; ?></td>
                            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                            <td><?= $row['jenis']; ?></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($row['nama_satuan']); ?></td>
                            <td>
                                <span class="badge <?= $row['status_barang'] == 'Aktif' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?= $row['status_barang']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>