<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Email SMTP & Notification Configuration
 */

// SMTP Server Configuration (Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 for TLS (STARTTLS), 465 for SSL
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'

// Gmail Credentials
define('SMTP_USER', 'achaljaiswar198@gmail.com');
// Enter your 16-character Google App Password here (e.g. 'abcd efgh ijkl mnop')
// Generate at: https://myaccount.google.com/apppasswords
define('SMTP_PASS', 'czjy wzrc xlsv ncza');

// Sender Information
define('SMTP_FROM_EMAIL', 'achaljaiswar198@gmail.com');
define('SMTP_FROM_NAME', 'Citizen Issue Reporting System (CIRS)');

// Email Settings
define('MAIL_ENABLED', true);
define('OTP_EXPIRY_MINUTES', 10);
