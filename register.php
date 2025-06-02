<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $no_wa = trim($_POST['no_wa']);
    $password = $_POST['password'];
    $nama_ibu = trim($_POST['nama_ibu']);
    $terms = isset($_POST['terms']) ? true : false;

    if (!$username || !$email || !$password || !$nama_ibu || !$terms) {
        $_SESSION['register_error'] = "Semua field wajib diisi dan setuju dengan syarat.";
        header("Location: register.php");
        exit;
    }
    $cek_user = $pdo->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
    $cek_user->execute([$username, $email]);
    if ($cek_user->rowCount() > 0) {
        $_SESSION['register_error'] = "Username atau email sudah digunakan.";
        header("Location: register.php");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {

        $pdo->beginTransaction();
        $stmtProfil = $pdo->prepare("INSERT INTO profil (nama_ibu, no_wa) VALUES (?, ?)");
        $stmtProfil->execute([$nama_ibu, $no_wa]);
        $profil_id = $pdo->lastInsertId();
        $stmtUser = $pdo->prepare("INSERT INTO user (username, email, password, profil_id) VALUES (?, ?, ?, ?)");
        $stmtUser->execute([$username, $email, $password_hash, $profil_id]);
        $pdo->commit();

        $_SESSION['register_success'] = "Akun berhasil dibuat. Silakan login.";
        header("Location: login.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['register_error'] = "Gagal membuat akun. Silakan coba lagi.";
        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Florelei</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="register-page">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h1 class="register-title">Daftarkan Akunmu!</h1>
                    <p class="register-subtitle">Daftarkan akunmu untuk akses membeli bunga secara lengkap!</p>
                </div>

                <?php if (isset($_SESSION['register_error'])): ?>
                    <div class="notif error"><?php echo $_SESSION['register_error']; unset($_SESSION['register_error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['register_success'])): ?>
                    <div class="notif success"><?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?></div>
                <?php endif; ?>

                <form class="register-form" action="register.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Masukan Nama</label>
                        <input type="text" id="name" name="username" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Masukan Email</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Masukan No HP</label>
                        <input type="tel" id="phone" name="no_wa" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Masukan Password</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="mother-name" class="form-label">Masukan Nama Hewan Anda</label>
                        <input type="text" id="mother-name" name="nama_ibu" class="form-input">
                        <small class="form-hint">Ini digunakan saat anda lupa password!</small>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="terms" name="terms" class="checkbox-input" required>
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">
                                Saya setuju dengan Florelei Flower.co. Terms of Service, Privacy Policy, dan default Notification Settings
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-register-form">Buat Akun</button>

                    <div class="register-links">
                        <p class="login-link">
                            Punya akun?
                            <a href="login.php" class="login-link-text">Login</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
