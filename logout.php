<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen Logout Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Clear Citizen Session Data
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_mobile']);

setFlash('info', 'You have been successfully logged out of your citizen account.');
header("Location: " . BASE_URL . "/login.php");
exit;
