<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

if (!isset($_GET['order_id'])) {
    die("Order ID not provided");
}

$order_id = $_GET['order_id'];

// Get order information
$order = $pdo->prepare("SELECT p.*, u.username, u.email 
                       FROM pesanan p 
                       JOIN user u ON p.user_id = u.id 
                       WHERE p.id = ?");
$order->execute([$order_id]);
$order = $order->fetch();

if (!$order) {
    die("Order not found");
}

// Get order items
$items = $pdo->prepare("SELECT dp.*, b.nama_barang, b.gambar 
                       FROM detail_pesanan dp 
                       JOIN barang b ON dp.barang_id = b.id 
                       WHERE dp.pesanan_id = ?");
$items->execute([$order_id]);

// Get payment proof if exists
$payment_proof = $pdo->prepare("SELECT * FROM bukti_pembayaran WHERE pesanan_id = ?");
$payment_proof->execute([$order_id]);
$payment_proof = $payment_proof->fetch();
?>

<div class="order-info">
    <h3>Order Information</h3>
    <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</p>
    <p><strong>Order Date:</strong> <?php echo date('d M Y H:i', strtotime($order['tanggal_pemesanan'])); ?></p>
    <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['alamat_pengiriman']); ?></p>
    <p><strong>Payment Method:</strong> <?php echo $order['metode_pembayaran'] ?? '-'; ?></p>
    <p><strong>Status:</strong> <span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></p>
</div>

<?php if ($payment_proof): ?>
<div class="payment-proof">
    <h3>Payment Proof</h3>
    <p><strong>Uploaded at:</strong> <?php echo date('d M Y H:i', strtotime($payment_proof['waktu_upload'])); ?></p>
    <img src="<?php echo htmlspecialchars($payment_proof['path_file']); ?>" alt="Payment Proof" style="max-width: 100%; max-height: 300px;">
</div>
<?php endif; ?>

<div class="order-details">
    <h3>Order Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach ($items as $item): 
                $total = $item['harga_satuan'] * $item['jumlah'];
                $subtotal += $total;
            ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($item['nama_barang']); ?>
                    <?php if ($item['gambar']): ?>
                        <img src="img/<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>" style="max-width: 50px; max-height: 50px;">
                    <?php endif; ?>
                </td>
                <td>Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                <td><?php echo $item['jumlah']; ?></td>
                <td>Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                <td><strong>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>