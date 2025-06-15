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
// Handle actions (update/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $item_id = $_POST['item_id'];
    
    if ($_POST['action'] === 'delete') {
        // Delete item
        $stmt = $pdo->prepare("DELETE FROM keranjang_item WHERE id = ?");
        $stmt->execute([$item_id]);
    } elseif (in_array($_POST['action'], ['increase', 'decrease'])) {
        // Update quantity
        $stmt = $pdo->prepare("SELECT jumlah FROM keranjang_item WHERE id = ?");
        $stmt->execute([$item_id]);
        $current_quantity = $stmt->fetchColumn();
        
        $new_quantity = $current_quantity;
        if ($_POST['action'] === 'increase') {
            $new_quantity = $current_quantity + 1;
        } elseif ($_POST['action'] === 'decrease' && $current_quantity > 1) {
            $new_quantity = $current_quantity - 1;
        }
        
        $stmt = $pdo->prepare("UPDATE keranjang_item SET jumlah = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $item_id]);
    }

    
    // Redirect to avoid form resubmission
    header("Location: keranjang.php");
    exit;
}

// Get cart items
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php?redirect=keranjang.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT ki.id, ki.jumlah, b.nama_barang, b.harga, b.gambar 
    FROM keranjang k 
    JOIN keranjang_item ki ON k.id = ki.keranjang_id 
    JOIN barang b ON ki.barang_id = b.id 
    WHERE k.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['harga'] * $item['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Florelei Flower.co</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Cart Specific Styles */
        .cart-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            min-height: 60vh;
        }

        .cart-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            gap: 15px;
            position: relative;
        }

        .cart-image {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
            border: 1px solid #eee;
        }

        .cart-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
        }

        .cart-info {
            flex: 1;
        }

        .cart-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .cart-price {
            font-size: 15px;
            color: #2d5016;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .cart-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #f0f0f0;
        }

        .quantity {
            min-width: 20px;
            text-align: center;
        }

        .remove-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: #ff5252;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .cart-total-info {
            text-align: right;
        }

        .cart-total p {
            font-size: 18px;
            font-weight: bold;
            color: #2d5016;
            margin: 0;
        }

        .btn-buy-now {
            background: linear-gradient(135deg, #2d5016 0%, #1a3009 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-buy-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(45, 80, 22, 0.3);
        }

        /* Empty cart styles */
        .cart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 50vh;
            text-align: center;
        }

        .cart-empty-icon {
            margin-bottom: 20px;
        }

        .cart-empty-icon svg {
            width: 80px;
            height: 80px;
            color: #666;
        }

        .cart-empty-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        .btn-shop {
            background-color: #2d5016;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-shop:hover {
            background-color: #1a3009;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(45, 80, 22, 0.3);
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
            .cart-badge {
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

        /* Responsive styles */
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-image {
                width: 100%;
                height: 200px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 480px) {
            .cart-section {
                padding: 20px;
            }
            
            .cart-image {
                height: 150px;
            }
            
            .cart-empty-icon svg {
                width: 60px;
                height: 60px;
            }
            
            .cart-empty-text {
                font-size: 16px;
            }
            
            .cart-total {
                flex-direction: column;
                gap: 15px;
                align-items: flex-end;
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
                <a href="keranjang.php" class="icon-btn cart-icon style=" style="position: relative; display: inline-flex;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                    <?php if (!empty($cart_items)): ?>
                        <span class="cart-badge"><?php 
                            $count = 0;
                            foreach ($cart_items as $item) {
                                $count += $item['jumlah'];
                            }
                            echo $count;
                        ?></span>
                    <?php endif; ?>
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
            <section class="cart-section">
                <h2 class="section-title">Keranjang Belanja</h2>
                <?php if (empty($cart_items)): ?>
                    <div class="cart-empty">
                        <div class="cart-empty-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </div>
                        <p class="cart-empty-text">Keranjang Anda kosong.</p>
                        <a href="lihat_produk.php" class="btn-shop">Lihat Produk</a>
                    </div>
                <?php else: ?>
                    <div class="cart-container">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="cart-image">
                                    <img src="Uploads/<?php echo htmlspecialchars($item['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                </div>
                                <div class="cart-info">
                                    <h3 class="cart-name"><?php echo htmlspecialchars($item['nama_barang']); ?></h3>
                                    <p class="cart-price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></p>
                                    <div class="cart-actions">
                                        <form method="post" class="quantity-form">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                                            <span class="quantity"><?php echo htmlspecialchars($item['jumlah']); ?></span>
                                            <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="remove-btn">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cart-total">
                        <div class="cart-total-info">
                            <p>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
                        </div>
                        <a href="form_pembelian.php" class="btn-buy-now">Lanjut ke Pembayaran</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>