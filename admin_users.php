<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    try {
        $id = $_GET['delete_id'];
        
        // First delete from profil table if exists
        $stmt = $pdo->prepare("DELETE p FROM profil p JOIN user u ON p.id = u.profil_id WHERE u.id = ?");
        $stmt->execute([$id]);
        
        // Then delete from user table
        $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success_message'] = 'User deleted successfully!';
        header("Location: admin_users.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error deleting user: ' . $e->getMessage();
        header("Location: admin_users.php");
        exit;
    }
}

// Get all users
$users = $pdo->query("SELECT u.id, u.username, u.email, u.created_at, p.no_wa 
                     FROM user u 
                     LEFT JOIN profil p ON u.profil_id = p.id 
                     ORDER BY u.created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Florelei Admin</title>
    <style>
        /* Admin Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #334155;
        }

        /* Color Palette */
        :root {
            --primary: green;
            --primary-hover: green;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            --bg-sidebar: #FFD700;
            --bg-card: #ffffff;
            --bg-body: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: black;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: 250px;
            background-color: var(--bg-sidebar);
            color: var(--text-light);
            padding: 1.5rem 0;
            position: fixed;
            height: 100%;
        }

        .admin-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 1.5rem;
        }

        .admin-logo-img {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }

        .admin-logo h2 {
            font-size: 1.2rem;
            text-align: center;
            color: var(--text-light);
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
        }

        .admin-nav-link {
            color: var(--text-light);
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            transition: background-color 0.3s;
        }

        .admin-nav-link:hover {
            background-color: green;
        }

        .admin-nav-link.active {
            background-color: var(--primary);
        }

        .admin-nav-link.logout {
            margin-top: auto;
            background-color: var(--danger);
        }

        .admin-nav-link.logout:hover {
            background-color: var(--danger-hover);
        }

        /* Main Content Styles */
        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background-color: var(--bg-body);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-header h1 {
            color: var(--text-primary);
            font-size: 1.5rem;
        }

        .admin-user {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Notification Styles */
        .admin-notif {
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }

        .admin-notif.success {
            background-color: #dcfce7;
            color: #166534;
            border-left: 4px solid #16a34a;
        }

        .admin-notif.error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Table Card Styles */
        .admin-data-card {
            background-color: var(--bg-card);
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .admin-table-actions {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Button Styles */
        .admin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .admin-btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .admin-btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .admin-btn-secondary {
            background-color: #64748b;
            color: white;
        }

        .admin-btn-secondary:hover {
            background-color: #475569;
        }

        .admin-btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .admin-btn-danger:hover {
            background-color: var(--danger-hover);
        }

        /* Table Styles */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .admin-table th {
            background-color: #f1f5f9;
            color: var(--text-secondary);
            font-weight: 600;
            text-align: left;
            padding: 0.75rem 1rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background-color: #f8fafc;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            margin-right: 0.3rem;
        }

        .edit-btn {
            background-color: var(--primary);
            color: white;
        }

        .edit-btn:hover {
            background-color: var(--primary-hover);
        }

        .delete-btn {
            background-color: var(--danger);
            color: white;
        }

        .delete-btn:hover {
            background-color: var(--danger-hover);
        }

        /* Modal Styles */
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
            width: 50%;
            max-width: 600px;
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

        /* Form Styles */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
        }

        .form-actions {
            margin-top: 1.5rem;
            text-align: right;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .modal-content {
                width: 90%;
            }
        }
    </style>
    <script>
        // Function to confirm user deletion
        function confirmDelete(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = 'admin_users.php?delete_id=' + userId;
            }
        }

        // Function to open edit modal
        function openEditModal(userId) {
            // In a real implementation, you would fetch user data via AJAX here
            // For this example, we'll just show the modal
            document.getElementById('editUserId').value = userId;
            document.getElementById('editModal').style.display = 'block';
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
                <a href="admin_users.php" class="admin-nav-link active">Users</a>
                <a href="admin_products.php" class="admin-nav-link">Products</a>
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
                <h1>Manage Users</h1>
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
                        <h2>All Users</h2>
                    </div>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['no_wa'] ?? '-'; ?></td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button onclick="openEditModal(<?php echo $user['id']; ?>)" class="action-btn edit-btn">Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit User</h2>
            <form action="admin_update_user.php" method="POST">
                <input type="hidden" id="editUserId" name="user_id">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>