<?php
session_start();
include "conn.php";

// Redirect if user is not logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php?redirect=bukti_bayar.php");
    exit;
}

// Get order details
$pesanan_id = $_GET['pesanan_id'] ?? null;
$order = null;
$cart_items = [];
$total = 0;
$total_pembayaran = 0;

if ($pesanan_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.email, pr.no_wa, pr.nama_ibu as nama
        FROM pesanan p
        JOIN user u ON p.user_id = u.id
        LEFT JOIN profil pr ON u.profil_id = pr.id
        WHERE p.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$pesanan_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $stmt = $pdo->prepare("
            SELECT dp.*, b.nama_barang
            FROM detail_pesanan dp
            JOIN barang b ON dp.barang_id = b.id
            WHERE dp.pesanan_id = ?
        ");
        $stmt->execute([$pesanan_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cart_items as $item) {
            $total += $item['harga_satuan'] * $item['jumlah'];
        }
        $total_pembayaran = $total;
    } else {
        header("Location: menu.php");
        exit;
    }
} else {
    header("Location: menu.php");
    exit;
}
$allowed_methods = ['QRIS', 'BRI', 'BCA', 'Mandiri'];
if (!in_array($order['metode_pembayaran'], $allowed_methods)) {
    header("Location: menu.php");
    exit;
}

// Handle file upload
$errors = [];
$success = '';
$uploaded_file = null;
$stmt = $pdo->prepare("SELECT path_file FROM bukti_pembayaran WHERE pesanan_id = ?");
$stmt->execute([$pesanan_id]);
$uploaded_file = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_pembayaran']) && !$uploaded_file) {
    $file = $_FILES['bukti_pembayaran'];
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = "File harus berupa JPG, PNG, atau PDF.";
    } elseif ($file['size'] > $max_size) {
        $errors[] = "Ukuran file maksimal 5MB.";
    } else {
        $upload_dir = 'uploads/bukti_pembayaran/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = 'bukti_' . $pesanan_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO bukti_pembayaran (pesanan_id, path_file, waktu_upload)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$pesanan_id, $file_path]);

                $pdo->commit();
                $_SESSION['success_message'] = "Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.";
                header("Location: struk.php?pesanan_id=".$pesanan_id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Gagal menyimpan bukti pembayaran: " . $e->getMessage();
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        } else {
            $errors[] = "Gagal mengunggah file.";
        }
    }
} else {
    $errors[] = "Terjadi kesalahan saat mengunggah file.";
}
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file']) && $uploaded_file) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM bukti_pembayaran WHERE pesanan_id = ?");
        $stmt->execute([$pesanan_id]);

        $stmt = $pdo->prepare("UPDATE pesanan SET status = 'pending' WHERE id = ?");
        $stmt->execute([$pesanan_id]);

        if (file_exists($uploaded_file['path_file'])) {
            unlink($uploaded_file['path_file']);
        }

        $pdo->commit();
        $success = "Bukti pembayaran berhasil dihapus!";
        $uploaded_file = null;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Gagal menghapus bukti pembayaran: " . $e->getMessage();
    }
}

