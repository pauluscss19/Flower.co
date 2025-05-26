<?php
include "conn.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florelei Flower.co - Homepage</title>
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
                    <div class="menu-item">
                        <div class="menu-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.1 5.4M7 13l2.1 5.4M17 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0zM9 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </div>
                        <span class="menu-text">Lihat Produk</span>
                    </div>
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
                    <div class="menu-item">
                        <div class="menu-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <span class="menu-text">Notification</span>
                    </div>
                </div>
            </section>

            <!-- Products Section -->
            <section class="products-section">
                <h2 class="section-title">Rekomendasi Produk</h2>
                <div class="products-container">
                    <div class="product-item">
                        <div class="product-image">
                            <img src="img/bunga1.jpg" alt="Bunga Paket A" class="product-img">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">Bunga Paket A</h3>
                            <p class="product-price">Rp 75.000</p>
                        </div>
                    </div>
                    <div class="product-item">
                        <div class="product-image">
                            <img src="img/bunga2.jpg" alt="Bunga Paket B" class="product-img">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">Bunga Paket B</h3>
                            <p class="product-price">Rp 95.000</p>
                        </div>
                    </div>
                    <div class="product-item">
                        <div class="product-image">
                            <img src="img/bunga3.jpg" alt="Bunga Paket C" class="product-img">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">Bunga Paket C</h3>
                            <p class="product-price">Rp 85.000</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>