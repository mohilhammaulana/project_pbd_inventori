<?php 
include 'koneksi.php';

// Mengambil data dari VIEW v_master_barang_stok
$query = mysqli_query($koneksi, "SELECT * FROM v_master_barang_stok ORDER BY idbarang DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Barang - Project PBD</title>
    <!-- CDN Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Sistem Inventori PBD</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Data Master Barang</h2>
            <a href="tambah_barang.php" class="btn btn-primary">+ Tambah Barang</a>
        </div>

        <!-- Tabel Data Barang -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Sisa Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($query) > 0):
                            while($row = mysqli_fetch_assoc($query)): 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_barang']); ?></strong></td>
                            <td><?= htmlspecialchars($row['nama_satuan']); ?></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?= $row['sisa_stok'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?= $row['sisa_stok']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">Belum ada data barang.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>