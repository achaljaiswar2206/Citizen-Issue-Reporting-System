<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Backend Profile & Password Updater
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/citizen/profile.php");
    exit;
}

if (!validate_csrf()) {
    setFlash('danger', 'Security validation token expired. Please try again.');
    header("Location: " . BASE_URL . "/citizen/profile.php");
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update_details') {
    $full_name = trim($_POST['full_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name)) {
        setFlash('danger', 'Full name cannot be empty.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $address, $user['id']]);

            $_SESSION['user_name'] = $full_name;
            setFlash('success', 'Profile details updated successfully.');
        } catch (Exception $e) {
            setFlash('danger', 'Database error updating profile: ' . $e->getMessage());
        }
    }
} elseif ($action === 'change_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        setFlash('danger', 'Please fill in all password fields.');
    } elseif ($new_pass !== $confirm_pass) {
        setFlash('danger', 'New password and confirmation do not match.');
    } elseif (strlen($new_pass) < 6) {
        setFlash('danger', 'New password must be at least 6 characters long.');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $db_pass = $stmt->fetchColumn();

            if (password_verify($current_pass, $db_pass)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$new_hash, $user['id']]);

                setFlash('success', 'Password updated successfully!');
            } else {
                setFlash('danger', 'The current password you entered is incorrect.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error updating password.');
        }
    }
}

header("Location: " . BASE_URL . "/citizen/profile.php");
exit;
