<?php
session_start();
include "conn.php";
if (!$pdo) {
    die("Koneksi database gagal.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cart_count = 0;
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT SUM(ki.jumlah) as total FROM keranjang k JOIN keranjang_item ki ON k.id = ki.keranjang_id WHERE k.user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
$cart_count = $result['total'] ? $result['total'] : 0;

$notification_count = 0;
$stmt_barang = $pdo->prepare("SELECT COUNT(*) as count FROM barang ORDER BY id DESC LIMIT 5");
$stmt_barang->execute();
$barang_count = $stmt_barang->fetch(PDO::FETCH_ASSOC)['count'];

$stmt_promo = $pdo->prepare("SELECT COUNT(*) as count FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
$stmt_promo->execute();
$promo_count = $stmt_promo->fetch(PDO::FETCH_ASSOC)['count'];

$notification_count = $barang_count + $promo_count;

if (isset($_GET['action']) && $_GET['action'] === 'fetch_notifications') {
    $stmt_barang = $pdo->prepare("SELECT id, nama_barang, harga, gambar, UNIX_TIMESTAMP(created_at) as timestamp FROM barang ORDER BY id DESC LIMIT 5");
    if (!$stmt_barang->execute()) {
        error_log("Error executing notification query: " . print_r($stmt_barang->errorInfo(), true));
        $barang_baru = [];
    } else {
        $barang_baru = $stmt_barang->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt_promo = $pdo->prepare("SELECT * FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
    if (!$stmt_promo->execute()) {
        error_log("Error executing notification query: " . print_r($stmt_promo->errorInfo(), true));
        $promo_aktif = [];
    } else {
        $promo_aktif = $stmt_promo->fetchAll(PDO::FETCH_ASSOC);
    }

    function formatTanggal($date) {
        if (!$date) return 'Tanggal tidak tersedia';
        return date('d M Y', strtotime($date));
    }

    function waktu_lalu($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return 'beberapa detik yang lalu';
        if ($diff < 3600) return floor($diff/60) . ' menit yang lalu';
        if ($diff < 86400) return floor($diff/3600) . ' jam yang lalu';
        if ($diff < 2592000) return floor($diff/86400) . ' hari yang lalu';
        return date('d M Y', $time);
    }

    $response = ['barang_baru' => [], 'promo_aktif' => []];
    foreach ($barang_baru as $barang) {
        $response['barang_baru'][] = [
            'id' => $barang['id'],
            'nama_barang' => htmlspecialchars($barang['nama_barang']),
            'harga' => number_format($barang['harga'], 0, ',', '.'),
            'gambar' => $barang['gambar'] ? htmlspecialchars($barang['gambar']) : 'default.jpg',
            'timestamp' => $barang['timestamp'],
            'waktu_lalu' => waktu_lalu(date('Y-m-d H:i:s', $barang['timestamp']))
        ];
    }
    foreach ($promo_aktif as $promo) {
        $response['promo_aktif'][] = [
            'nama_promo' => htmlspecialchars($promo['nama_promo']),
            'potongan_persen' => $promo['potongan_persen'],
            'deskripsi' => htmlspecialchars($promo['deskripsi']),
            'berlaku_hingga' => formatTanggal($promo['berlaku_hingga'])
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch initial data for page load
$stmt_barang = $pdo->prepare("SELECT id, nama_barang, harga, gambar, UNIX_TIMESTAMP(created_at) as timestamp FROM barang ORDER BY id DESC LIMIT 5");
if (!$stmt_barang->execute()) {
    error_log("Error executing initial barang query: " . print_r($stmt_barang->errorInfo(), true));
    $barang_baru = [];
} else {
    $barang_baru = $stmt_barang->fetchAll();
}

$stmt_promo = $pdo->prepare("SELECT * FROM promo WHERE berlaku_hingga >= CURDATE() ORDER BY id DESC");
if (!$stmt_promo->execute()) {
    error_log("Error executing initial promo query: " . print_r($stmt_promo->errorInfo(), true));
    $promo_aktif = [];
} else {
    $promo_aktif = $stmt_promo->fetchAll();
}

function formatTanggal($date) {
    if (!$date) return 'Tanggal tidak tersedia';
    return date('d M Y', strtotime($date));
}

function waktu_lalu($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'beberapa detik yang lalu';
    if ($diff < 3600) return floor($diff/60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff/3600) . ' jam yang lalu';
    if ($diff < 2592000) return floor($diff/86400) . ' hari yang lalu';
    return date('d M Y', $time);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Florelei Flower.co</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Notification Page */
        .notification-page {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .notification-page h1 {
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        /* Notification Sections */
        .notification-section {
            margin-bottom: 30px;
        }
        .notification-section h2 {
            color: #3498db;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        /* Notification Cards */
        .notification-card {
            display: flex;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border-left: 4px solid #3498db;
        }

        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .notification-content {
            flex: 1;
            padding-left: 15px;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .notification-desc {
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #95a5a6;
            margin-bottom: 10px;
        }

        .notification-time i {
            margin-right: 5px;
            font-size: 0.9rem;
        }

        .notification-image {
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

        .notification-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
        }

        /* Buttons */
        .btn-small {
            display: inline-block;
            padding: 5px 12px;
            background: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: background 0.3s;
        }

        .btn-small:hover {
            background: #2980b9;
        }
        
        /* Empty State */
        .empty-notification {
            text-align: center;
            padding: 30px;
            color: #95a5a6;
            background: #f9f9f9;
            border-radius: 8px;
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
    </style>
</head>
<body>
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
            <a href="keranjang.php" class="icon-btn cart-icon" style="position: relative; display: inline-flex;">
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
    <main class="container">
        <div class="notification-page">
            <h1>Notifikasi Terbaru</h1>
            <!-- Barang Baru -->
            <div class="notification-section" id="barang-baru-section">
                <h2>Barang Baru</h2>
                <div class="notification-list">
                    <?php if (count($barang_baru) > 0): ?>
                        <?php foreach ($barang_baru as $barang): ?>
                            <div class="notification-card">
                                <img src="uploads/<?= htmlspecialchars($barang['gambar']) ?>" alt="<?= htmlspecialchars($barang['nama_barang']) ?>" class="notification-image">
                                <div class="notification-content">
                                    <div class="notification-title"><?= htmlspecialchars($barang['nama_barang']) ?></div>
                                    <div class="notification-desc">Rp <?= number_format($barang['harga'], 0, ',', '.') ?></div>
                                    <div class="notification-time" data-time="<?= $barang['timestamp'] ?>">
                                        <span class="time-text"><?= waktu_lalu(date('Y-m-d H:i:s', $barang['timestamp'])) ?></span>
                                    </div>
                                    <a href="detail_produk.php?id=<?= $barang['id'] ?>" class="btn-small">Lihat Produk</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-notification">
                            Tidak ada barang baru
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Promo Aktif -->
            <div class="notification-section" id="promo-aktif-section">
                <h2>Promo Spesial</h2>
                <div class="notification-list">
                    <?php if (count($promo_aktif) > 0): ?>
                        <?php foreach ($promo_aktif as $promo): ?>
                            <div class="notification-card">
                                <div class="notification-content">
                                    <div class="notification-title"><?= htmlspecialchars($promo['nama_promo']) ?></div>
                                    <div class="notification-desc">Diskon <?= $promo['potongan_persen'] ?>% - <?= htmlspecialchars($promo['deskripsi']) ?></div>
                                    <div class="notification-time">
                                        <i>⏳</i> Berakhir <?= formatTanggal($promo['berlaku_hingga']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-notification">
                            Tidak ada promo aktif saat ini
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function fetchNotifications() {
            fetch('?action=fetch_notifications')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const barangSection = document.querySelector('#barang-baru-section .notification-list');
                    barangSection.innerHTML = '';
                    if (data.barang_baru.length > 0) {
                        data.barang_baru.forEach(barang => {
                            const card = document.createElement('div');
                            card.className = 'notification-card';
                            card.innerHTML = `
                                <img src="Uploads/${barang.gambar}" alt="${barang.nama_barang}" class="notification-image">
                                <div class="notification-content">
                                    <div class="notification-title">${barang.nama_barang}</div>
                                    <div class="notification-desc">Rp ${barang.harga}</div>
                                    <div class="notification-time" data-time="${barang.timestamp}">
                                        <span class="time-text">${barang.waktu_lalu}</span>
                                    </div>
                                    <a href="detail_produk.php?id=${barang.id}" class="btn-small">Lihat Produk</a>
                                </div>
                            `;
                            barangSection.appendChild(card);
                        });
                    } else {
                        barangSection.innerHTML = '<div class="empty-notification">Tidak ada barang baru</div>';
                    }

                    const promoSection = document.querySelector('#promo-aktif-section .notification-list');
                    promoSection.innerHTML = '';
                    if (data.promo_aktif.length > 0) {
                        data.promo_aktif.forEach(promo => {
                            const card = document.createElement('div');
                            card.className = 'notification-card';
                            card.innerHTML = `
                                <div class="notification-content">
                                    <div class="notification-title">${promo.nama_promo}</div>
                                    <div class="notification-desc">Diskon ${promo.potongan_persen}% - ${promo.deskripsi}</div>
                                    <div class="notification-time">
                                        <i>⏳</i> Berakhir ${promo.berlaku_hingga}
                                    </div>
                                </div>
                            `;
                            promoSection.appendChild(card);
                        });
                    } else {
                        promoSection.innerHTML = '<div class="empty-notification">Tidak ada promo aktif saat ini</div>';
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }
        fetchNotifications();
        setInterval(fetchNotifications, 30000);

        function updateRelativeTimes() {
            const timeElements = document.querySelectorAll('.notification-time[data-time]');
            const now = Math.floor(Date.now() / 1000);
            timeElements.forEach(element => {
                const timestamp = parseInt(element.getAttribute('data-time'));
                const diff = now - timestamp;
                let text;
                if (diff < 60) text = 'beberapa detik yang lalu';
                else if (diff < 3600) text = Math.floor(diff / 60) + ' menit yang lalu';
                else if (diff < 86400) text = Math.floor(diff / 3600) + ' jam yang lalu';
                else if (diff < 2592000) text = Math.floor(diff / 86400) + ' hari yang lalu';
                else {
                    const date = new Date(timestamp * 1000);
                    text = date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                }
                element.querySelector('.time-text').textContent = text;
            });
        }
        updateRelativeTimes();
        setInterval(updateRelativeTimes, 30000);
    </script>
</body>
</html>