// Notification count
$notification_count = 0;
if ($user_id) {
    $stmt_barang = $pdo->prepare("SELECT COUNT(*) as count FROM barang ORDER BY id DESC LIMIT 5");
    $stmt_barang->execute();
    $barang_count = $stmt_barang->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt_promo = $pdo->prepare("SELECT COUNT(*) as count FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
    $stmt_promo->execute();
    $promo_count = $stmt_promo->fetch(PDO::FETCH_ASSOC)['count'];

    $notification_count = $barang_count + $promo_count;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Pembayaran - Florelei Flower.co</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            min-height: 60vh;
        }

        .payment-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .payment-info {
            flex: 1;
            min-width: 300px;
        }

        .order-details {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 15px;
            color: #333;
            margin-bottom: 8px;
            display: block;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 15px;
            color: #333;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #2d5016;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(45, 80, 22, 0.08);
        }

        .file-upload {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 150px;
            border: 2px dashed #e8e8e8;
            border-radius: 12px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: #2d5016;
            background: white;
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-text {
            text-align: center;
            color: #666;
            font-size: 15px;
        }

        .file-upload-text svg {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
            color: #2d5016;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
            color: #2d5016;
            padding: 15px 0;
        }

        .btn-upload {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2d5016 0%, #1a3009 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
            background: linear-gradient(135deg, #1a3009 0%, #0f1f05 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45, 80, 22, 0.3);
        }

        .btn-delete {
            width: 100%;
            padding: 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45, 80, 22, 0.3);
        }

        .order-info {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .order-info-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .order-info-item:last-child {
            border-bottom: none;
        }

        .order-info-icon {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            color: #2d5016;
        }

        .order-info-label {
            font-size: 14px;
            color: #666;
            margin-right: 10px;
            min-width: 100px;
        }

        .order-info-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .countdown {
            font-size: 24px;
            font-weight: bold;
            color: #2d5016;
            text-align: center;
            margin-bottom: 20px;
        }

        .qrcode-placeholder {
            text-align: center;
            margin: 20px 0 30px;
        }

        .qrcode-placeholder img {
            width: 200px;
            height: 200px;
        }

        @media (max-width: 768px) {
            .payment-container {
                flex-direction: column;
            }

            .order-info {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .order-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .order-info-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="menu.php">
                    <img src="img/logo.png" alt="Florelei" class="logo-img">
                </a>
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Cari produk bunga..." class="search-input">
                <button class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                </button>
            </div>
            <div class="header-icons">
                <a href="notif.php" class="icon-btn" style="position: relative; display: inline-flex;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="keranjang.php" class="icon-btn cart-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                </a>
                <a href="profil.php" class="icon-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <section class="payment-section">
                <h2 class="section-title">Halaman Pembayaran</h2>
                <?php if (!empty($errors)): ?>
                    <div class="notif error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="notif success">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>
                <div class="countdown" id="countdown">23:59:45</div>
                <div class="order-info">
                    <h3 class="section-title">Informasi Pesanan</h3>
                    <div class="order-info-item">
                        <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span class="order-info-label">Order ID</span>
                        <span class="order-info-value">FLR<?php echo str_pad($pesanan_id, 8, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
                <div class="payment-container">
                    <!-- Payment Information -->
                    <div class="payment-info">
                        <div class="qrcode-placeholder">
                            <img src="img/qr code.svg" alt="QR Code" width="200" height="200">
                            <p>Scan untuk pembayaran</p>
                        </div>
<div class="order-info">
    <h3 class="section-title">Informasi Pembayaran</h3>
    <?php if ($order['metode_pembayaran'] === 'QRIS'): ?>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span class="order-info-label">Metode</span>
            <span class="order-info-value">QRIS</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            </svg>
            <span class="order-info-label">Instruksi</span>
            <span class="order-info-value">Scan QR Code di atas</span>
        </div>
    <?php elseif ($order['metode_pembayaran'] === 'BRI'): ?>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span class="order-info-label">Bank</span>
            <span class="order-info-value">Bank BRI</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            </svg>
            <span class="order-info-label">No. Rekening</span>
            <span class="order-info-value">1234-5678-9012-3456</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span class="order-info-label">Atas Nama</span>
            <span class="order-info-value">PT Florelei Indonesia</span>
        </div>
    <?php elseif ($order['metode_pembayaran'] === 'BCA'): ?>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span class="order-info-label">Bank</span>
            <span class="order-info-value">Bank BCA</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            </svg>
            <span class="order-info-label">No. Rekening</span>
            <span class="order-info-value">4567-8901-2345-6789</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span class="order-info-label">Atas Nama</span>
            <span class="order-info-value">PT Florelei Indonesia</span>
        </div>
    <?php elseif ($order['metode_pembayaran'] === 'Mandiri'): ?>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span class="order-info-label">Bank</span>
            <span class="order-info-value">Bank Mandiri</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            </svg>
            <span class="order-info-label">No. Rekening</span>
            <span class="order-info-value">7890-1234-5678-9012</span>
        </div>
        <div class="order-info-item">
            <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span class="order-info-label">Atas Nama</span>
            <span class="order-info-value">PT Florelei Indonesia</span>
        </div>
    <?php endif; ?>
</div>
                        <form method="post" enctype="multipart/form-data" class="form-group">
                            <label class="form-label">Upload Bukti Pembayaran</label>
                            <div class="file-upload">
                                <input type="file" name="bukti_pembayaran" id="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf" <?php echo $uploaded_file ? 'disabled' : 'required'; ?>>
                                <div class="file-upload-text">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p><?php echo $uploaded_file ? basename($uploaded_file['path_file']) : 'Klik untuk upload bukti transfer<br>Maksimal 5MB (JPG, PNG, PDF)'; ?></p>
                                </div>
                            </div>
                            <?php if (!$uploaded_file): ?>
                                <button type="submit" class="btn-upload">Konfirmasi Pembayaran</button>
                            <?php endif; ?>
                        </form>
                        <?php if ($uploaded_file): ?>
                            <form method="post" class="form-group">
                                <input type="hidden" name="delete_file" value="1">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <!-- Order Details -->
                    <div class="order-details">
                        <h3 class="section-title">Detail Pesanan</h3>
                        <div class="order-info">
                            <h4>Data Penerima</h4>
                            <div class="order-info-item">
                                <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span class="order-info-label">Nama</span>
                                <span class="order-info-value"><?php echo htmlspecialchars($order['nama'] ?? 'Tidak tersedia'); ?></span>
                            </div>
                            <div class="order-info-item">
                                <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span class="order-info-label">Email</span>
                                <span class="order-info-value"><?php echo htmlspecialchars($order['email'] ?? 'Tidak tersedia'); ?></span>
                            </div>
                            <div class="order-info-item">
                                <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <span class="order-info-label">Telepon</span>
                                <span class="order-info-value"><?php echo htmlspecialchars($order['no_wa'] ?? 'Tidak tersedia'); ?></span>
                            </div>
                            <div class="order-info-item">
                                <svg class="order-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="order-info-label">Alamat</span>
                                <span class="order-info-value"><?php echo htmlspecialchars($order['alamat_pengiriman'] ?? 'Tidak tersedia'); ?></span>
                            </div>
                        </div>
                        <h4>Item Pesanan</h4>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item">
                                <span><?php echo htmlspecialchars($item['nama_barang']); ?> (<?php echo $item['jumlah']; ?>x Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>)</span>
                                <span>Rp <?php echo number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total Pembayaran</span>
                            <span>Rp <?php echo number_format($total_pembayaran, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Countdown timer
        document.addEventListener('DOMContentLoaded', function() {
            const countdownElement = document.getElementById('countdown');
            let timeLeft = 24 * 60 * 60; // 24 hours
            
            function updateCountdown() {
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                countdownElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                timeLeft--;
                
                if (timeLeft < 0) {
                    countdownElement.textContent = "Waktu pembayaran habis";
                    clearInterval(countdownInterval);
                }
            }
            
            const countdownInterval = setInterval(updateCountdown, 1000);
            updateCountdown();

            // File upload preview
            const fileInput = document.getElementById('bukti_pembayaran');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileName = this.files[0]?.name || 'Klik untuk upload bukti transfer<br>Maksimal 5MB (JPG, PNG, PDF)';
                    const fileUploadText = document.querySelector('.file-upload-text p');
                    fileUploadText.innerHTML = fileName;
                });
            }
        });
    </script>
</body>
</html>