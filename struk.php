<?php
session_start();
include "conn.php";

// Ensure pesanan_id is provided
$pesanan_id = $_GET['pesanan_id'] ?? null;
if (!$pesanan_id) {
    header("Location: lihat_produk.php");
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email, pr.no_wa
    FROM pesanan p
    JOIN user u ON p.user_id = u.id
    LEFT JOIN profil pr ON u.profil_id = pr.id
    WHERE p.id = ?
");
$stmt->execute([$pesanan_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: lihat_produk.php");
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT dp.jumlah, dp.harga_satuan, b.nama_barang
    FROM detail_pesanan dp
    JOIN barang b ON dp.barang_id = b.id
    WHERE dp.pesanan_id = ?
");
$stmt->execute([$pesanan_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['jumlah'] * $item['harga_satuan'];
}
$total_pembayaran = $subtotal;

// Format rupiah
function formatRupiah($angka) {
    return 'Rp' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berhasil - Florelei</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .header {
            background-color: #e57373;
            padding: 15px 0;
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo h1 {
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .flower-icon {
            width: 30px;
            height: 30px;
            background: #ffeb3b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .nav-icons {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 15px;
        }

        .nav-icons span {
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .success-header {
            background: #f0f8e8;
            padding: 30px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #4caf50;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .success-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .success-subtitle {
            color: #666;
            font-size: 14px;
        }

        .receipt-content {
            padding: 20px 30px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            color: #666;
            font-size: 14px;
        }

        .receipt-value {
            color: #333;
            font-weight: 500;
            text-align: right;
        }

        .total-row {
            background: #f8f9fa;
            margin: 0 -30px;
            padding: 15px 30px;
            font-weight: bold;
            color: #333;
        }

        .payment-method {
            color: #4caf50;
            font-weight: 500;
        }

        .date-time {
            font-size: 12px;
            color: #888;
        }

        .back-button {
            background: #4caf50;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin: 20px 0;
            transition: background 0.3s;
        }

        .back-button:hover {
            background: #45a049;
        }

        .footer-text {
            text-align: center;
            color: #888;
            font-size: 12px;
            padding: 20px;
        }

        .user-info {
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        .user-info .receipt-row {
            padding: 8px 0;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                margin: 0;
            }
            
            .nav-icons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-header">
            <div class="success-icon">✓</div>
            <div class="success-title">Transaksi Berhasil</div>
            <div class="success-subtitle">Terima kasih, pesananmu sudah berhasil diproses.</div>
        </div>

        <div class="user-info">
            <div class="receipt-row">
                <span class="receipt-label">Username</span>
                <span class="receipt-value"><?php echo htmlspecialchars($order['username']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Email</span>
                <span class="receipt-value"><?php echo htmlspecialchars($order['email']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">No. WhatsApp</span>
                <span class="receipt-value"><?php echo htmlspecialchars($order['no_wa'] ?? 'Tidak tersedia'); ?></span>
            </div>
        </div>

        <div class="receipt-content">
            <?php foreach ($order_items as $item): ?>
                <div class="receipt-row">
                    <span class="receipt-label">Produk</span>
                    <span class="receipt-value"><?php echo htmlspecialchars($item['nama_barang']); ?> (x<?php echo $item['jumlah']; ?>)</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Harga Satuan</span>
                    <span class="receipt-value"><?php echo formatRupiah($item['harga_satuan']); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="receipt-row">
                <span class="receipt-label">Subtotal</span>
                <span class="receipt-value"><?php echo formatRupiah($subtotal); ?></span>
            </div>
            <div class="receipt-row total-row">
                <span class="receipt-label">Total Pembayaran</span>
                <span class="receipt-value"><?php echo formatRupiah($total_pembayaran); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Metode Pembayaran</span>
                <span class="receipt-value payment-method"><?php echo htmlspecialchars($order['metode_pembayaran']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Alamat Pengiriman</span>
                <span class="receipt-value"><?php echo htmlspecialchars($order['alamat_pengiriman']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Tanggal Pesanan</span>
                <span class="receipt-value date-time"><?php echo $order['tanggal_pemesanan']; ?></span>
            </div>
            <button class="back-button" onclick="kembaliKeStorenya()">
                Continue
            </button>
        </div>

        <div class="footer-text">
            Pesanan Anda akan segera diproses dan dikirim sesuai jadwal yang telah ditentukan.
        </div>
    </div>

    <script>
        function kembaliKeStorenya() {
            window.location.href = 'pemesanan.php';
        }


        setTimeout(function() {
        }, 30000);
    </script>
</body>
</html>