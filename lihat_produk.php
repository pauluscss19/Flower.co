<?php
include "conn.php";

// Ambil semua data produk dari database
$products = $pdo->query("SELECT * FROM barang")->fetchAll();
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
                <button class="icon-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </button>
                <button class="icon-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                </button>
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
                    <select id="kategori" class="hlm-produk-filter-select">
                        <option value="semua">Semua Kategori</option>
                        <option value="buket">Buket Bunga</option>
                        <option value="pot">Tanaman Pot</option>
                        <option value="hadiah">Hadiah Bunga</option>
                    </select>
                </div>
                <div class="hlm-produk-filter-group">
                    <label for="urutkan" class="hlm-produk-filter-label">Urutkan:</label>
                    <select id="urutkan" class="hlm-produk-filter-select">
                        <option value="terbaru">Terbaru</option>
                        <option value="termurah">Harga Terendah</option>
                        <option value="termahal">Harga Tertinggi</option>
                        <option value="terlaris">Terlaris</option>
                    </select>
                </div>
            </section>

            <section class="hlm-produk-grid">
                <?php foreach ($products as $product): ?>
                <div class="hlm-produk-card">
                    <div class="hlm-produk-image">
                        <img src="img/<?php echo htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>">
                        <?php if($product['stok'] == 0): ?>
                            <span class="hlm-produk-stok habis">Habis</span>
                        <?php elseif($product['stok'] < 5): ?>
                            <span class="hlm-produk-stok sedikit">Hampir Habis</span>
                        <?php endif; ?>
                    </div>
                    <div class="hlm-produk-info">
                        <h3 class="hlm-produk-name"><?php echo htmlspecialchars($product['nama_barang']); ?></h3>
                        <p class="hlm-produk-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                        <p class="hlm-produk-stok-info">Stok: <?php echo $product['stok']; ?></p>
                        <p class="hlm-produk-terjual">Terjual: <?php echo $product['terjual']; ?></p>
                        <button class="hlm-produk-btn">Lihat Detail Produk</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

    <style>
        /* Halaman Produk */
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
            border: 1px solid #ddd;
            border-radius: 20px;
            background-color: white;
            font-size: 14px;
            cursor: pointer;
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
            width: 100%;
            padding: 10px;
            background-color: #2d5016;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .hlm-produk-btn:hover {
            background-color: #1a3009;
        }

        /* Responsive Design */
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
                grid-template-columns: 1fr 1fr;
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
</body>
</html>