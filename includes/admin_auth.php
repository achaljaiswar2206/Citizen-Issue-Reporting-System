<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Authentication Guard
 */

require_once __DIR__ . '/../config/config.php';

// Check if admin session exists
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/admin/dashboard.php');
    
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Unauthorized area. Please log in with municipal administrator credentials.'
    ];
    
    header("Location: " . BASE_URL . "/admin/login.php");
    exit;
}

// Function to get current admin data
function getCurrentAdmin($pdo) {
    if (!isset($_SESSION['admin_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}
