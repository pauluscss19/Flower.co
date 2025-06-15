<?php
session_start();
include "conn.php";

$notification_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt_barang = $pdo->prepare("SELECT COUNT(*) as count FROM barang ORDER BY id DESC LIMIT 5");
    $stmt_barang->execute();
    $barang_count = $stmt_barang->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt_promo = $pdo->prepare("SELECT COUNT(*) as count FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
    $stmt_promo->execute();
    $promo_count = $stmt_promo->fetch(PDO::FETCH_ASSOC)['count'];

    $notification_count = $barang_count + $promo_count;
}

$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt_user = $pdo->prepare("SELECT u.username, u.email, p.no_wa 
                              FROM user u 
                              LEFT JOIN profil p ON u.profil_id = p.id 
                              WHERE u.id = ?");
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
} else {
    $user_data = [];
}

if (!$user_id) {
    header("Location: login.php?redirect=form_pembelian.php");
    exit;
}

$direct_product_id = $_GET['direct_product_id'] ?? null;
$direct_quantity = $_GET['quantity'] ?? 1;

if ($direct_product_id) {
    // Handle direct product purchase (Beli Sekarang)
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
    $stmt->execute([$direct_product_id]);
    $direct_product = $stmt->fetch();

    if ($direct_product) {
        $cart_items = [[
            'id' => 0,
            'jumlah' => $direct_quantity,
            'barang_id' => $direct_product['id'],
            'nama_barang' => $direct_product['nama_barang'],
            'harga' => $direct_product['harga'],
            'gambar' => $direct_product['gambar'],
            'stok' => $direct_product['stok']
        ]];
        $total = $direct_product['harga'] * $direct_quantity;
    } else {
        header("Location: lihat_produk.php");
        exit;
    }
} else {
    // Handle cart purchase
    $stmt = $pdo->prepare("SELECT ki.id, ki.jumlah, b.id as barang_id, b.nama_barang, b.harga, b.gambar, b.stok 
                          FROM keranjang k 
                          JOIN keranjang_item ki ON k.id = ki.keranjang_id 
                          JOIN barang b ON ki.barang_id = b.id 
                          WHERE k.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['harga'] * $item['jumlah'];
    }
}

