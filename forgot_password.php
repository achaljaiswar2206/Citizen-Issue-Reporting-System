<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Forgot Password - Email OTP Request
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

$page_title = "Forgot Password";
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid registered email address.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, full_name, email, mobile FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $otp = generateOTP();
                    saveEmailOTP($pdo, $email, $otp, $user['id'], $user['mobile'], 'PASSWORD_RESET');

                    // Send Password Reset OTP Email
                    sendPasswordResetOTPEmail($user['email'], $user['full_name'], $otp);

                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_name'] = $user['full_name'];
                    $_SESSION['demo_reset_otp'] = $otp;

                    setFlash('info', "A 6-digit password reset OTP has been sent to <strong>" . sanitize($email) . "</strong>. Please enter the OTP and your new password.");
                    header("Location: " . BASE_URL . "/reset_password.php");
                    exit;
                } else {
                    $error = "No registered account found with that email address.";
                }
            } catch (Exception $e) {
                $error = "A system error occurred. Please try again later.";
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
                        <div class="cirs-logo-badge mx-auto mb-3" style="width: 55px; height: 55px; font-size: 1.6rem; background: linear-gradient(135deg, #ea580c, #c2410c);">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Reset Password</h3>
                        <p class="text-muted small">Enter your registered email to receive a password reset verification code</p>
                    </div>

                    <?php renderFlash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/forgot_password.php" method="POST">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Registered Email Address</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" required autofocus value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                            </div>
                            <div class="form-text small">We will send a 6-digit OTP to this email address.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="bi bi-send-check me-1"></i> Send Password Reset OTP
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top small text-muted">
                        Remembered your password? 
                        <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
