<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Municipal Administrator Login Portal
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in as admin, redirect to admin dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

// Block citizen accounts from accessing admin portal
if (isset($_SESSION['user_id'])) {
    setFlash('danger', 'Access Denied: Citizen accounts are not authorized to access the Municipal Staff Portal.');
    header("Location: " . BASE_URL . "/citizen/dashboard.php");
    exit;
}

$page_title = "Admin Login - Municipal Control Room";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = "Session expired or invalid token.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter admin email and password.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];

                    setFlash('success', "Welcome back, " . $admin['name'] . "! Municipal control room active.");

                    $redirect = $_SESSION['admin_redirect'] ?? (BASE_URL . '/admin/dashboard.php');
                    unset($_SESSION['admin_redirect']);
                    header("Location: " . $redirect);
                    exit;
                } else {
                    $error = "Invalid administrator credentials.";
                }
            } catch (Exception $e) {
                $error = "Database connection error.";
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="d-flex align-items-center justify-content-center min-vh-100" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="cirs-card shadow-2xl p-4 p-sm-5 bg-white border-0">
                    <div class="text-center mb-4">
                        <div class="cirs-logo-badge mx-auto mb-3" style="width: 55px; height: 55px; font-size: 1.6rem; background: linear-gradient(135deg, #0f172a, #1e40af);">
                            <i class="bi bi-shield-lock-fill text-warning"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Municipal Admin Portal</h4>
                        <p class="text-muted small">Authorized municipal personnel only</p>
                    </div>

                    <?php renderFlash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-3 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/admin/login.php" method="POST">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Administrator Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope-at"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="admin@cirs.gov" required autofocus value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" id="admin_password" class="form-control" placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="admin_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2 fw-bold shadow">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Enter Control Room
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top small">
                        <a href="<?= BASE_URL ?>/index.php" class="text-secondary"><i class="bi bi-arrow-left"></i> Return to Public Website</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
