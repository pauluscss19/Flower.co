<?php
include "conn.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florelei - Flower.co</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="img/logo.png" alt="Florelei" class="logo-img">
            </div>
            <nav class="nav">
                <a href="index.php" class="btn btn-login">Logout</a>
                <a class="btn btn-login">Welcome, User</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="hero">
                <div class="hero-logo">
                    <img src="img/logo.png" alt="Florelei" class="hero-logo-img">
                </div>
                
                <h1 class="hero-title">
                    Tempat Jual Beli Bucket Bunga . Temurah dan Terbaik.<br>
                    Florelei 🌸 Flower.co — Berbelanja dengan Gaya.
                </h1>
                
                <p class="hero-description">
                    Florelei Flower.co bukan sekadar toko bunga — ini adalah sebuah pergerakan. Temukan rangkaian bunga pilihan 
                    dengan makna yang mendalam, sekaligus dapatkan inspirasi merangkai bunga yang elegan dan bermakna untuk setiap momen spesialmu.
                </p>
                
                <a href="menu.php" class="btn btn-shop">
                    🛒 Belanja Sekarang
                </a>
            </div>
        </div>
    </main>
</body>
</html>