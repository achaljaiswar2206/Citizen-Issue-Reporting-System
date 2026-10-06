<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Dynamic Responsive Navigation Bar
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = isset($_SESSION['admin_id']);
$is_citizen = isset($_SESSION['user_id']);
?>

<nav class="navbar navbar-expand-lg cirs-navbar sticky-top">
    <div class="<?= $is_admin ? 'container-fluid px-lg-4' : 'container' ?>">
        <!-- Brand Logo -->
        <a class="navbar-brand" href="<?= $is_admin ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php' ?>">
            <div class="cirs-logo-badge">
                <i class="bi bi-buildings"></i>
            </div>
            <span><?= APP_SHORT_NAME ?></span>
            <?php if ($is_admin): ?>
                <span class="badge bg-dark text-warning border border-warning-subtle rounded-pill fs-7 d-none d-sm-inline ms-1"><i class="bi bi-shield-lock-fill me-1"></i>Admin Control</span>
            <?php else: ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-7 d-none d-sm-inline ms-1">Civic Portal</span>
            <?php endif; ?>
        </a>

        <!-- Mobile Hamburger Toggle -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <?php if ($is_admin): ?>
                <!-- ADMIN NAVIGATION (Left side directly in navbar, public links removed) -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-1 text-primary"></i> Admin Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($current_page, ['complaints.php', 'complaint_details.php']) ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/complaints.php">
                            <i class="bi bi-inbox-fill me-1 text-primary"></i> All Complaints
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'departments.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/departments.php">
                            <i class="bi bi-building me-1 text-primary"></i> Departments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'users.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php">
                            <i class="bi bi-people-fill me-1 text-primary"></i> Citizens List
                        </a>
                    </li>
                </ul>

                <!-- Admin Profile Badge & Logout on Right -->
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-2 fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-person-badge-fill text-warning"></i>
                        <span><?= sanitize($_SESSION['admin_name'] ?? 'Municipal Commissioner') ?></span>
                    </span>
                    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview Public Citizen Portal">
                        <i class="bi bi-globe me-1"></i> Public Site
                    </a>
                    <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-sm btn-danger shadow-sm">
                        <i class="bi bi-box-arrow-right me-1"></i> Admin Logout
                    </a>
                </div>

            <?php else: ?>
                <!-- PUBLIC / CITIZEN NAVIGATION -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php#how-it-works">
                            <i class="bi bi-diagram-3 me-1"></i> How It Works
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'file_complaint.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/citizen/file_complaint.php">
                            <i class="bi bi-plus-circle me-1"></i> Report Issue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'track.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/track.php">
                            <i class="bi bi-search me-1"></i> Track Complaint
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'about.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">
                            <i class="bi bi-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'contact.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact.php">
                            <i class="bi bi-telephone me-1"></i> Contact
                        </a>
                    </li>
                </ul>

                <!-- Auth Buttons / User Profile Navigation -->
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <?php if ($is_citizen): ?>
                        <!-- Citizen Logged In Menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5"></i>
                                <span class="d-none d-md-inline"><?= sanitize($_SESSION['user_name'] ?? 'Citizen Account') ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li><h6 class="dropdown-header text-uppercase fs-7 text-muted">Citizen Portal</h6></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/citizen/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/citizen/complaints.php"><i class="bi bi-card-list me-2 text-primary"></i> My Complaints</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/citizen/file_complaint.php"><i class="bi bi-plus-circle me-2 text-success"></i> File New Issue</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/citizen/profile.php"><i class="bi bi-person-gear me-2 text-secondary"></i> My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Public Guest Actions -->
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary shadow-sm">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                        <button type="button" class="btn btn-sm btn-light border text-muted" data-bs-toggle="modal" data-bs-target="#adminRestrictedModal" title="Municipal Staff Access">
                            <i class="bi bi-shield-lock"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
