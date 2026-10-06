<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Email SMTP & Notification Configuration
 * 
 * Sensitive credentials are loaded securely from your root .env file.
 * DO NOT hardcode passwords in this file.
 */

require_once __DIR__ . '/config.php';

// SMTP Server Configuration (Gmail SMTP)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587)); // 587 for TLS, 465 for SSL
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // 'tls' or 'ssl'

// Gmail Credentials (Loaded from .env)
define('SMTP_USER', getenv('SMTP_USER') ?: 'your_email@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your_app_password_here');

// Sender Information
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USER);
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Citizen Issue Reporting System (CIRS)');

// Email Settings
define('MAIL_ENABLED', true);
define('OTP_EXPIRY_MINUTES', 10);
