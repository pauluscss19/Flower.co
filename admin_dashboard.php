<?php
session_start();
include "conn.php";

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_start();
    $_SESSION['admin_logout_success'] = "Berhasil logout dari admin panel.";
    header("Location: admin_login.php");
    exit;
}

// Handle tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama_barang = trim($_POST['nama_barang']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);
    
    if ($nama_barang && $harga > 0 && $stok >= 0) {
        $stmt = $pdo->prepare("INSERT INTO barang (nama_barang, harga, stok) VALUES (?, ?, ?)");
        if ($stmt->execute([$nama_barang, $harga, $stok])) {
            $success_message = "Produk berhasil ditambahkan!";
        } else {
            $error_message = "Gagal menambahkan produk!";
        }
    } else {
        $error_message = "Data tidak valid!";
    }
}

// Handle edit produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $nama_barang = trim($_POST['nama_barang']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);
    
    if ($id > 0 && $nama_barang && $harga > 0 && $stok >= 0) {
        $stmt = $pdo->prepare("UPDATE barang SET nama_barang = ?, harga = ?, stok = ? WHERE id = ?");
        if ($stmt->execute([$nama_barang, $harga, $stok, $id])) {
            $success_message = "Produk berhasil diupdate!";
        } else {
            $error_message = "Gagal mengupdate produk!";
        }
    } else {
        $error_message = "Data tidak valid!";
    }
}

// Handle hapus produk
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success_message = "Produk berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus produk!";
    }
}

// Ambil data produk
$stmt = $pdo->query("SELECT * FROM barang ORDER BY id DESC");
$products = $stmt->fetchAll();

// Ambil statistik
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM barang");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT SUM(stok) as total_stock FROM barang");
$total_stock = $stmt->fetch()['total_stock'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM user WHERE role = 'user'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM pesanan");
$total_orders = $stmt->fetch()['total_orders'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Florelei</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-layout {
            min-height: 100vh;
            background-color: #f8fafc;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .admin-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        
        .admin-nav a:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .stat-title {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            color: #1f2937;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
        }
        
        .add-product-form {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .form-field {
            display: flex;
            flex-direction: column;
        }
        
        .form-field label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-field input {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
        }
        
        .btn-edit {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .products-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <header class="admin-header">
            <h1>🌸 Florelei Admin Panel</h1>
            <nav class="admin-nav">
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="?logout=1">Logout</a>
            </nav>
        </header>

        <div class="admin-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total Produk</div>
                    <div class="stat-value"><?= $total_products ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Total Stok</div>
                    <div class="stat-value"><?= $total_stock ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Total User</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Total Pesanan</div>
                    <div class="stat-value"><?= $total_orders ?></div>
                </div>
            </div>

            <!-- Add Product Form -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Tambah Produk Baru</h2>
                </div>
                <form class="add-product-form" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-field">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_barang" required>
                    </div>
                    <div class="form-field">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" step="0.01" min="0" required>
                    </div>
                    <div class="form-field">
                        <label>Stok</label>
                        <input type="number" name="stok" min="0" required>
                    </div>
                    <div class="form-field">
                        <button type="submit" class="btn-primary">Tambah Produk</button>
                    </div>
                </form>
            </div>

            <!-- Products List -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Daftar Produk</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Terjual</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['nama_barang']) ?></td>
                                <td>Rp <?= number_format($product['harga'], 0, ',', '.') ?></td>
                                <td><?= $product['stok'] ?></td>
                                <td><?= $product['terjual'] ?></td>
                                <td>
                                    <button class="btn-edit" onclick="editProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['nama_barang']) ?>', <?= $product['harga'] ?>, <?= $product['stok'] ?>)">Edit</button>
                                    <button class="btn-delete" onclick="deleteProduct(<?= $product['id'] ?>)">Hapus</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Produk</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-field">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_barang" id="edit-nama" required>
                </div>
                <div class="form-field">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" id="edit-harga" step="0.01" min="0" required>
                </div>
                <div class="form-field">
                    <label>Stok</label>
                    <input type="number" name="stok" id="edit-stok" min="0" required>
                </div>
                <br>
                <button type="submit" class="btn-primary">Update Produk</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('editModal');
        const span = document.getElementsByClassName('close')[0];

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function editProduct(id, nama, harga, stok) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nama').value = nama;
            document.getElementById('edit-harga').value = harga;
            document.getElementById('edit-stok').value = stok;
            modal.style.display = 'block';
        }

        function deleteProduct(id) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                window.location.href = '?delete=' + id;
            }
        }
    </script>
</body>
</html>