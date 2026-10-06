  <?php
/**
 * Citizen Issue Reporting System (CIRS)
 * About & Citizen Charter Page
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "About CIRS & Citizen Charter";

// Fetch departments for directory
$departments = [];
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY id ASC");
    $departments = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-5">
    <div class="container">
        <!-- Hero Header -->
        <div class="row justify-content-center mb-5 text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase fs-7">Municipal Transparency</span>
                <h1 class="fw-bold mt-2 display-5">About the CIRS Initiative</h1>
                <p class="text-muted lead">Empowering citizens to actively participate in urban governance, neighborhood improvement, and transparent civic redressal.</p>
            </div>
        </div>

        <!-- Vision & Mission Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="cirs-card p-4 h-100 shadow-sm border-top border-4 border-primary">
                    <div class="kpi-icon bg-primary-subtle text-primary mb-3">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <h5 class="fw-bold">Our Vision</h5>
                    <p class="text-muted small leading-relaxed">
                        To build clean, sustainable, well-maintained smart cities where every citizen has a direct, transparent channel to resolve municipal issues quickly.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cirs-card p-4 h-100 shadow-sm border-top border-4 border-success">
                    <div class="kpi-icon bg-success-subtle text-success mb-3">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <h5 class="fw-bold">Our Mission</h5>
                    <p class="text-muted small leading-relaxed">
                        To eliminate bureaucratic delays by routing grievances to designated departments automatically with live progress tracking and accountability.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cirs-card p-4 h-100 shadow-sm border-top border-4 border-warning">
                    <div class="kpi-icon bg-warning-subtle text-warning mb-3">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold">Citizen Charter</h5>
                    <p class="text-muted small leading-relaxed">
                        Committed to initial complaint verification within 24 hours and priority-based field resolution within 48 to 72 working hours.
                    </p>
                </div>
            </div>
        </div>

        <!-- Predefined Civic Departments Directory -->
        <div class="cirs-card p-4 p-md-5 mb-5 shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h3 class="fw-bold mb-1">Participating Municipal Departments</h3>
                    <p class="text-muted small mb-0">Predefined civic departments handling complaints in the CIRS system.</p>
                </div>
                <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> File a Complaint
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($departments as $dept): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 rounded-3 border bg-light h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-building text-primary me-2"></i><?= sanitize($dept['department_name']) ?></h6>
                            <p class="text-muted small mb-3"><?= sanitize($dept['description']) ?></p>
                            <div class="small text-secondary pt-2 border-top">
                                <div class="mb-1"><i class="bi bi-envelope me-1"></i> <?= sanitize($dept['contact_email'] ?? 'dept@cirs.gov') ?></div>
                                <div><i class="bi bi-telephone me-1"></i> <?= sanitize($dept['phone'] ?? '1800-111-2000') ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
