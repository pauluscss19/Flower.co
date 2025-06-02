<?php
include "conn.php";

// Ambil ID barang dari URL dan tambahkan debugging
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "<!-- Debugging: ID dari URL = $id_barang -->"; // Untuk memeriksa nilai ID

// Siapkan dan jalankan query dengan kolom 'id'
$stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->execute([$id_barang]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p>Produk tidak ditemukan. (Debug: Periksa ID atau tabel barang)</p>";
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
</head>
<body>
    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Back Button -->
            <a href="menu.php" class="back-button">
                <span class="back-arrow">←</span>
            </a>

            <!-- Detail Section -->
            <section class="detail-section">
                <div class="detail-container">
                    <!-- Product Image -->
                    <div class="detail-image">
                        <img src="uploads/<?php echo htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>">
                    </div>

                    <!-- Product Info -->
                    <div class="detail-info">
                        <h2 class="detail-title"><?php echo htmlspecialchars($product['nama_barang']); ?></h2>
                        
                        <!-- Deskripsi Produk -->
                        <?php if (!empty($product['deskripsi'])): ?>
                            <div class="detail-description">
                                <?php echo nl2br(htmlspecialchars($product['deskripsi'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="detail-price">Rp <?php echo number_format($product['harga'], 2, ',', '.'); ?></p>
                        <p class="detail-stock">Stok: <?php echo htmlspecialchars($product['stok']); ?></p>
                        <p class="detail-sold">Terjual: <?php echo htmlspecialchars($product['terjual']); ?></p>
                        <div class="button-container">
                            <a href="#" class="btn-add-to-cart">Tambah ke Keranjang</a>
                            <a href="#" class="btn-buy-now">Beli Sekarang</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>