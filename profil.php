<?php
session_start();
include 'conn.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['username'] ?? '';
$query = "SELECT u.username, u.email, p.no_wa 
          FROM user u
          LEFT JOIN profil p ON u.profil_id = p.id
          WHERE u.username = :username";
$stmt =$pdo->prepare($query);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil User - Florelei</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="profil-container">
    <div class="profil-header">
      <button class="profil-back-button" onclick="window.location.href='menu.php'">
        <span class="profil-back-arrow">‹</span>
      </button>
    </div>

    <div class="profil-card-wrapper">
      <div class="profil-brand">
        Fl<span class="profil-brand-flower">✿</span>relei
      </div>

      <div class="profil-card">
        <div class="profil-avatar">
          <div class="profil-avatar-icon"></div>
        </div>

        <h2 class="profil-name"><?= htmlspecialchars($user['username']) ?></h2>
        

        <div class="profil-info-item">
          <svg class="profil-info-icon" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
          </svg>
          <div class="profil-info-content">
            <div class="profil-info-label">Email</div>
            <div class="profil-info-value"><?= htmlspecialchars($user['email']) ?></div>
          </div>
        </div>

        <div class="profil-info-item">
          <svg class="profil-info-icon" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
          </svg>
          <div class="profil-info-content">
            <div class="profil-info-label">Telepon</div>
            <div class="profil-info-value"><?= htmlspecialchars($user['no_wa'] ?? '-') ?></div>
          </div>
        </div>

        <button class="profil-edit-button" onclick="window.location.href='edit_profil.php'">
          Edit profil
        </button>
      </div>

      <div class="profil-decoration"></div>
    </div>
  </div>

  <script>
    document.querySelector(".profil-back-button").addEventListener("click", function () {
      alert("Kembali ke halaman sebelumnya");
    });

    document.querySelector(".profil-edit-button").addEventListener("click", function () {
      alert("Membuka halaman edit profil");
    });

    const infoItems = document.querySelectorAll(".profil-info-item");
    infoItems.forEach((item) => {
      item.addEventListener("mouseenter", function () {
        this.style.background = "#fff3a0";
      });
      item.addEventListener("mouseleave", function () {
        this.style.background = "#fff9c4";
      });
    });
  </script>
</body>
</html>
