<?php
session_start();
include "conn.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=custom.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Notification count
$notification_count = 0;
$stmt_barang = $pdo->prepare("SELECT COUNT(*) as count FROM barang ORDER BY id DESC LIMIT 5");
$stmt_barang->execute();
$barang_count = $stmt_barang->fetch(PDO::FETCH_ASSOC)['count'];

$stmt_promo = $pdo->prepare("SELECT COUNT(*) as count FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
$stmt_promo->execute();
$promo_count = $stmt_promo->fetch(PDO::FETCH_ASSOC)['count'];

$notification_count = $barang_count + $promo_count;

// Fetch products from database
$stmt = $pdo->prepare("SELECT id, nama_barang, harga, gambar, deskripsi, stok FROM barang");
$stmt->execute();
$produk_bunga = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize temporary cart for selection (stored in session)
if (!isset($_SESSION['custom_cart'])) {
    $_SESSION['custom_cart'] = [];
}

// Handle quantity changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];

    // Check stock before increasing quantity
    $stmt = $pdo->prepare("SELECT stok FROM barang WHERE id = ?");
    $stmt->execute([$product_id]);
    $stok = $stmt->fetchColumn();

    if ($action === 'increase' && $stok > (isset($_SESSION['custom_cart'][$product_id]) ? $_SESSION['custom_cart'][$product_id]['jumlah'] : 0)) {
        if (isset($_SESSION['custom_cart'][$product_id])) {
            $_SESSION['custom_cart'][$product_id]['jumlah']++;
        } else {
            $stmt = $pdo->prepare("SELECT id, nama_barang, harga, gambar FROM barang WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                $_SESSION['custom_cart'][$product_id] = [
                    'nama_barang' => $product['nama_barang'],
                    'harga' => $product['harga'],
                    'gambar' => $product['gambar'],
                    'jumlah' => 1
                ];
            }
        }
    } elseif ($action === 'decrease') {
        if (isset($_SESSION['custom_cart'][$product_id]) && $_SESSION['custom_cart'][$product_id]['jumlah'] > 0) {
            $_SESSION['custom_cart'][$product_id]['jumlah']--;
            if ($_SESSION['custom_cart'][$product_id]['jumlah'] == 0) {
                unset($_SESSION['custom_cart'][$product_id]);
            }
        }
    }

    // Redirect to avoid form resubmission
    header("Location: custom.php");
    exit;
}

