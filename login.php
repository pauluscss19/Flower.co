<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    if (!$identifier || !$password) {
        $_SESSION['login_error'] = "Silakan isi semua kolom.";
        header("Location: login.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_success'] = "Berhasil login!";
        header("Location: menu.php");
        exit;
    } else {
        $_SESSION['login_error'] = "Username/email atau password salah.";
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Florelei</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Login Page -->
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1 class="login-title">Login ke Florelei</h1>
                    <p class="login-subtitle">Selamat datang kembali</p>
                </div>

                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="notif error"><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['register_success'])): ?>
                    <div class="notif success"><?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?></div>
                <?php endif; ?>

                <form class="login-form" action="login.php" method="POST">
                    <div class="form-group">
                        <label for="identifier" class="form-label">Username atau Email</label>
                        <input type="text" id="identifier" name="identifier" class="form-input" placeholder="Masukkan username atau email" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••" required>
                    </div>

                    <button type="submit" class="btn-login-form">Login</button>

                    <div class="login-links">
                        <a href="#" class="forgot-password">Lupa Password?</a>
                        <p class="register-link">
                            Belum punya akun?
                            <a href="register.php" class="register-link-text">Daftar di sini</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
