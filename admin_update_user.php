<?php
session_start();
include "conn.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // Validate required fields
    if (empty($username) || empty($email)) {
        $_SESSION['error_message'] = 'Username and email are required fields.';
        header("Location: admin_users.php");
        exit;
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Update user table
        if (!empty($password)) {
            // If password is provided, hash it and update
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE user SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed_password, $user_id]);
        } else {
            // Update without password
            $stmt = $pdo->prepare("UPDATE user SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_id]);
        }

        // Update profile table (phone number)
        // First check if profile exists
        $stmt = $pdo->prepare("SELECT profil_id FROM user WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $user['profil_id']) {
            // Update existing profile
            $stmt = $pdo->prepare("UPDATE profil SET no_wa = ? WHERE id = ?");
            $stmt->execute([$phone, $user['profil_id']]);
        } else {
            // Create new profile if doesn't exist
            $stmt = $pdo->prepare("INSERT INTO profil (no_wa) VALUES (?)");
            $stmt->execute([$phone]);
            $profile_id = $pdo->lastInsertId();
            
            // Update user with new profile_id
            $stmt = $pdo->prepare("UPDATE user SET profil_id = ? WHERE id = ?");
            $stmt->execute([$profile_id, $user_id]);
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success_message'] = 'User updated successfully!';
        header("Location: admin_users.php");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error updating user: ' . $e->getMessage();
        header("Location: admin_users.php");
        exit;
    }
} else {
    // If not POST request, redirect back
    header("Location: admin_users.php");
    exit;
}