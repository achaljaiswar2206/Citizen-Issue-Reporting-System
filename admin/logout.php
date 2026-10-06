<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Logout Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Destroy Admin Session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

setFlash('info', 'You have been successfully logged out of the administrative portal.');
header("Location: " . BASE_URL . "/admin/login.php");
exit;
