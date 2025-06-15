<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    try {
        $id = $_GET['delete_id'];
        
        // First check if product exists in any orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM detail_pesanan WHERE barang_id = ?");
        $stmt->execute([$id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            $_SESSION['error_message'] = 'Produk tidak dapat dihapus karena terdapat dalam pesanan.';
            header("Location: admin_products.php");
            exit;
        }
        
        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete image file if it's not the default
        if ($product['gambar'] != 'default.jpg' && file_exists("uploads/" . $product['gambar'])) {
            unlink("uploads/" . $product['gambar']);
        }
        
        $_SESSION['success_message'] = 'Produk berhasil dihapus!';
        header("Location: admin_products.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header("Location: admin_products.php");
        exit;
    }
}

// Get all products
$products = $pdo->query("SELECT * FROM barang ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Florelei Admin</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: 250px;
            background-color: #FFD700;
            color: white;
            padding: 20px 0;
        }
        
        .admin-logo {
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .admin-logo-img {
            max-width: 80%;
            height: auto;
            margin-bottom: 10px;
        }
        
        .admin-nav {
            padding: 20px 0;
        }
        
        .admin-nav-link {
            display: block;
            padding: 12px 20px;
            color: black;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .admin-nav-link:hover, .admin-nav-link.active {
            background-color: green;
            color: #fff;
        }
        
        .admin-nav-link.logout {
            color: #e74c3c;
        }
        
        .admin-nav-link.logout:hover {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Main Content Styles */
        .admin-main {
            flex: 1;
            padding: 20px;
            background-color: #ecf0f1;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .admin-user {
            font-size: 14px;
            color: black;
        }
        
        /* Notification Styles */
        .admin-notif {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .admin-notif.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .admin-notif.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Table Styles */
        .admin-data-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .admin-table-actions {
            margin-bottom: 20px;
            text-align: right;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .admin-table tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Button Styles */
        .admin-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            margin-right: 5px;
        }
        
        .admin-btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .admin-btn-primary:hover {
            background-color: #2980b9;
        }
        
        .admin-btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .admin-btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .admin-btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .admin-btn-danger:hover {
            background-color: #c0392b;
        }
        
        /* Product Image Styles */
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                window.location.href = 'admin_products.php?delete_id=' + id;
            }
        }
    </script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <img src="img/logo.png" alt="Florelei" class="admin-logo-img">
                <h2>Florelei Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="admin_dashboard.php" class="admin-nav-link">Dashboard</a>
                <a href="admin_users.php" class="admin-nav-link">Users</a>
                <a href="admin_products.php" class="admin-nav-link active">Products</a>
                <a href="admin_tambah_produk.php" class="admin-nav-link">Tambah Produk</a>
                <a href="admin_orders.php" class="admin-nav-link">Orders</a>
                <a href="admin_reviews.php" class="admin-nav-link">Reviews</a>
                <a href="admin_promotions.php" class="admin-nav-link">Promotions</a>
                <a href="admin_messages.php" class="admin-nav-link">Messages</a>
                <a href="admin_logout.php" class="admin-nav-link logout">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>Manage Products</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="admin-notif success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="admin-notif error">
                        <?php echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="admin-data-card">
                    <div class="admin-table-actions">
                        <a href="admin_tambah_produk.php" class="admin-btn admin-btn-primary">Tambah Produk Baru</a>
                    </div>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="uploads/<?php echo htmlspecialchars($product['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['nama_barang']); ?>" 
                                         class="product-image">
                                </td>
                                <td><?php echo htmlspecialchars($product['nama_barang']); ?></td>
                                <td>Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo $product['stok']; ?></td>
                                <td>
                                    <a href="admin_edit_produk.php?id=<?php echo $product['id']; ?>" 
                                       class="admin-btn admin-btn-primary">Edit</a>
                                    <button onclick="confirmDelete(<?php echo $product['id']; ?>)" 
                                            class="admin-btn admin-btn-danger">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>