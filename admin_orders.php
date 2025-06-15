<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    $_SESSION['message'] = "Order status updated successfully!";
    header("Location: admin_orders.php");
    exit;
}

// Get all orders with user information
$orders = $pdo->query("SELECT p.id, u.username, p.tanggal_pemesanan, p.status, p.metode_pembayaran 
                      FROM pesanan p 
                      JOIN user u ON p.user_id = u.id 
                      ORDER BY p.tanggal_pemesanan DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Florelei Admin</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 800px;
            border-radius: 8px;
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
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }
        .status-badge.pending {
            background-color: #f39c12;
            color: white;
        }
        .status-badge.dibayar {
            background-color: #3498db;
            color: white;
        }
        .status-badge.dikirim {
            background-color: #9b59b6;
            color: white;
        }
        .status-badge.selesai {
            background-color: #2ecc71;
            color: white;
        }
        .order-details {
            margin-top: 20px;
        }
        .order-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-details th, .order-details td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .order-details th {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
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
                <a href="admin_tambah_produk.php" class="admin-nav-link">Tambah Produk</a>
                <a href="admin_orders.php" class="admin-nav-link active">Orders</a>
                <a href="admin_reviews.php" class="admin-nav-link">Reviews</a>
                <a href="admin_promotions.php" class="admin-nav-link">Promotions</a>
                <a href="admin_messages.php" class="admin-nav-link">Messages</a>
                <a href="admin_logout.php" class="admin-nav-link logout">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>Manage Orders</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                <?php endif; ?>
                
                <div class="admin-data-card">
                    <h2>All Orders</h2>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo date('d M Y', strtotime($order['tanggal_pemesanan'])); ?></td>
                                <td><?php echo $order['metode_pembayaran'] ?? '-'; ?></td>
                                <td><span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                <td>
                                    <button onclick="openViewModal(<?php echo $order['id']; ?>)" style="background: #3498db; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; margin-right: 0.3rem; cursor: pointer;">View</button>
                                    <button onclick="openUpdateModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" style="background: #2ecc71; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; cursor: pointer;">Update</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetailsContent"></div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateModal')">&times;</span>
            <h2>Update Order Status</h2>
            <form method="POST" action="">
                <input type="hidden" id="update_order_id" name="order_id">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="dibayar">Dibayar</option>
                        <option value="dikirim">Dikirim</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
                <button type="submit" name="update_status" style="background: #2ecc71; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Update Status</button>
            </form>
        </div>
    </div>

    <script>
        // Function to open view modal and load order details
        function openViewModal(orderId) {
            // Fetch order details via AJAX
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                    document.getElementById('viewModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML = '<p>Error loading order details.</p>';
                    document.getElementById('viewModal').style.display = 'block';
                });
        }

        // Function to open update modal
        function openUpdateModal(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('updateModal').style.display = 'block';
        }

        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>