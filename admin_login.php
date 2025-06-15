<?php
ob_start(); // Mulai output buffering
session_start();
include "conn.php";

// Redirect jika sudah login sebagai admin
if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    if (!$identifier || !$password) {
        $_SESSION['admin_login_error'] = "Silakan isi semua kolom.";
        header("Location: admin_login.php");
        exit;
    }

    // Query ke tabel admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$identifier]);
    $admin = $stmt->fetch();

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_id'] = $admin['id']; // Ubah dari user_id ke admin_id
        $_SESSION['admin_username'] = $admin['username']; // Ubah dari username ke admin_username
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_login_success'] = "Berhasil login sebagai admin!";
        session_write_close(); // Pastikan sesi disimpan
        header("Location: admin_dashboard.php");
        exit;
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
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .admin-login-container {
            max-width: 400px;
            width: 100%;
        }
        
        .admin-login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            text-align: center;
        }
        
        .admin-login-header {
            margin-bottom: 30px;
        }
        
        .admin-login-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .admin-login-subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="admin-login-page">
        <div class="admin-login-container">
            <div class="admin-login-card">
                <div class="admin-badge">Admin Portal</div>
                
                <div class="admin-login-header">
                    <h1 class="admin-login-title">Admin Login</h1>
                    <p class="admin-login-subtitle">Masuk ke panel administrator</p>
                </div>

                <?php if (isset($_SESSION['admin_login_error'])): ?>
                    <div class="notif error"><?php echo $_SESSION['admin_login_error']; unset($_SESSION['admin_login_error']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['admin_logout_success'])): ?>
                    <div class="notif success"><?php echo $_SESSION['admin_logout_success']; unset($_SESSION['admin_logout_success']); ?></div>
                <?php endif; ?>

                <form class="login-form" action="admin_login.php" method="POST">
                    <div class="form-group">
                        <label for="identifier" class="form-label">Username</label>
                        <input type="text" id="identifier" name="identifier" class="form-input" placeholder="Masukkan username admin" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••" required>
                    </div>

                    <button type="submit" class="btn-login-form">Login Admin</button>

                    <div class="login-links">
                        <p class="register-link">
                            Bukan admin?
                            <a href="login.php" class="register-link-text">Login User</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>