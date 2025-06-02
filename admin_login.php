<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$username || !$password) {
        $_SESSION['admin_login_error'] = "Silakan isi semua kolom.";
        header("Location: admin_login.php");
        exit;
    }

    // Query ke tabel admin terpisah
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Cek apakah password di database sudah di-hash atau masih plain text
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_login_success'] = "Berhasil login sebagai admin!";
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $_SESSION['admin_login_error'] = "Username atau password salah.";
            header("Location: admin_login.php");
            exit;
        }
    } else {
        $_SESSION['admin_login_error'] = "Username atau password salah.";
        header("Location: admin_login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Florelei</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <h1 class="admin-login-title">Admin Login</h1>
                <p class="admin-login-subtitle">Florelei Flower.co Management</p>
            </div>

            <?php if (isset($_SESSION['admin_login_error'])): ?>
                <div class="admin-notif error"><?php echo $_SESSION['admin_login_error']; unset($_SESSION['admin_login_error']); ?></div>
            <?php endif; ?>

            <form class="admin-login-form" action="admin_login.php" method="POST">
                <div class="admin-form-group">
                    <label for="username" class="admin-form-label">Username</label>
                    <input type="text" id="username" name="username" class="admin-form-input" placeholder="Masukkan username admin" required>
                </div>

                <div class="admin-form-group">
                    <label for="password" class="admin-form-label">Password</label>
                    <input type="password" id="password" name="password" class="admin-form-input" placeholder="••••••••••" required>
                </div>

                <button type="submit" class="admin-login-btn">Login</button>
            </form>
        </div>
    </div>
</body>
</html>