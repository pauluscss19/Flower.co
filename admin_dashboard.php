<?php
session_start();
include "conn.php";

// Cek apakah user sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Ambil data statistik
$users_count = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
$products_count = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$orders_count = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$reviews_count = $pdo->query("SELECT COUNT(*) FROM ulasan")->fetchColumn();

// Ambil data terbaru
$latest_users = $pdo->query("SELECT username, email, created_at FROM user ORDER BY created_at DESC LIMIT 5")->fetchAll();
$latest_products = $pdo->query("SELECT nama_barang, harga, stok FROM barang ORDER BY id DESC LIMIT 5")->fetchAll();
$latest_orders = $pdo->query("SELECT p.id, u.username, p.tanggal_pemesanan, p.status 
                             FROM pesanan p 
                             JOIN user u ON p.user_id = u.id 
                             ORDER BY p.tanggal_pemesanan DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Florelei</title>
    <link rel="stylesheet" href="admin.css">
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
               <a href="admin_dashboard.php" class="admin-nav-link active">Dashboard</a>
                <a href="admin_users.php" class="admin-nav-link">Users</a>
                <a href="admin_products.php" class="admin-nav-link">Products</a>
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
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="admin-stats">
                    <div class="admin-stat-card">
                        <h3>Total Users</h3>
                        <p><?php echo $users_count; ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Total Products</h3>
                        <p><?php echo $products_count; ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Total Orders</h3>
                        <p><?php echo $orders_count; ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Total Reviews</h3>
                        <p><?php echo $reviews_count; ?></p>
                    </div>
                </div>

                <!-- Latest Data Sections -->
                <div class="admin-data-section">
                    <div class="admin-data-card">
                        <h2>Latest Users</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-data-card">
                        <h2>Latest Products</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['nama_barang']); ?></td>
                                    <td>Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></td>
                                    <td><?php echo $product['stok']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-data-section">
                    <div class="admin-data-card">
                        <h2>Latest Orders</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['tanggal_pemesanan'])); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>