// Proses form checkout
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamat = trim($_POST['alamat'] ?? '');
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? '';

    // Validasi input
    if (empty($alamat)) {
        $errors[] = "Alamat pengiriman wajib diisi.";
    }
    if (!in_array($metode_pembayaran, ['Cash', 'QRIS', 'BRI', 'BCA', 'Mandiri'])) {
        $errors[] = "Metode pembayaran tidak valid.";
    }
    if (empty($cart_items)) {
        $errors[] = "Keranjang belanja kosong.";
    }

    // Cek stok barang
    foreach ($cart_items as $item) {
        if ($item['jumlah'] > $item['stok']) {
            $errors[] = "Stok untuk {$item['nama_barang']} tidak mencukupi.";
        }
    }

    // Jika tidak ada error, simpan pesanan
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Simpan ke tabel pesanan
            $stmt = $pdo->prepare("
                INSERT INTO pesanan (user_id, alamat_pengiriman, metode_pembayaran, tanggal_pemesanan, status) 
                VALUES (?, ?, ?, NOW(), 'pending')
            ");
            $stmt->execute([$user_id, $alamat, $metode_pembayaran]);
            $pesanan_id = $pdo->lastInsertId();

            // Simpan detail pesanan
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO detail_pesanan (pesanan_id, barang_id, jumlah, harga_satuan) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$pesanan_id, $item['barang_id'], $item['jumlah'], $item['harga']]);

                // Kurangi stok barang
                $stmt = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                $stmt->execute([$item['jumlah'], $item['barang_id']]);
            }

            // Kosongkan keranjang
            $stmt = $pdo->prepare("DELETE FROM keranjang_item WHERE keranjang_id IN (SELECT id FROM keranjang WHERE user_id = ?)");
            $stmt->execute([$user_id]);

            $pdo->commit();
            $success = "Pesanan berhasil dibuat! ";

            // Arahkan ke halaman unggah bukti pembayaran jika metode bukan Cash
            if ($metode_pembayaran !== 'Cash') {
                header("Location: upload_bukti.php?pesanan_id=$pesanan_id");
                exit;
            } else {
                $success .= "Silakan lakukan pembayaran secara tunai saat barang diterima.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Terjadi kesalahan saat menyimpan pesanan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pembelian - Florelei Flower.co</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            min-height: 60vh;
        }

        .checkout-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .checkout-form {
            flex: 1;
            min-width: 300px;
        }

        .checkout-summary {
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

        /* Custom Select Styles */
        .custom-select-wrapper {
            position: relative;
            width: 100%;
        }

        .custom-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 15px;
            color: #333;
            background-color: #f8f9fa;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .custom-select:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 4px rgba(45, 80, 22, 0.08);
        }

        .custom-select-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            z-index: 10;
            display: none;
        }

        .custom-select-options.show {
            display: block;
        }

        .custom-select-option {
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .custom-select-option:hover {
            background: #f0f0f0;
        }

        .payment-logo {
            width: 24px;
            height: 24px;
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

        .btn-checkout {
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

        .btn-checkout:hover {
            background: linear-gradient(135deg, #1a3009 0%, #0f1f05 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45, 80, 22, 0.3);
        }

        .user-info {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .user-info-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .user-info-item:last-child {
            border-bottom: none;
        }

        .user-info-icon {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            color: #2d5016;
        }

        .user-info-label {
            font-size: 14px;
            color: #666;
            margin-right: 10px;
            min-width: 100px;
        }

        .user-info-value {
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

        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }

            .user-info {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .user-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .user-info-label {
                min-width: auto;
            }

            .custom-select-option {
                padding: 8px 12px;
            }

            .payment-logo {
                width: 20px;
                height: 20px;
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
            <section class="checkout-section">
                <h2 class="section-title">Form Pembelian</h2>
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
                <?php if (!empty($cart_items)): ?>
                    <div class="user-info">
                        <h3 class="section-title">Informasi Pengguna</h3>
                        <div class="user-info-item">
                            <svg class="user-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="user-info-label">Username</span>
                            <span class="user-info-value"><?php echo htmlspecialchars($user_data['username'] ?? 'Tidak tersedia'); ?></span>
                        </div>
                        <div class="user-info-item">
                            <svg class="user-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <span class="user-info-label">Email</span>
                            <span class="user-info-value"><?php echo htmlspecialchars($user_data['email'] ?? 'Tidak tersedia'); ?></span>
                        </div>
                        <div class="user-info-item">
                            <svg class="user-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span class="user-info-label">No. WhatsApp</span>
                            <span class="user-info-value"><?php echo htmlspecialchars($user_data['no_wa'] ?? 'Tidak tersedia'); ?></span>
                        </div>
                    </div>
                    <div class="checkout-container">
                        <!-- Form Checkout -->
<form method="post" class="checkout-form">
    <div class="form-group">
        <label class="form-label">Alamat Pengiriman</label>
        <textarea name="alamat" class="form-input" rows="4" placeholder="Masukkan alamat lengkap..." required><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">Metode Pembayaran</label>
        <div class="custom-select-wrapper">
            <div class="custom-select">
                <span class="custom-select-text">Pilih metode pembayaran</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="custom-select-options">
<div class="custom-select-option" data-value="Cash">
    <img class="payment-logo" src="img/cash.svg" alt="Cash" width="24" height="24">
    <span>Cash</span>
</div>
<div class="custom-select-option" data-value="QRIS">
    <img class="payment-logo" src="img/qris.png" alt="QRIS" width="24" height="24">
    <span>QRIS</span>
</div>
<div class="custom-select-option" data-value="BRI">
    <img class="payment-logo" src="img/bri.png" alt="BRI" width="24" height="24">
    <span>BRI</span>
</div>
<div class="custom-select-option" data-value="BCA">
    <img class="payment-logo" src="img/bca.svg" alt="BCA" width="24" height="24">
    <span>BCA</span>
</div>
<div class="custom-select-option" data-value="Mandiri">
    <img class="payment-logo" src="img/livin.png" alt="Mandiri" width="24" height="24">
    <span>Mandiri</span>
</div>
            </div>
            <input type="hidden" name="metode_pembayaran" id="metode_pembayaran">
        </div>
    </div>
    <button type="submit" class="btn-checkout">Buat Pesanan</button>
</form>
                        <!-- Ringkasan Pesanan -->
                        <div class="checkout-summary">
                            <h3 class="section-title">Ringkasan Pesanan</h3>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="summary-item">
                                    <span><?php echo htmlspecialchars($item['nama_barang']); ?> (x<?php echo $item['jumlah']; ?>)</span>
                                    <span>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="summary-total">
                                <span>Total</span>
                                <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cart-empty">
                        <div class="cart-empty-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </div>
                        <p class="cart-empty-text">Keranjang Anda kosong.</p>
                        <a href="lihat_produk.php" class="btn-shop">Lihat Produk</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customSelect = document.querySelector('.custom-select');
            const customSelectText = customSelect.querySelector('.custom-select-text');
            const customSelectOptions = document.querySelector('.custom-select-options');
            const options = customSelectOptions.querySelectorAll('.custom-select-option');
            const hiddenInput = document.querySelector('#metode_pembayaran');

            customSelect.addEventListener('click', function() {
                customSelectOptions.classList.toggle('show');
            });

            options.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    customSelectText.textContent = this.querySelector('span').textContent;
                    hiddenInput.value = value;
                    customSelectOptions.classList.remove('show');
                });
            });

            document.addEventListener('click', function(e) {
                if (!customSelect.contains(e.target)) {
                    customSelectOptions.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>