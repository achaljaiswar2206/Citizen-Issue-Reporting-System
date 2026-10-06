<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Departments & Workload Overview
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$admin = getCurrentAdmin($pdo);
$page_title = "Municipal Departments";

// Fetch Departments with Complaint Counts Breakdown
try {
    $stmt = $pdo->query("SELECT d.*, 
        COUNT(c.id) as total_complaints,
        SUM(CASE WHEN c.status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN c.status = 'IN PROGRESS' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN c.status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved_count
        FROM departments d 
        LEFT JOIN complaints c ON d.id = c.department_id 
        GROUP BY d.id 
        ORDER BY d.id ASC");
    $departments = $stmt->fetchAll();
} catch (Exception $e) {
    $departments = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container-fluid px-lg-5">
        <!-- Breadcrumb & Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-1">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Municipal Departments</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-building-check text-primary me-2"></i>Municipal Departments & Workloads</h3>
                <small class="text-muted">Predefined civic departments available in CIRS with live grievance distribution.</small>
            </div>
            <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-primary btn-sm">
                <i class="bi bi-inbox me-1"></i> View All Complaints
            </a>
        </div>

        <?php renderFlash(); ?>

        <!-- Department Cards Grid -->
        <div class="row g-4">
            <?php foreach ($departments as $d): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="cirs-card p-4 bg-white shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="kpi-icon bg-primary-subtle text-primary" style="width:45px; height:45px; font-size:1.3rem;">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark"><?= sanitize($d['department_name']) ?></h5>
                                        <span class="badge bg-light text-secondary border small">Keyword: <?= sanitize($d['category_keyword']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted small mb-3"><?= sanitize($d['description']) ?></p>

                            <!-- Workload Metrics -->
                            <div class="bg-light p-3 rounded-3 mb-3">
                                <div class="row text-center g-2 small">
                                    <div class="col-4">
                                        <div class="fw-bold text-warning fs-5"><?= (int)$d['pending_count'] ?></div>
                                        <div class="text-muted fs-7">Pending</div>
                                    </div>
                                    <div class="col-4 border-start border-end">
                                        <div class="fw-bold text-info fs-5"><?= (int)$d['in_progress_count'] ?></div>
                                        <div class="text-muted fs-7">In Progress</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-success fs-5"><?= (int)$d['resolved_count'] ?></div>
                                        <div class="text-muted fs-7">Resolved</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact info -->
                            <div class="small text-muted mb-3">
                                <div class="mb-1"><i class="bi bi-envelope me-2 text-primary"></i> <?= sanitize($d['contact_email'] ?? 'N/A') ?></div>
                                <div><i class="bi bi-telephone me-2 text-primary"></i> <?= sanitize($d['phone'] ?? '1800-111-2000') ?></div>
                            </div>
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-secondary">Total: <strong><?= $d['total_complaints'] ?></strong> Issues</span>
                            <a href="<?= BASE_URL ?>/admin/complaints.php?dept=<?= $d['id'] ?>" class="btn btn-outline-primary btn-sm">
                                View Department Workload <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
