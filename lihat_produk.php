<?php
session_start(); 
include "conn.php";
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
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
$search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
$urutkan = isset($_REQUEST['urutkan']) ? trim($_REQUEST['urutkan']) : 'terbaru';

$search = htmlspecialchars($search);
$urutkan = in_array($urutkan, ['terbaru', 'termurah', 'termahal', 'terlaris']) ? $urutkan : 'terbaru';

$sql = "SELECT * FROM barang";
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "nama_barang LIKE ?";
    $params[] = "%$search%";
}

$order_by = match ($urutkan) {
    'termurah' => "harga ASC",
    'termahal' => "harga DESC",
    'terlaris' => "terjual DESC",
    default => "id DESC" // terbaru
};

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY $order_by";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ob_start();
    if (empty($products)) {
        echo '<p class="hlm-produk-empty">Tidak ada produk ditemukan.</p>';
    } else {
        foreach ($products as $product) {
            ?>
            <div class="hlm-produk-card">
                <div class="hlm-produk-image">
                    <img src="uploads/<?php echo htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>">
                    <?php if ($product['stok'] == 0): ?>
                        <span class="hlm-produk-stok habis">Habis</span>
                    <?php elseif ($product['stok'] < 5): ?>
                        <span class="hlm-produk-stok sedikit">Hampir Habis</span>
                    <?php endif; ?>
                </div>
                <div class="hlm-produk-info">
                    <h3 class="hlm-produk-name"><?php echo htmlspecialchars($product['nama_barang']); ?></h3>
                    <p class="hlm-produk-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                    <p class="hlm-produk-stok-info">Stok: <?php echo $product['stok']; ?></p>
                    <p class="hlm-produk-terjual">Terjual: <?php echo $product['terjual']; ?></p>
                    <a href="detail_produk.php?id=<?php echo $product['id']; ?>" class="hlm-produk-btn">Lihat Detail Produk</a>
                </div>
            </div>
            <?php
        }
    }
    $html = ob_get_clean();
    echo $html;
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florelei Flower.co - Produk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
    <div class="container">
        <div class="logo">
            <a href="menu.php">
                <img src="img/logo.png" alt="Florelei" class="logo-img">
            </a>
        </div>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Cari produk bunga..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                <button class="search-btn" id="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                </button>
            </div>
            
            <div class="header-icons">
                <a href="notif.php" class="icon-btn"  style="position: relative;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                 <?php if ($notification_count > 0): ?>
                    <span class="notification-badge"><?php echo $notification_count; ?></span>
                <?php endif; ?>
                </a>
                <a href="keranjang.php" class="icon-btn"  style="position: relative;">
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
    <main class="hlm-produk">
        <div class="container">
            <section class="hlm-produk-header">
                <h1 class="hlm-produk-title">Semua Produk Bunga</h1>
                <p class="hlm-produk-subtitle">Temukan rangkaian bunga terbaik untuk setiap momen spesial</p>
            </section>

            <section class="hlm-produk-filter">
                <div class="hlm-produk-filter-group">
                    <label for="kategori" class="hlm-produk-filter-label">Kategori:</label>
                    <select id="kategori" class="hlm-produk-filter-select" disabled title="Fitur kategori belum tersedia">
                        <option value="semua">Semua Kategori</option>
                        <option value="buket">Buket Bunga</option>
                        <option value="pot">Tanaman Pot</option>
                        <option value="hadiah">Hadiah Bunga</option>
                    </select>
                </div>
                <div class="hlm-produk-filter-group">
                    <label for="urutkan" class="hlm-produk-filter-label">Urutkan:</label>
                    <select id="urutkan" class="hlm-produk-filter-select">
                        <option value="terbaru" <?php echo $urutkan === 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="termurah" <?php echo $urutkan === 'termurah' ? 'selected' : ''; ?>>Harga Terendah</option>
                        <option value="termahal" <?php echo $urutkan === 'termahal' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                        <option value="terlaris" <?php echo $urutkan === 'terlaris' ? 'selected' : ''; ?>>Terlaris</option>
                    </select>
                </div>
            </section>

            <section class="hlm-produk-grid" id="produk-grid">
                <?php if (empty($products)): ?>
                    <p class="hlm-produk-empty">Tidak ada produk ditemukan.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="hlm-produk-card">
                            <div class="hlm-produk-image">
                                <img src="uploads/<?php echo htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>">
                                <?php if ($product['stok'] == 0): ?>
                                    <span class="hlm-produk-stok habis">Habis</span>
                                <?php elseif ($product['stok'] < 5): ?>
                                    <span class="hlm-produk-stok sedikit">Hampir Habis</span>
                                <?php endif; ?>
                            </div>
                            <div class="hlm-produk-info">
                                <h3 class="hlm-produk-name"><?php echo htmlspecialchars($product['nama_barang']); ?></h3>
                                <p class="hlm-produk-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                                <p class="hlm-produk-stok-info">Stok: <?php echo $product['stok']; ?></p>
                                <p class="hlm-produk-terjual">Terjual: <?php echo $product['terjual']; ?></p>
                                <a href="detail_produk.php?id=<?php echo $product['id']; ?>" class="hlm-produk-btn">Lihat Detail Produk</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <style>
        .hlm-produk {
            padding: 30px 0;
            background-color: #f9f9f9;
            min-height: calc(100vh - 75px);
        }
        .hlm-produk-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .hlm-produk-title {
            font-size: 28px;
            color: #2d5016;
            margin-bottom: 10px;
        }
        .hlm-produk-subtitle {
            color: #666;
            font-size: 16px;
        }
        .hlm-produk-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .hlm-produk-filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .hlm-produk-filter-label {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        .hlm-produk-filter-select {
            padding: 8px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            background-color: #f8f9fa;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .hlm-produk-filter-select:focus {
            outline: none;
            border-color: #2d5016;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(45, 80, 22, 0.08);
        }
        .hlm-produk-filter-select:disabled {
            background-color: #e0e0e0;
            cursor: not-allowed;
        }
        .hlm-produk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        .hlm-produk-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .hlm-produk-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .hlm-produk-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        .hlm-produk-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .hlm-produk-card:hover .hlm-produk-image img {
            transform: scale(1.05);
        }
        .hlm-produk-stok {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .hlm-produk-stok.habis {
            background-color: #cc0000;
        }
        .hlm-produk-stok.sedikit {
            background-color: #ff9900;
        }
        .hlm-produk-info {
            padding: 20px;
        }
        .hlm-produk-name {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .hlm-produk-price {
            font-size: 16px;
            color: #2d5016;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .hlm-produk-stok-info,
        .hlm-produk-terjual {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .hlm-produk-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #2d5016 0%, #1a3009 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }
        .hlm-produk-btn:hover {
            background: linear-gradient(135deg, #1a3009 0%, #0f1f05 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(45, 80, 22, 0.3);
        }
        .hlm-produk-empty {
            text-align: center;
            font-size: 16px;
            color: #666;
            margin: 20px 0;
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

        @media (max-width: 768px) {
            .hlm-produk-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
            }
            .hlm-produk-image {
                height: 180px;
            }
        }
        @media (max-width: 480px) {
            .hlm-produk-filter {
                flex-direction: column;
            }
            .hlm-produk-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .hlm-produk-image {
                height: 150px;
            }
            .hlm-produk-info {
                padding: 15px;
            }
            .hlm-produk-name {
                font-size: 16px;
            }
            .hlm-produk-price {
                font-size: 15px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-input');
            const searchBtn = document.getElementById('search-btn');
            const urutkanSelect = document.getElementById('urutkan');
            const produkGrid = document.getElementById('produk-grid');

            function updateProducts() {
                const search = searchInput.value.trim();
                const urutkan = urutkanSelect.value;

                fetch(`lihat_produk.php?search=${encodeURIComponent(search)}&urutkan=${urutkan}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    produkGrid.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    produkGrid.innerHTML = '<p class="hlm-produk-empty">Terjadi kesalahan saat memuat produk.</p>';
                });
            }

            // Event listeners
            searchBtn.addEventListener('click', updateProducts);
            searchInput.addEventListener('input', () => {
                if (searchInput.value.length >= 2 || searchInput.value.length === 0) {
                    updateProducts();
                }
            });
            urutkanSelect.addEventListener('change', updateProducts);

            // Update URL without reloading
            function updateURL() {
                const search = searchInput.value.trim();
                const urutkan = urutkanSelect.value;
                const url = new URL(window.location);
                url.searchParams.set('search', search);
                url.searchParams.set('urutkan', urutkan);
                history.pushState({}, '', url);
            }

            searchBtn.addEventListener('click', updateURL);
            searchInput.addEventListener('input', updateURL);
            urutkanSelect.addEventListener('change', updateURL);
        });
    </script>
</body>
</html>