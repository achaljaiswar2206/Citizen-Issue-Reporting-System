<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Reset Password - OTP Verification & New Password Setup
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/otp_config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/citizen/dashboard.php");
    exit;
}

$reset_email = $_SESSION['reset_email'] ?? '';
$reset_name = $_SESSION['reset_name'] ?? 'Citizen';
$demo_otp = $_SESSION['demo_reset_otp'] ?? '123456';

if (empty($reset_email)) {
    header("Location: " . BASE_URL . "/forgot_password.php");
    exit;
}

$page_title = "Set New Password";
$error = '';
$success = '';

// Resend Action
if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    $new_otp = generateOTP();
    saveEmailOTP($pdo, $reset_email, $new_otp, null, null, 'PASSWORD_RESET');
    
    // Resend Email
    sendPasswordResetOTPEmail($reset_email, $reset_name, $new_otp);
    
    $_SESSION['demo_reset_otp'] = $new_otp;
    $demo_otp = $new_otp;
    $success = "A fresh 6-digit password reset OTP has been sent to " . sanitize($reset_email) . ".";
}

// Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $otp_code = trim($_POST['otp_code'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($otp_code) || empty($new_password) || empty($confirm_password)) {
            $error = "All fields are mandatory.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and Confirm Password do not match.";
        } else {
            // Verify OTP
            $res = verifyEmailOTP($pdo, $reset_email, $otp_code, 'PASSWORD_RESET');

            if ($res['status']) {
                try {
                    // Update password in users table
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed_password, $reset_email]);

                    // Send Password Changed Notification Email
                    sendPasswordChangedConfirmationEmail($reset_email, $reset_name);

                    // Clear reset session
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_name']);
                    unset($_SESSION['demo_reset_otp']);

                    setFlash('success', "Your password has been reset successfully! You can now log in using your new password.");
                    header("Location: " . BASE_URL . "/login.php");
                    exit;

                } catch (Exception $e) {
                    $error = "A database error occurred while updating your password: " . $e->getMessage();
                }
            } else {
                $error = $res['message'];
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-5" style="background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="cirs-card shadow-lg p-4 p-sm-5 bg-white">
                    <div class="text-center mb-4">
                        <div class="cirs-logo-badge mx-auto mb-3" style="width: 55px; height: 55px; font-size: 1.6rem; background: linear-gradient(135deg, #0284c7, #0369a1);">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Set New Password</h3>
                        <p class="text-muted small">
                            Enter the OTP code sent to:
                            <br><strong class="text-dark fs-6"><?= sanitize($reset_email) ?></strong>
                        </p>
                    </div>

                    <?php renderFlash(); ?>

                    <?php if (empty(SMTP_PASS) || SMTP_PASS === 'your_app_password_here' || OTP_MODE === 'demo'): ?>
                        <!-- Demo OTP Box for local test / evaluation -->
                        <div class="alert alert-info border-info-subtle shadow-sm mb-4">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-broadcast me-2 fs-5"></i>
                                <strong>LOCAL TESTING / DEMO OTP</strong>
                            </div>
                            <small class="d-block text-secondary mb-2">Use this generated OTP code to reset password:</small>
                            <div class="p-2 bg-white rounded border text-center font-monospace fs-4 fw-bold text-primary tracking-widest">
                                <?= sanitize($demo_otp) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-3">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?= sanitize($success) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/reset_password.php" method="POST">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Enter 6-Digit Email OTP</label>
                            <input type="text" name="otp_code" class="form-control form-control-lg text-center font-monospace fw-bold fs-4" maxlength="6" placeholder="------" autofocus required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">New Password (Min 6 chars)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="new_password" id="reset_new_pass" class="form-control" placeholder="••••••••" required minlength="6">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="reset_new_pass">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" id="reset_conf_pass" class="form-control" placeholder="••••••••" required minlength="6">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="reset_conf_pass">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Update Password & Proceed
                        </button>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top small">
                        <span class="text-muted">Didn't get code?</span>
                        <a href="<?= BASE_URL ?>/reset_password.php?action=resend" class="fw-bold text-primary">Resend Reset OTP</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
