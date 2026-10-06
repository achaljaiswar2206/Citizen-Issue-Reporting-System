<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen Dashboard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);
$page_title = "Citizen Dashboard";

// Fetch Citizen's Complaint Statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0
];

try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'IN PROGRESS' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved
        FROM complaints WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $res = $stmt->fetch();
    if ($res) {
        $stats['total'] = (int)$res['total'];
        $stats['pending'] = (int)$res['pending'];
        $stats['in_progress'] = (int)$res['in_progress'];
        $stats['resolved'] = (int)$res['resolved'];
    }

    // Fetch Recent Complaints
    $stmt_recent = $pdo->prepare("SELECT c.*, d.department_name 
        FROM complaints c 
        LEFT JOIN departments d ON c.department_id = d.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC LIMIT 5");
    $stmt_recent->execute([$user['id']]);
    $recent_complaints = $stmt_recent->fetchAll();
} catch (Exception $e) {
    $recent_complaints = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="cirs-card p-4 mb-4 bg-white shadow-sm border-0" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: white;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 small">Citizen Portal</span>
                        <span class="text-white-50 small">&bull; Verified Resident</span>
                    </div>
                    <h2 class="fw-bold mb-1 text-white">Welcome back, <?= sanitize($user['full_name']) ?>!</h2>
                    <p class="mb-0 text-light opacity-75 small">Track your reported civic grievances and manage your civic submissions.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-light text-primary fw-bold shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1 text-primary"></i> Report New Issue
                    </a>
                </div>
            </div>
        </div>

        <?php renderFlash(); ?>

        <!-- KPI Statistic Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val"><?= $stats['total'] ?></div>
                        <div class="kpi-lbl">Total Submitted</div>
                    </div>
                    <div class="kpi-icon bg-primary-subtle text-primary">
                        <i class="bi bi-folder-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val text-warning"><?= $stats['pending'] ?></div>
                        <div class="kpi-lbl">Pending Review</div>
                    </div>
                    <div class="kpi-icon bg-warning-subtle text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val text-info"><?= $stats['in_progress'] ?></div>
                        <div class="kpi-lbl">Under Action</div>
                    </div>
                    <div class="kpi-icon bg-info-subtle text-info">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val text-success"><?= $stats['resolved'] ?></div>
                        <div class="kpi-lbl">Resolved Issues</div>
                    </div>
                    <div class="kpi-icon bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Complaints Section -->
        <div class="cirs-card shadow-sm bg-white mb-4">
            <div class="cirs-card-header">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>My Recent Grievances</h5>
                <a href="<?= BASE_URL ?>/citizen/complaints.php" class="btn btn-outline-primary btn-sm">
                    View All My Complaints <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="cirs-card-body p-0">
                <?php if (empty($recent_complaints)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-card-checklist text-muted fs-1 d-block mb-3"></i>
                        <h6 class="fw-bold text-dark">No complaints submitted yet</h6>
                        <p class="text-muted small mb-3">Notice a pothole, waste accumulation, or street light issue? File your first report.</p>
                        <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> File an Issue Now
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Complaint ID</th>
                                    <th>Category</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Department</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_complaints as $c): ?>
                                    <tr>
                                        <td class="ps-4 font-monospace fw-bold text-primary">
                                            <?= sanitize($c['complaint_id']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi <?= $CATEGORIES[$c['category']]['icon'] ?? 'bi-tag' ?> me-1"></i>
                                                <?= sanitize($c['category']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= sanitize($c['title']) ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= sanitize(substr($c['location'], 0, 30)) ?>...</small>
                                        </td>
                                        <td class="small text-muted">
                                            <?= formatDate($c['created_at'], 'd M Y') ?>
                                        </td>
                                        <td class="small text-secondary">
                                            <?= sanitize($c['department_name'] ?? 'Pending Assignment') ?>
                                        </td>
                                        <td>
                                            <?= getPriorityBadge($c['priority']) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($c['status']) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= BASE_URL ?>/citizen/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye-fill"></i> Track
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Help Card -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-1">Need Quick Assistance?</h6>
                        <p class="text-muted small mb-0">Call our 24/7 central civic helpline at <strong>1800-111-2000</strong></p>
                    </div>
                    <a href="<?= BASE_URL ?>/contact.php" class="btn btn-outline-secondary btn-sm">Contact</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-1">Keep Your Profile Updated</h6>
                        <p class="text-muted small mb-0">Ensure your phone and address are accurate for faster response.</p>
                    </div>
                    <a href="<?= BASE_URL ?>/citizen/profile.php" class="btn btn-outline-secondary btn-sm">Profile</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
