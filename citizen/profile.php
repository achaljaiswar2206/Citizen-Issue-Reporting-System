<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen Profile Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);
$page_title = "Citizen Profile";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/citizen/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-person-gear text-primary me-2"></i>My Profile Settings</h3>
                <p class="text-muted small mb-0">Manage your contact details and security credentials.</p>
            </div>
        </div>

        <?php renderFlash(); ?>

        <div class="row g-4">
            <!-- Left: Citizen Info Overview Card -->
            <div class="col-lg-4">
                <div class="cirs-card p-4 bg-white shadow-sm text-center">
                    <div class="cirs-logo-badge mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= sanitize($user['full_name']) ?></h5>
                    <p class="text-muted small mb-3"><?= sanitize($user['email']) ?></p>

                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> Verified Citizen
                        </span>
                    </div>

                    <div class="text-start bg-light p-3 rounded-3 small text-secondary">
                        <div class="mb-2"><i class="bi bi-phone me-2 text-primary"></i> <strong>Mobile:</strong> <?= sanitize($user['mobile']) ?></div>
                        <div class="mb-2"><i class="bi bi-geo-alt me-2 text-danger"></i> <strong>Address:</strong> <?= sanitize($user['address'] ?? 'Not set') ?></div>
                        <div><i class="bi bi-calendar-check me-2 text-success"></i> <strong>Registered:</strong> <?= formatDate($user['created_at'], 'd M Y') ?></div>
                    </div>
                </div>
            </div>

            <!-- Right: Edit Profile & Password Changer -->
            <div class="col-lg-8">
                <!-- 1. Update Contact Info -->
                <div class="cirs-card p-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Update Personal Details</h5>
                    
                    <form action="<?= BASE_URL ?>/citizen/update_profile.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_details">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= sanitize($user['full_name']) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Email Address (Read-only)</label>
                                <input type="email" class="form-control bg-light" value="<?= sanitize($user['email']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Mobile Number (Read-only)</label>
                                <input type="tel" class="form-control bg-light" value="<?= sanitize($user['mobile']) ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Residential Address / Ward</label>
                            <input type="text" name="address" class="form-control" value="<?= sanitize($user['address'] ?? '') ?>" placeholder="Flat/House, Society, Ward Number">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- 2. Change Password -->
                <div class="cirs-card p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-key-fill text-warning me-2"></i>Change Password</h5>

                    <form action="<?= BASE_URL ?>/citizen/update_profile.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">New Password (Min 6 chars)</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="••••••••">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning btn-sm px-3 fw-bold text-dark">
                            <i class="bi bi-shield-check me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
