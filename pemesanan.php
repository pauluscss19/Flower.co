    <?php
    session_start();
    include "conn.php";

    // Pastikan koneksi database aktif
    if (!$pdo) {
        die("Koneksi database gagal.");
    }

    // Anti-cache
    header("Cache-Control: no-cache, must-revalidate");

    $cart_count = 0;
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        header("Location: login.php");
        exit;
    }

    // Logging untuk debugging
    error_log("User ID: $user_id");

    // Hitung jumlah item di keranjang
    $stmt = $pdo->prepare("SELECT SUM(ki.jumlah) as total FROM keranjang k JOIN keranjang_item ki ON k.id = ki.keranjang_id WHERE k.user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ? $result['total'] : 0;

    // Hitung notifikasi
    $notification_count = 0;
    $stmt_barang = $pdo->prepare("SELECT COUNT(*) as count FROM barang ORDER BY id DESC LIMIT 5");
    $stmt_barang->execute();
    $barang_count = $stmt_barang->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt_promo = $pdo->prepare("SELECT COUNT(*) as count FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
    $stmt_promo->execute();
    $promo_count = $stmt_promo->fetch(PDO::FETCH_ASSOC)['count'];

    $notification_count = $barang_count + $promo_count;

    // Ambil data pengguna
    $stmt_user = $pdo->prepare("SELECT u.email, p.no_wa FROM user u LEFT JOIN profil p ON u.profil_id = p.id WHERE u.id = ?");
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // Proses aksi (hapus atau batalkan)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $order_id = $_POST['order_id'] ?? '';

        // Logging data POST
        error_log("POST Action: $action, Order ID: $order_id");

    if ($action === 'delete') {
        $pdo->beginTransaction();
        try {
            // Periksa apakah pesanan ada
            $stmt_check = $pdo->prepare("SELECT id FROM pesanan WHERE id = ? AND user_id = ?");
            $stmt_check->execute([$order_id, $user_id]);
            if (!$stmt_check->fetch()) {
                throw new Exception("Pesanan tidak ditemukan atau tidak dapat dihapus (order_id: $order_id, user_id: $user_id).");
            }

            // Hapus bukti pembayaran terlebih dahulu
            $stmt_delete_bukti = $pdo->prepare("DELETE FROM bukti_pembayaran WHERE pesanan_id = ?");
            $stmt_delete_bukti->execute([$order_id]);
            
            // Hapus detail pesanan
            $stmt_delete_details = $pdo->prepare("DELETE FROM detail_pesanan WHERE pesanan_id = ?");
            $stmt_delete_details->execute([$order_id]);
            
            // Hapus pesanan
            $stmt_delete_order = $pdo->prepare("DELETE FROM pesanan WHERE id = ? AND user_id = ?");
            $stmt_delete_order->execute([$order_id, $user_id]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Pesanan berhasil dihapus";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menghapus pesanan: " . $e->getMessage();
            error_log("Delete Error: " . $e->getMessage());
        }
    } elseif ($action === 'cancel') {
            $pdo->beginTransaction();
            try {
                // Periksa apakah pesanan ada dan belum dibatalkan
                $stmt_check = $pdo->prepare("SELECT id, status FROM pesanan WHERE id = ? AND user_id = ?");
                $stmt_check->execute([$order_id, $user_id]);
                $order = $stmt_check->fetch();
                if (!$order) {
                    throw new Exception("Pesanan tidak ditemukan (order_id: $order_id, user_id: $user_id).");
                }
                if ($order['status'] === 'dibatalkan') {
                    throw new Exception("Pesanan sudah dibatalkan (order_id: $order_id).");
                }

                // Perbarui status pesanan
                $stmt_cancel = $pdo->prepare("UPDATE pesanan SET status = 'dibatalkan' WHERE id = ? AND user_id = ?");
                $stmt_cancel->execute([$order_id, $user_id]);
                
                // Verifikasi apakah query berhasil
                if ($stmt_cancel->rowCount() === 0) {
                    throw new Exception("Gagal memperbarui status pesanan (order_id: $order_id, user_id: $user_id).");
                }

                // Logging status setelah pembatalan
                $stmt_check_status = $pdo->prepare("SELECT status FROM pesanan WHERE id = ?");
                $stmt_check_status->execute([$order_id]);
                $new_status = $stmt_check_status->fetchColumn();
                error_log("Status setelah pembatalan untuk order_id $order_id: $new_status");

                // Ambil item pesanan
                $stmt_get_items = $pdo->prepare("
                    SELECT dp.barang_id, dp.jumlah, b.nama_barang, b.harga
                    FROM detail_pesanan dp
                    JOIN barang b ON dp.barang_id = b.id
                    WHERE dp.pesanan_id = ?
                ");
                $stmt_get_items->execute([$order_id]);
                $items = $stmt_get_items->fetchAll(PDO::FETCH_ASSOC);
                
                // Cek keranjang
                $stmt_check_cart = $pdo->prepare("SELECT id FROM keranjang WHERE user_id = ?");
                $stmt_check_cart->execute([$user_id]);
                $cart = $stmt_check_cart->fetch();
                
                if (!$cart) {
                    $stmt_create_cart = $pdo->prepare("INSERT INTO keranjang (user_id) VALUES (?)");
                    $stmt_create_cart->execute([$user_id]);
                    $cart_id = $pdo->lastInsertId();
                } else {
                    $cart_id = $cart['id'];
                }

                foreach ($items as $item) {
                    $stmt_check_item = $pdo->prepare("
                        SELECT id, jumlah 
                        FROM keranjang_item 
                        WHERE keranjang_id = ? AND barang_id = ?
                    ");
                    $stmt_check_item->execute([$cart_id, $item['barang_id']]);
                    $existing_item = $stmt_check_item->fetch();
                    
                    if ($existing_item) {
                        $new_quantity = $existing_item['jumlah'] + $item['jumlah'];
                        $stmt_update_item = $pdo->prepare("UPDATE keranjang_item SET jumlah = ? WHERE id = ?");
                        $stmt_update_item->execute([$new_quantity, $existing_item['id']]);
                    } else {
                        $stmt_add_item = $pdo->prepare("
                            INSERT INTO keranjang_item (keranjang_id, barang_id, jumlah)
                            VALUES (?, ?, ?)
                        ");
                        $stmt_add_item->execute([$cart_id, $item['barang_id'], $item['jumlah']]);
                    }
                }
                $stmt_restock = $pdo->prepare("
                    UPDATE barang b
                    JOIN detail_pesanan dp ON b.id = dp.barang_id
                    SET b.stok = b.stok + dp.jumlah
                    WHERE dp.pesanan_id = ?
                ");
                $stmt_restock->execute([$order_id]);
                $pdo->commit();
                $_SESSION['success_message'] = "Pesanan berhasil dibatalkan dan item dikembalikan ke keranjang";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error_message'] = "Gagal membatalkan pesanan: " . $e->getMessage();
                error_log("Cancel Error: " . $e->getMessage());
            }
        }
        
        // Redirect untuk memastikan data segar
        header("Location: pemesanan.php");
        exit;
    }

    // Ambil data pesanan user setelah POST
    $stmt = $pdo->prepare("
        SELECT p.id, p.alamat_pengiriman, p.metode_pembayaran, p.tanggal_pemesanan, p.status,
            dp.jumlah, b.nama_barang, b.harga, b.gambar
        FROM pesanan p
        LEFT JOIN detail_pesanan dp ON p.id = dp.pesanan_id
        LEFT JOIN barang b ON dp.barang_id = b.id
        WHERE p.user_id = ? AND p.status NOT IN ('selesai', 'dibatalkan') 
        ORDER BY p.tanggal_pemesanan DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Logging data pesanan untuk debugging
    foreach ($orders as $order) {
        error_log("Order ID: {$order['id']}, Status: {$order['status']}");
    }

    // Validasi 15 menit untuk pesanan menunggu pembayaran
    $stmt_check = $pdo->prepare("
        SELECT id, tanggal_pemesanan, metode_pembayaran, status
        FROM pesanan
        WHERE user_id = ? AND status = 'pending' AND TIMESTAMPDIFF(MINUTE, tanggal_pemesanan, NOW()) > 15
    ");
    $stmt_check->execute([$user_id]);
    $expired_orders = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expired_orders as $order) {
        $stmt_update = $pdo->prepare("UPDATE pesanan SET status = 'dibatalkan' WHERE id = ?");
        $stmt_update->execute([$order['id']]);
        error_log("Expired Order ID: {$order['id']} set to dibatalkan");
    }
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pesanan Saya - Florelei Flower.co</title>
        <link rel="stylesheet" href="style.css">
        <style>
            .orders-section {
                background: white;
                border-radius: 15px;
                padding: 30px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                margin: 20px 0;
            }
            
            .section-title {
                font-size: 24px;
                font-weight: bold;
                color: #2d5016;
                margin-bottom: 25px;
                text-align: left;
                border-bottom: 2px solid #f0e68c;
                padding-bottom: 10px;
            }
            
            .empty-orders {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 16px;
            }
            
            .order-card {
                background: white;
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 25px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
                border: 1px solid #e0e0e0;
                transition: all 0.3s ease;
            }
            
            .order-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            }
            
            .order-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px dashed #e0e0e0;
            }
            
            .order-id-status {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .order-id {
                font-weight: bold;
                color: #2d5016;
                font-size: 18px;
            }
            
            .order-date-status {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .order-date {
                color: #666;
                font-size: 14px;
            }
            
            .products-list {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .product-item {
                display: flex;
                gap: 15px;
                padding: 10px;
                border-radius: 10px;
                background: #f9f9f9;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
                border-radius: 8px;
                overflow: hidden;
                flex-shrink: 0;
            }
            
            .product-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .product-details {
                flex-grow: 1;
            }
            
            .product-name {
                font-weight: 600;
                color: #333;
                margin-bottom: 5px;
            }
            
            .product-meta {
                display: flex;
                gap: 15px;
                font-size: 14px;
                color: #666;
            }
            
            .product-price {
                color: #2d5016;
                font-weight: bold;
            }
            
            .order-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px dashed #e0e0e0;
            }
            
            .info-item {
                display: flex;
                align-items: flex-start;
                gap: 8px;
            }
            
            .info-icon {
                color: #2d5016;
                margin-top: 2px;
            }
            
            .info-label {
                font-weight: 500;
                color: #333;
                font-size: 14px;
            }
            
            .info-value {
                color: #666;
                font-size: 14px;
            }
            
            .order-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #e0e0e0;
            }
            
            .order-total {
                font-weight: bold;
                color: #2d5016;
                font-size: 18px;
            }
            
            .order-status {
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }
            
            .status-pending {
                background-color: #fff3cd;
                color: #856404;
            }
            
            .status-confirmed {
                background-color: #d4edda;
                color: #155724;
            }
            
            .status-cancelled {
                background-color: #f8d7da;
                color: #721c24;
            }
            
            .order-actions {
                display: flex;
                gap: 10px;
            }
            
            .btn-action {
                padding: 8px 15px;
                border-radius: 25px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                border: none;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }
            
            .btn-edit {
                background: #2d5016;
                color: white;
            }
            
            .btn-edit:hover {
                background: #1a3009;
                transform: translateY(-2px);
            }
            
            .btn-cancel {
                background: #f0f0f0;
                color: #666;
            }
            
            .btn-cancel:hover {
                background: #e0e0e0;
                transform: translateY(-2px);
            }
            
            .btn-delete {
                background: #e74c3c;
                color: white;
            }
            
            .btn-delete:hover {
                background: #c0392b;
                transform: translateY(-2px);
            }
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
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.3s ease-out;
            }

            .notification.success {
                background-color: #4CAF50;
            }

            .notification.error {
                background-color: #F44336;
            }

            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
    }
            
            @media (max-width: 768px) {
                .order-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
                
                .order-id-status, .order-date-status {
                    width: 100%;
                    justify-content: space-between;
                }
                
                .product-item {
                    flex-direction: column;
                }
                
                .product-image {
                    width: 100%;
                    height: 150px;
                }
                
                .order-info-grid {
                    grid-template-columns: 1fr;
                }
                
                .order-footer {
                    flex-direction: column;
                    gap: 15px;
                    align-items: flex-start;
                }
                
                .order-actions {
                    width: 100%;
                    flex-direction: column;
                    gap: 8px;
                }
                
                .btn-action {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="notification success">
                <?= $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="notification error">
                <?= $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
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
                    <a href="keranjang.php" class="icon-btn" style="position: relative; display: inline-flex;">
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
        <main class="main">
            <div class="container">
                <section class="orders-section">
                    <h2 class="section-title">Pesanan Saya</h2>
                    <?php if (empty($orders)): ?>
                        <div class="empty-orders">
                            <p>Anda belum memiliki pesanan aktif.</p>
                            <a href="menu.php" class="btn-shop" style="margin-top: 20px;">Mulai Belanja</a>
                        </div>
                    <?php else: ?>
                        <?php
                        $grouped_orders = [];
                        foreach ($orders as $order) {
                            $grouped_orders[$order['id']][] = $order;
                        }
                        
                        krsort($grouped_orders);
                        
                        foreach ($grouped_orders as $order_id => $items): 
                            $first_item = $items[0];
                            $total_order = 0;
                            
                                $status_map = [
                                    'pending' => ['class' => 'status-pending', 'text' => 'Menunggu Pembayaran'],
                                    'dibayar' => ['class' => 'status-confirmed', 'text' => 'Dikonfirmasi'],
                                    'dikirim' => ['class' => 'status-shipped', 'text' => 'Dikirim'],
                                    'selesai' => ['class' => 'status-completed', 'text' => 'Selesai'],
                                    'dibatalkan' => ['class' => 'status-cancelled', 'text' => 'Dibatalkan']
                                ];

                                $status_class = $status_map[$first_item['status']]['class'];
                                $status_text = $status_map[$first_item['status']]['text'];
                        ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id-status">
                                        <div class="order-id">Pesanan <?= htmlspecialchars($order_id) ?></div>
                                        <span class="order-status <?= $status_class ?>"><?= $status_text ?></span>
                                    </div>
                                    <div class="order-date-status">
                                        <div class="order-date"><?= date('d M Y H:i', strtotime($first_item['tanggal_pemesanan'])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="products-list">
                                    <?php foreach ($items as $item): 
                                        $total_order += $item['harga'] * $item['jumlah'];
                                    ?>
                                        <div class="product-item">
                                            <div class="product-image">
                                                <img src="Uploads/<?= htmlspecialchars($item['gambar'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>">
                                            </div>
                                            <div class="product-details">
                                                <div class="product-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                                                <div class="product-meta">
                                                    <span>Jumlah: <?= $item['jumlah'] ?></span>
                                                    <span class="product-price">Rp<?= number_format($item['harga'], 0, ',', '.') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="order-info-grid">
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="info-label">Nama Pemesan</div>
                                            <div class="info-value"><?= htmlspecialchars($_SESSION['username'] ?? 'Tidak tersedia') ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                <polyline points="22,6 12,13 2,6"></polyline>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?= htmlspecialchars($user_data['email'] ?? 'Tidak tersedia') ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="info-label">WhatsApp</div>
                                            <div class="info-value"><?= htmlspecialchars($user_data['no_wa'] ?? 'Tidak tersedia') ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="info-label">Alamat</div>
                                            <div class="info-value"><?= htmlspecialchars($first_item['alamat_pengiriman']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                                <path d="M2 10h20M7 14h1m4 0h1m4 0h1"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="info-label">Pembayaran</div>
                                            <div class="info-value"><?= htmlspecialchars($first_item['metode_pembayaran']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-footer">
                                    <div class="order-total">
                                        Total: Rp<?= number_format($total_order, 0, ',', '.') ?>
                                    </div>
                                    
    <div class="order-actions">
        <?php if ($first_item['status'] === 'dibatalkan'): ?>
            <!-- Tombol untuk pesanan dibatalkan -->
            <a href="form_pembelian.php?order_id=<?= $order_id ?>" class="btn-action btn-edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Ubah Pembayaran
            </a>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <button type="submit" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus pesanan?');">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                    Hapus
                </button>
            </form>
        <?php elseif ($first_item['status'] === 'dibayar'): ?>
            <!-- Tombol untuk pesanan sudah dibayar -->
            <?php if ($first_item['metode_pembayaran'] === 'Cash'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <button type="submit" class="btn-action btn-cancel" onclick="return confirm('Yakin ingin membatalkan pesanan?');">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        Batalkan
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <!-- Tombol untuk pesanan pending -->
            <a href="form_pembelian.php?order_id=<?= $order_id ?>" class="btn-action btn-edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Ubah Pembayaran
            </a>
            
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <button type="submit" class="btn-action btn-cancel" onclick="return confirm('Yakin ingin membatalkan pesanan?');">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Batalkan
                </button>
            </form>
            
            <?php if ($first_item['metode_pembayaran'] !== 'Cash'): ?>
                <a href="bukti_bayar.php?order_id=<?= $order_id ?>" class="btn-action btn-edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Upload Bukti
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </body>
    </html>