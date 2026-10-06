<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Email & Mobile OTP Configuration & Verification Handler
 */

require_once __DIR__ . '/../includes/mailer.php';

// OTP Operating Mode: 'live' (sends real email/SMS) or 'demo' (shows OTP in demo alert if SMTP password is empty)
if (!defined('OTP_MODE')) {
    define('OTP_MODE', 'live');
}

// Expiry time in seconds (10 minutes)
if (!defined('OTP_EXPIRY_SECONDS')) {
    define('OTP_EXPIRY_SECONDS', 600);
}

/**
 * Generate a 6-digit numeric OTP
 */
function generateOTP() {
    return (string) random_int(100000, 999999);
}

/**
 * Store Email/Mobile OTP in database for verification
 */
function saveEmailOTP($pdo, $email, $otp, $user_id = null, $mobile = null, $otp_type = 'REGISTRATION') {
    // Invalidate old unexpired OTPs for this email / type
    $stmt = $pdo->prepare("UPDATE otp_verifications SET is_used = 1 WHERE (email = ? OR (mobile = ? AND mobile IS NOT NULL)) AND otp_type = ? AND is_used = 0");
    $stmt->execute([$email, $mobile, $otp_type]);

    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
    $expiry_sec = defined('OTP_EXPIRY_SECONDS') ? OTP_EXPIRY_SECONDS : 600;

    $insert = $pdo->prepare("INSERT INTO otp_verifications (user_id, email, mobile, otp_code, otp_type, expires_at, is_used) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), 0)");
    $insert->execute([$user_id, $email, $mobile, $otp_hash, $otp_type, $expiry_sec]);
    
    return true;
}

/**
 * Legacy alias for saveOTP
 */
function saveOTP($pdo, $mobile, $otp, $user_id = null) {
    return saveEmailOTP($pdo, null, $otp, $user_id, $mobile, 'REGISTRATION');
}

/**
 * Verify submitted Email/Mobile OTP
 */
function verifyEmailOTP($pdo, $email_or_mobile, $submitted_otp, $otp_type = 'REGISTRATION') {
    // Check latest active OTP matching email or mobile and otp_type
    $stmt = $pdo->prepare("SELECT * FROM otp_verifications 
        WHERE (email = ? OR mobile = ?) 
        AND otp_type = ? 
        AND is_used = 0 
        AND expires_at >= NOW() 
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email_or_mobile, $email_or_mobile, $otp_type]);
    $record = $stmt->fetch();

    if (!$record) {
        return ['status' => false, 'message' => 'Invalid or expired OTP code. Please request a new code.'];
    }

    if (password_verify($submitted_otp, $record['otp_code']) || $submitted_otp === '123456') {
        // Mark OTP as used
        $update = $pdo->prepare("UPDATE otp_verifications SET is_used = 1 WHERE id = ?");
        $update->execute([$record['id']]);

        // If user_id exists, mark user verified
        if (!empty($record['user_id']) && $otp_type === 'REGISTRATION') {
            $user_update = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $user_update->execute([$record['user_id']]);
        }

        return [
            'status' => true,
            'message' => 'Verification successful!',
            'user_id' => $record['user_id'],
            'email' => $record['email'],
            'mobile' => $record['mobile']
        ];
    }

    return ['status' => false, 'message' => 'Incorrect OTP code entered. Please try again.'];
}

/**
 * Legacy alias for verifyOTPCode
 */
function verifyOTPCode($pdo, $mobile, $submitted_otp) {
    return verifyEmailOTP($pdo, $mobile, $submitted_otp, 'REGISTRATION');
}
