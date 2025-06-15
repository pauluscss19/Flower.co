<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Initialize variables
$id = $nama_barang = $harga = $stok = $deskripsi = $gambar = $current_image = '';
$error = '';

// Get product ID from URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Fetch product data
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $nama_barang = $product['nama_barang'];
        $harga = $product['harga'];
        $stok = $product['stok'];
        $deskripsi = $product['deskripsi'];
        $current_image = $product['gambar'];
    } else {
        header("Location: admin_products.php");
        exit;
    }
} else {
    header("Location: admin_products.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $nama_barang = trim($_POST['nama_barang']);
    $harga = trim($_POST['harga']);
    $stok = trim($_POST['stok']);
    $deskripsi = trim($_POST['deskripsi']);
    $gambar = $current_image; // Default to current image
    
    // Validate required fields
    if (empty($nama_barang) || empty($harga) || empty($stok)) {
        $error = 'Harap isi semua field yang wajib diisi.';
    } else {
        // Handle file upload if new image is provided
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["gambar"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is a actual image
            $check = getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check !== false) {
                // Generate unique filename
                $gambar = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $gambar;
                
                // Check file size (max 2MB)
                if ($_FILES["gambar"]["size"] > 2000000) {
                    $error = 'Ukuran file terlalu besar (maksimal 2MB).';
                } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $error = 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.';
                } elseif (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                    // Delete old image if it's not the default
                    if ($current_image != 'default.jpg' && file_exists($target_dir . $current_image)) {
                        unlink($target_dir . $current_image);
                    }
                } else {
                    $error = 'Terjadi error saat mengupload file.';
                }
            } else {
                $error = 'File bukan gambar.';
            }
        }
        
        // If no errors, update database
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("UPDATE barang SET nama_barang = ?, harga = ?, stok = ?, deskripsi = ?, gambar = ? WHERE id = ?");
                $stmt->execute([$nama_barang, $harga, $stok, $deskripsi, $gambar, $id]);
                
                // Redirect to products page with success message
                $_SESSION['success_message'] = 'Produk berhasil diperbarui!';
                header("Location: admin_products.php");
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete request
if (isset($_POST['delete_product'])) {
    try {
        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete image file if it's not the default
        if ($product['gambar'] != 'default.jpg' && file_exists("uploads/" . $product['gambar'])) {
            unlink("uploads/" . $product['gambar']);
        }
        
        $_SESSION['success_message'] = 'Produk berhasil dihapus!';
        header("Location: admin_products.php");
        exit;
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Florelei Admin</title>
    <link rel="stylesheet" href="admin.css">
    <script>
        function previewImage(event) {
            const preview = document.getElementById('imagePreview');
            const file = event.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function() {
                preview.src = reader.result;
                preview.classList.add('visible');
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        
        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus produk ini?');
        }
    </script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <img src="img/logo.png" alt="Florelei" class="admin-logo-img">
                <h2>Florelei Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="admin_dashboard.php" class="admin-nav-link">Dashboard</a>
                <a href="admin_users.php" class="admin-nav-link">Users</a>
                <a href="admin_products.php" class="admin-nav-link">Products</a>
                <a href="admin_tambah_produk.php" class="admin-nav-link">Tambah Produk</a>
                <a href="admin_orders.php" class="admin-nav-link">Orders</a>
                <a href="admin_reviews.php" class="admin-nav-link">Reviews</a>
                <a href="admin_promotions.php" class="admin-nav-link">Promotions</a>
                <a href="admin_messages.php" class="admin-nav-link">Messages</a>
                <a href="admin_logout.php" class="admin-nav-link logout">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Produk</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <div class="admin-content">
                <div class="admin-tambah-produk">
                    <?php if (!empty($error)): ?>
                        <div class="admin-notif error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="admin-form-produk" method="POST" enctype="multipart/form-data">
                        <div class="admin-form-row">
                            <div class="admin-form-col">
                                <div class="admin-form-group">
                                    <label for="nama_barang" class="admin-form-label">Nama Produk</label>
                                    <input type="text" id="nama_barang" name="nama_barang" class="admin-form-input" 
                                           value="<?php echo htmlspecialchars($nama_barang); ?>" required>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label for="harga" class="admin-form-label">Harga (Rp)</label>
                                    <input type="number" id="harga" name="harga" class="admin-form-input" 
                                           value="<?php echo htmlspecialchars($harga); ?>" min="0" step="1000" required>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label for="stok" class="admin-form-label">Stok</label>
                                    <input type="number" id="stok" name="stok" class="admin-form-input" 
                                           value="<?php echo htmlspecialchars($stok); ?>" min="0" required>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label for="deskripsi" class="admin-form-label">Deskripsi Produk</label>
                                    <textarea id="deskripsi" name="deskripsi" class="admin-form-textarea" 
                                              rows="5"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="admin-form-col">
                                <div class="admin-form-group">
                                    <label for="gambar" class="admin-form-label">Gambar Produk</label>
                                    <input type="file" id="gambar" name="gambar" class="admin-form-file" 
                                           accept="image/*" onchange="previewImage(event)">
                                    <img id="imagePreview" class="admin-preview-image" 
                                         src="uploads/<?php echo htmlspecialchars($current_image); ?>" 
                                         alt="Current Image">
                                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($current_image); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="admin-form-actions">
                            <button type="submit" formaction="admin_edit_produk.php?id=<?php echo $id; ?>" 
                                    class="admin-btn admin-btn-primary">Simpan Perubahan</button>
                            <button type="submit" name="delete_product" class="admin-btn admin-btn-danger" 
                                    onclick="return confirmDelete()">Hapus Produk</button>
                            <a href="admin_products.php" class="admin-btn admin-btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>