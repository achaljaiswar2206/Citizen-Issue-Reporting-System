<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Email & Mobile OTP Verification
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/otp_config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

$page_title = "Account Email OTP Verification";
$error = '';
$success = '';

// Check if there is a pending user or email to verify
$temp_user_id = $_SESSION['temp_user_id'] ?? null;
$temp_email = $_SESSION['temp_email'] ?? null;
$temp_mobile = $_SESSION['temp_mobile'] ?? null;
$temp_name = $_SESSION['temp_name'] ?? 'Citizen';
$demo_otp = $_SESSION['demo_otp_display'] ?? '123456';

if (!$temp_email && !$temp_mobile && !$temp_user_id) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Resend OTP Action
if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    $new_otp = generateOTP();
    saveEmailOTP($pdo, $temp_email, $new_otp, $temp_user_id, $temp_mobile, 'REGISTRATION');
    
    // Resend Email
    if ($temp_email) {
        sendRegistrationOTPEmail($temp_email, $temp_name, $new_otp);
    }
    
    $_SESSION['demo_otp_display'] = $new_otp;
    $demo_otp = $new_otp;
    $success = "A fresh 6-digit verification code has been dispatched to " . ($temp_email ? sanitize($temp_email) : sanitize($temp_mobile)) . ".";
}

// Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $otp_code = trim($_POST['otp_code'] ?? '');

        if (empty($otp_code)) {
            $error = "Please enter the 6-digit OTP code.";
        } else {
            $verify_target = $temp_email ?: $temp_mobile;
            $res = verifyEmailOTP($pdo, $verify_target, $otp_code, 'REGISTRATION');

            if ($res['status']) {
                // Fetch full user record
                $user = null;
                if ($temp_user_id) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                    $stmt->execute([$temp_user_id]);
                    $user = $stmt->fetch();
                } elseif ($temp_email) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                    $stmt->execute([$temp_email]);
                    $user = $stmt->fetch();
                }

                if ($user) {
                    // Send Registration Confirmation & Credentials Email
                    sendRegistrationSuccessEmail($user['email'], $user['full_name'], $user['mobile']);

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_mobile'] = $user['mobile'];

                    // Clear temp registration session
                    unset($_SESSION['temp_user_id']);
                    unset($_SESSION['temp_email']);
                    unset($_SESSION['temp_mobile']);
                    unset($_SESSION['temp_name']);
                    unset($_SESSION['demo_otp_display']);

                    setFlash('success', "Account verified successfully! Welcome to CIRS. Registration details have also been sent to your email.");
                    header("Location: " . BASE_URL . "/citizen/dashboard.php");
                    exit;
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
                        <div class="cirs-logo-badge mx-auto mb-3" style="width: 55px; height: 55px; font-size: 1.6rem; background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Verify Your Email</h3>
                        <p class="text-muted small">
                            We sent a 6-digit verification code to:
                            <br><strong class="text-dark fs-6"><?= sanitize($temp_email ?: $temp_mobile) ?></strong>
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
                            <small class="d-block text-secondary mb-2">Use this generated OTP code to complete registration:</small>
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

                    <form action="<?= BASE_URL ?>/verify_otp.php" method="POST">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Enter 6-Digit Email OTP</label>
                            <input type="text" name="otp_code" class="form-control form-control-lg text-center font-monospace fw-bold fs-4" maxlength="6" placeholder="------" autofocus required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Verify & Complete Registration
                        </button>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top small">
                        <span class="text-muted">Didn't receive email?</span>
                        <a href="<?= BASE_URL ?>/verify_otp.php?action=resend" class="fw-bold text-primary">Resend OTP Code</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
