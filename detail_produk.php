<?php
session_start();
include "conn.php";

$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;
error_log("Accessing detail_produk.php with barang_id: $id_barang");

// Ambil notifikasi dari session jika ada
$notification = $_SESSION['notification'] ?? '';
unset($_SESSION['notification']); // Hapus notifikasi setelah ditampilkan

$stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->execute([$id_barang]);
$product = $stmt->fetch();

if (!$product) {
    error_log("Product not found for barang_id: $id_barang");
    echo "<p>Produk tidak ditemukan.</p>";
    exit;
}

$cart_count = 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT SUM(ki.jumlah) as total FROM keranjang k JOIN keranjang_item ki ON k.id = ki.keranjang_id WHERE k.user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ? $result['total'] : 0;
}

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

if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=detail_produk.php?id=" . $id_barang);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $quantity = 1;

    $stmt = $pdo->prepare("SELECT id FROM keranjang WHERE user_id = ? ORDER BY dibuat_pada DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $pdo->prepare("INSERT INTO keranjang (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['id'];
    }

    $stmt = $pdo->prepare("SELECT id, jumlah FROM keranjang_item WHERE keranjang_id = ? AND barang_id = ?");
    $stmt->execute([$cart_id, $id_barang]);
    $cart_item = $stmt->fetch();

    try {
        $stmt = $pdo->prepare("SELECT stok FROM barang WHERE id = ?");
        $stmt->execute([$id_barang]);
        $stok = $stmt->fetchColumn();

        if ($stok < ($cart_item ? $cart_item['jumlah'] + $quantity : $quantity)) {
            $_SESSION['notification'] = '<div class="notif error">Stok tidak mencukupi untuk produk ini.</div>';
        } else {
            if ($cart_item) {
                $new_quantity = $cart_item['jumlah'] + $quantity;
                $stmt = $pdo->prepare("UPDATE keranjang_item SET jumlah = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $cart_item['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO keranjang_item (keranjang_id, barang_id, jumlah) VALUES (?, ?, ?)");
                $stmt->execute([$cart_id, $id_barang, $quantity]);
            }
            $_SESSION['notification'] = '<div class="notif success">Produk berhasil ditambahkan ke keranjang!</div>';
        }
    } catch (Exception $e) {
        $_SESSION['notification'] = '<div class="notif error">Gagal menambahkan produk ke keranjang.</div>';
    }

    // Redirect untuk menghindari form resubmission
    header("Location: detail_produk.php?id=" . $id_barang);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Barang - Florelei Flower.co</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Badge */
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
        
        /* Notification styles */
        .notif {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .notif.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .notif.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                <a href="keranjang.php" class="icon-btn cart-icon" style="position: relative;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
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
    <main>
        <div class="container">
            <?php if (!empty($notification)): ?>
                <?php echo $notification; ?>
            <?php endif; ?>

            <section class="detail-section">
                <div class="detail-container">
                    <div class="detail-image">
                        <img src="Uploads/<?php echo htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>">
                    </div>

                    <div class="detail-info">
                        <h2 class="detail-title"><?php echo htmlspecialchars($product['nama_barang']); ?></h2>
                        
                        <?php if (!empty($product['deskripsi'])): ?>
                            <div class="detail-description">
                                <?php echo nl2br(htmlspecialchars($product['deskripsi'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="detail-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                        <p class="detail-stock">Stok: <?php echo htmlspecialchars($product['stok']); ?></p>
                        <p class="detail-sold">Terjual: <?php echo htmlspecialchars($product['terjual']); ?></p>
                        <div class="button-container">
                            <form method="POST" action="">
                                <button type="submit" name="add_to_cart" class="btn-add-to-cart">Tambah ke Keranjang</button>
                            </form>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="GET" action="form_pembelian.php">
                                    <input type="hidden" name="direct_product_id" value="<?php echo $id_barang; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn-buy-now">Beli Sekarang</button>
                                </form>
                            <?php else: ?>
                                <form method="GET" action="login.php">
                                    <input type="hidden" name="redirect" value="detail_produk.php?id=<?php echo $id_barang; ?>">
                                    <button type="submit" class="btn-buy-now">Beli Sekarang</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>