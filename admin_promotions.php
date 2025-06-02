<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Get all promotions
$promotions = $pdo->query("SELECT * FROM promo ORDER BY berlaku_hingga DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promotions - Florelei Admin</title>
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
                <a href="admin_dashboard.php" class="admin-nav-link">Dashboard</a>
                <a href="admin_users.php" class="admin-nav-link">Users</a>
                <a href="admin_products.php" class="admin-nav-link">Products</a>
                <a href="admin_orders.php" class="admin-nav-link">Orders</a>
                <a href="admin_reviews.php" class="admin-nav-link">Reviews</a>
                <a href="admin_promotions.php" class="admin-nav-link active">Promotions</a>
                <a href="admin_messages.php" class="admin-nav-link">Messages</a>
                <a href="admin_logout.php" class="admin-nav-link logout">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>Manage Promotions</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <div class="admin-content">
                <div class="admin-data-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2>All Promotions</h2>
                        <button class="admin-login-btn" style="padding: 0.5rem 1rem;">Add New Promotion</button>
                    </div>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Promo Name</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Valid Until</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promotions as $promo): ?>
                            <tr>
                                <td><?php echo $promo['id']; ?></td>
                                <td><?php echo htmlspecialchars($promo['nama_promo']); ?></td>
                                <td><?php echo htmlspecialchars(substr($promo['deskripsi'], 0, 50)); ?><?php echo strlen($promo['deskripsi']) > 50 ? '...' : ''; ?></td>
                                <td><?php echo $promo['potongan_persen']; ?>%</td>
                                <td><?php echo date('d M Y', strtotime($promo['berlaku_hingga'])); ?></td>
                                <td>
                                    <button style="background: #3498db; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; margin-right: 0.3rem;">Edit</button>
                                    <button style="background: #e74c3c; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px;">Delete</button>
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