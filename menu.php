<?php
session_start(); // Mulai session
include "conn.php";

// Ambil data produk dari database
$products = $pdo->query("SELECT * FROM barang ORDER BY RAND() LIMIT 3")->fetchAll();

// Hitung jumlah item di keranjang
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT SUM(ki.jumlah) as total FROM keranjang k JOIN keranjang_item ki ON k.id = ki.keranjang_id WHERE k.user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ? $result['total'] : 0;
}
$notification_count = 0;
$stmt_barang = $pdo->prepare("SELECT COUNT(*) as count FROM barang ORDER BY id DESC LIMIT 5");
$stmt_barang->execute();
$barang_count = $stmt_barang->fetch(PDO::FETCH_ASSOC)['count'];

$stmt_promo = $pdo->prepare("SELECT COUNT(*) as count FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
$stmt_promo->execute();
$promo_count = $stmt_promo->fetch(PDO::FETCH_ASSOC)['count'];

$notification_count = $barang_count + $promo_count;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florelei Flower.co - Homepage</title>
    <link rel="stylesheet" href="style.css">
    <style>
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

    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="img/logo.png" alt="Florelei" class="logo-img">
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
                <a href="keranjang.php" class="icon-btn cart-icon" style="position: relative;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <button class="icon-btn">
                    <a href="profil.php">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <section class="hero-banners">
                <div class="banner-card">
                    <div class="banner-content">
                        <h3>Promo Spesial</h3>
                        <p>Diskon hingga 30%</p>
                    </div>
                </div>
                <div class="banner-card">
                    <div class="banner-content">
                        <h3>Bunga Fresh</h3>
                        <p>Langsung dari kebun</p>
                    </div>
                </div>
                <div class="banner-card">
                    <div class="banner-content">
                        <h3>Custom Bouquet</h3>
                        <p>Sesuai keinginan Anda</p>
                    </div>
                </div>
            </section>

            <!-- Menu Section -->
            <section class="menu-section">
                <div class="menu-container">
                    <a href="lihat_produk.php" div class="menu-item">
                        <class="menu-link" style="text-decoration: none; color: inherit;">
                            <div class="menu-icon">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                                </svg>
                            </div>
                            <span class="menu-text">Lihat Produk</span>
                    </a>
                    <div class="menu-item">
                        <div class="menu-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="7.5,4.21 12,6.81 16.5,4.21"></polyline>
                                <polyline points="7.5,19.79 7.5,14.6 3,12"></polyline>
                                <polyline points="21,12 16.5,14.6 16.5,19.79"></polyline>
                            </svg>
                        </div>
                        <span class="menu-text">Pesanan</span>
                    </div>
                    <div class="menu-item">
                        <div class="menu-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                        <span class="menu-text">Customize</span>
                    </div>
                    <a href="notif.php" class="menu-item">
                        <div class="menu-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge"><?php echo $notification_count; ?></span>
                                <?php endif; ?>
                        </div>
                        <span class="menu-text">Notification</span>
                    </a>
                </div>
            </section>

            <!-- Products Section -->
            <section class="products-section">
                <h2 class="section-title">Rekomendasi Produk</h2>
                <div class="products-container">
                    <?php foreach ($products as $product): ?>
                    <a href="detail_produk.php?id=<?php echo $product['id']; ?>" class="product-link" style="text-decoration: none; color: inherit;">
                        <div class="product-item">
                            <a href="detail_produk.php?id=<?php echo $product['id']; ?>" class="product-image-link">
                                <div class="product-image">
                                    <img src="Uploads/<?php echo htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" 
                                        alt="<?php echo htmlspecialchars($product['nama_barang']); ?>" class="product-img">
                                </div>
                            </a>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['nama_barang']); ?></h3>
                                <p class="product-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>  
            </section>
        </div>
    </main>
</body>
</html>