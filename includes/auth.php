<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen Authentication Guard
 */

require_once __DIR__ . '/../config/config.php';

// Check if citizen session exists
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Store requested page for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/citizen/dashboard.php');
    
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'Please log in with your citizen account to continue.'
    ];
    
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Function to get current citizen data
function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, full_name, email, mobile, address, is_verified, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