// Handle adding to main cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!empty($_SESSION['custom_cart'])) {
        // Get or create cart for user
        $stmt = $pdo->prepare("SELECT id FROM keranjang WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            $stmt = $pdo->prepare("INSERT INTO keranjang (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Add items to keranjang_item
        foreach ($_SESSION['custom_cart'] as $product_id => $item) {
            $stmt = $pdo->prepare("SELECT id FROM keranjang_item WHERE keranjang_id = ? AND barang_id = ?");
            $stmt->execute([$cart_id, $product_id]);
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_item) {
                $stmt = $pdo->prepare("UPDATE keranjang_item SET jumlah = jumlah + ? WHERE id = ?");
                $stmt->execute([$item['jumlah'], $existing_item['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO keranjang_item (keranjang_id, barang_id, jumlah) VALUES (?, ?, ?)");
                $stmt->execute([$cart_id, $product_id, $item['jumlah']]);
            }
        }

        // Clear custom cart
        $_SESSION['custom_cart'] = [];
        header("Location: keranjang.php");
        exit;
    }
}

// Calculate cart summary
$total_harga = 0;
$jumlah_barang = 0;
foreach ($_SESSION['custom_cart'] as $item) {
    $total_harga += $item['harga'] * $item['jumlah'];
    $jumlah_barang += $item['jumlah'];
}

// Get current cart items for badge
$stmt = $pdo->prepare("
    SELECT ki.jumlah
    FROM keranjang k 
    JOIN keranjang_item ki ON k.id = ki.keranjang_id 
    WHERE k.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cart_count = 0;
foreach ($cart_items as $item) {
    $cart_count += $item['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Bunga - Florelei</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e57373;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content {
            padding: 30px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 40px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            padding: 5px;
        }

        .product-info {
            padding: 15px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #e57373;
            margin: 0;
        }

        .product-stock {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
        }

        .quantity-btn:disabled {
            background: #f0f0f0;
            color: #ccc;
            cursor: not-allowed;
        }

        .quantity-btn:hover:not(:disabled) {
            background: #f0f0f0;
            border-color: #bbb;
        }

        .quantity-display {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            min-width: 30px;
            text-align: center;
        }

        .cart-summary {
            background: #f8f3a0;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .cart-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cart-label {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .cart-total {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .checkout-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .checkout-btn:hover {
            background: #45a049;
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
.out-of-stock {
    font-size: 16px;
    font-weight: bold;
    color: white; 
    background-color: #e57373; 
    padding: 8px 12px; 
    border-radius: 5px; 
    display: inline-flex; 
    align-items: center;
    justify-content: center;
    min-width: 100px; 
}

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .search-bar {
                width: 100%;
                max-width: none;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }

            .cart-summary {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4caf50;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
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
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-hexadecimal="#2d5016" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                </button>
            </div>
            <div class="header-icons">
                <a href="notif.php" class="icon-btn" style="position: relative;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-hexadecimal="#2d5016" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($notification_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background: #e57373; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center; pointer-events: none;">
                            <?php echo $notification_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                                <a href="keranjang.php" class="icon-btn" style="position: relative;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-hexadecimal="#2d5016" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background: #e57373; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center; pointer-events: none;">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="profil.php" class="icon-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-hexadecimal="#2d5016" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <div class="main-content">
        <h2 class="page-title">Customize Bunga</h2>
        
<div class="products-grid">
    <?php foreach ($produk_bunga as $produk): ?>
        <div class="product-card">
            <div class="product-image">
                <img src="Uploads/<?php echo htmlspecialchars($produk['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($produk['nama_barang']); ?>">
            </div>
            <div class="product-info">
                <h3 class="product-name"><?php echo htmlspecialchars($produk['nama_barang']); ?></h3>
                <p class="product-price">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></p>
                <p class="product-stock">Stok: <?php echo htmlspecialchars($produk['stok']); ?></p>
                <div class="quantity-controls">
                    <?php if ($produk['stok'] == 0): ?>
                        <span class="out-of-stock">Barang Habis</span> <!-- Hapus gaya inline -->
                    <?php else: ?>
                        <form method="post" action="custom.php" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $produk['id']; ?>">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit" class="quantity-btn" <?php echo (!isset($_SESSION['custom_cart'][$produk['id']]) || $_SESSION['custom_cart'][$produk['id']]['jumlah'] <= 0) ? 'disabled' : ''; ?>>-</button>
                        </form>
                        <span class="quantity-display">
                            <?php echo isset($_SESSION['custom_cart'][$produk['id']]) ? $_SESSION['custom_cart'][$produk['id']]['jumlah'] : 0; ?>
                        </span>
                        <form method="post" action="custom.php" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $produk['id']; ?>">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit" class="quantity-btn" <?php echo $produk['stok'] <= (isset($_SESSION['custom_cart'][$produk['id']]) ? $_SESSION['custom_cart'][$produk['id']]['jumlah'] : 0) ? 'disabled' : ''; ?>>+</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

        <div class="cart-summary">
            <div class="cart-info">
                <p class="cart-label">Jumlah Barang: <?php echo $jumlah_barang; ?> item</p>
                <p class="cart-total">Total Harga: Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></p>
            </div>
            <form method="post">
                <button type="submit" name="add_to_cart" class="checkout-btn" <?php echo $jumlah_barang == 0 ? 'disabled' : ''; ?>>Masukkan Ke Keranjang</button>
            </form>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>