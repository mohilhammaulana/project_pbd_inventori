<?php 
include 'koneksi.php';
$query = mysqli_query($koneksi, "SELECT * FROM v_master_user");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>View Master User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Inventori PBD</a>
            <div class="navbar-nav">
                <a class="nav-link" href="view_barang.php">1. Barang</a>
                <a class="nav-link active" href="view_user.php">2. User</a>
                <a class="nav-link" href="view_vendor.php">3. Vendor</a>
                <a class="nav-link" href="view_satuan.php">4. Satuan</a>
                <a class="nav-link" href="view_margin.php">5. Margin</a>
                <a class="nav-link" href="view_stok.php">6. Stok Barang</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h4 class="mb-3 fw-bold">View 2: Master User & Role (v_master_user)</h4>
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID User</th>
                            <th>Username</th>
                            <th>ID Role</th>
                            <th>Nama Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $row['iduser']; ?></td>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td><?= $row['idrole']; ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($row['nama_role']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>