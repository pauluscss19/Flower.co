<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$sql = "SELECT u.id, u.username, p.no_wa FROM user u
        LEFT JOIN profil p ON u.profil_id = p.id
        WHERE u.username = :username";
$stmt = $pdo->prepare($sql);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if (!$user) {
    die("User tidak ditemukan.");
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username']);
    $newTelepon = trim($_POST['telepon']);

    if ($newUsername === '' || $newTelepon === '') {
        $error = "Harap isi semua field!";
    } else 
        $pdo->beginTransaction();
        $updateUser = "UPDATE user SET username = :username WHERE id = :id";
        $stmt = $pdo->prepare($updateUser);
            $stmt->execute([
                'username' => $newUsername,
                'id' => $user['id']
            ]);
            if ($user['no_wa'] !== null) {
                $updateProfil = "UPDATE profil SET no_wa = :no_wa WHERE id = (SELECT profil_id FROM user WHERE id = :id)";
                $stmt = $pdo->prepare($updateProfil);
                $stmt->execute([
                    'no_wa' => $newTelepon,
                    'id' => $user['id']
                ]);
            } 
            $pdo->commit();
            $_SESSION['username'] = $newUsername;
            $success = true;  
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['username' => $newUsername]);
            $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Profil</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="editprofil-container">
        <div class="editprofil-card">
            <div class="editprofil-header">
                <h1 class="editprofil-title">Edit profil</h1>
                <p class="editprofil-subtitle">edit profil anda</p>
            </div>

            <div class="editprofil-avatar">
                <div class="editprofil-avatar-icon"></div>
            </div>
            <?php if ($success): ?>
                <p style="color: green; text-align: center; margin-top: 10px;">
                    Data berhasil diubah!
                </p>
            <?php endif; ?>
            <form class="editprofil-form" method="post">
                <div class="editprofil-field">
                    <label class="editprofil-label" for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username"
                        class="editprofil-input" 
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($user['username']) ?>"
                    >
                </div>

                <div class="editprofil-field">
                    <label class="editprofil-label" for="telepon">Telepon</label>
                    <input 
                        type="tel" 
                        id="telepon" 
                        name="telepon"
                        class="editprofil-input" 
                        placeholder="Masukkan nomor telepon"
                        value="<?= htmlspecialchars($user['no_wa'] ?? '') ?>"
                    >
                </div>

                <button type="submit" class="editprofil-submit-btn">
                    Ubah
                </button>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('.editprofil-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const telepon = document.getElementById('telepon').value.trim();
            if (username === '' || telepon === '') {
                e.preventDefault();
                alert('Harap isi semua field!');
            }
        });

        const inputs = document.querySelectorAll('.editprofil-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('editprofil-field-focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('editprofil-field-focused');
            });
        });
    </script>
</body>
</html>
