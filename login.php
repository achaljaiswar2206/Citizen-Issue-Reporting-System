<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen Login Page (Supports Email or Mobile Login)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/otp_config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

// If already logged in as citizen, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/citizen/dashboard.php");
    exit;
}

$page_title = "Citizen Login";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = "Session expired or invalid token. Please try again.";
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = "Please enter your registered Email / Mobile and Password.";
        } else {
            try {
                // Search by email or mobile number
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR mobile = ? LIMIT 1");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Check if email OTP verified
                    if ($user['is_verified'] == 0) {
                        $otp = generateOTP();
                        saveEmailOTP($pdo, $user['email'], $otp, $user['id'], $user['mobile'], 'REGISTRATION');
                        sendRegistrationOTPEmail($user['email'], $user['full_name'], $otp);

                        $_SESSION['temp_user_id'] = $user['id'];
                        $_SESSION['temp_email'] = $user['email'];
                        $_SESSION['temp_mobile'] = $user['mobile'];
                        $_SESSION['temp_name'] = $user['full_name'];
                        $_SESSION['demo_otp_display'] = $otp;

                        setFlash('warning', "Please verify your account with the OTP sent to <strong>" . sanitize($user['email']) . "</strong> to log in.");
                        header("Location: " . BASE_URL . "/verify_otp.php");
                        exit;
                    }

                    // Regenerate session ID for security against session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_mobile'] = $user['mobile'];

                    setFlash('success', "Welcome back, " . $user['full_name'] . "!");

                    $redirect = $_SESSION['redirect_url'] ?? (BASE_URL . '/citizen/dashboard.php');
                    unset($_SESSION['redirect_url']);
                    header("Location: " . $redirect);
                    exit;
                } else {
                    $error = "Invalid Email/Mobile Number or Password. Please try again.";
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
                        <div class="cirs-logo-badge mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Citizen Login</h3>
                        <p class="text-muted small">Sign in with your Email or Mobile Number to manage grievances</p>
                    </div>

                    <?php renderFlash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/login.php" method="POST" autocomplete="on">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Registered Email or Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="identifier" class="form-control" placeholder="name@example.com or 10-digit mobile" required autofocus value="<?= isset($_POST['identifier']) ? sanitize($_POST['identifier']) : '' ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-secondary small mb-0">Password</label>
                                <a href="<?= BASE_URL ?>/forgot_password.php" class="small text-primary fw-semibold">Forgot Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="login_password" class="form-control" placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="login_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mt-2 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Log In to Portal
                        </button>
                    </form>

                    <!-- Demo Credentials Box for Viva -->
        

                    <div class="text-center mt-4 pt-3 border-top small text-muted">
                        Don't have an account yet? 
                        <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-primary">Register Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
