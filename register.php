<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen Registration Page
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/otp_config.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/citizen/dashboard.php");
    exit;
}

$page_title = "Citizen Registration";
$error = '';
$form_data = [
    'full_name' => '',
    'email' => '',
    'mobile' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = "Session expired or invalid token. Please try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $form_data = [
            'full_name' => $full_name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address
        ];

        // Validations
        if (empty($full_name) || empty($email) || empty($mobile) || empty($password)) {
            $error = "All mandatory fields marked with * are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $error = "Please enter a valid 10-digit Indian mobile number.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Password and Confirm Password do not match.";
        } else {
            try {
                // Check if email already registered
                $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check_email->execute([$email]);
                if ($check_email->fetch()) {
                    $error = "This email address is already registered. Please log in.";
                } else {
                    // Check if mobile already registered
                    $check_mobile = $pdo->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
                    $check_mobile->execute([$mobile]);
                    if ($check_mobile->fetch()) {
                        $error = "This mobile number is already registered. Please log in.";
                    } else {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $is_verified = 0;

                        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, mobile, password, address, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$full_name, $email, $mobile, $hashed_password, $address, $is_verified]);
                        $user_id = $pdo->lastInsertId();

                        // Generate and send Email OTP
                        $otp = generateOTP();
                        saveEmailOTP($pdo, $email, $otp, $user_id, $mobile, 'REGISTRATION');
                        
                        // Send Email via SMTP
                        $mail_res = sendRegistrationOTPEmail($email, $full_name, $otp);

                        $_SESSION['temp_user_id'] = $user_id;
                        $_SESSION['temp_email'] = $email;
                        $_SESSION['temp_mobile'] = $mobile;
                        $_SESSION['temp_name'] = $full_name;
                        $_SESSION['demo_otp_display'] = $otp;

                        setFlash('info', "A 6-digit verification code has been sent to <strong>" . sanitize($email) . "</strong>. Please enter the OTP to activate your account.");
                        header("Location: " . BASE_URL . "/verify_otp.php");
                        exit;
                    }
                }
            } catch (Exception $e) {
                $error = "A database error occurred: " . $e->getMessage();
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
            <div class="col-md-8 col-lg-6">
                <div class="cirs-card shadow-lg p-4 p-sm-5 bg-white">
                    <div class="text-center mb-4">
                        <div class="cirs-logo-badge mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Create Citizen Account</h3>
                        <p class="text-muted small">Register to report civic problems and track redressal progress</p>
                    </div>

                    <?php renderFlash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/register.php" method="POST" id="registerForm">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= sanitize($form_data['full_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= sanitize($form_data['email']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Mobile Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="tel" name="mobile" class="form-control" placeholder="10-digit mobile" maxlength="10" pattern="[6-9][0-9]{9}" value="<?= sanitize($form_data['mobile']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Residential Address / Ward</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="address" class="form-control" placeholder="e.g. Flat 101, Green Valley, Ward 12" value="<?= sanitize($form_data['address']) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="reg_pass" class="form-control" placeholder="Min 6 chars" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="confirm_password" id="reg_cpass" class="form-control" placeholder="Re-enter password" required minlength="6">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i> Register Account
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top small text-muted">
                        Already have an account? 
                        <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary">Log In Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
