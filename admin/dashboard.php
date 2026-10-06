<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Analytics Dashboard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$admin = getCurrentAdmin($pdo);
$page_title = "Admin Dashboard";

// 1. Fetch KPI Statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'emergency' => 0,
    'unverified' => 0
];

try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'IN PROGRESS' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN priority = 'Emergency' AND status != 'RESOLVED' THEN 1 ELSE 0 END) as emergency,
        SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as unverified
        FROM complaints");
    $db_stats = $stmt->fetch();
    if ($db_stats) {
        $stats = array_map('intval', $db_stats);
    }

    // 2. Complaints by Category for Chart.js
    $stmt_cat = $pdo->query("SELECT category, COUNT(*) as count FROM complaints GROUP BY category");
    $cat_data = $stmt_cat->fetchAll();
    $chart_cat_labels = [];
    $chart_cat_counts = [];
    foreach ($cat_data as $row) {
        $chart_cat_labels[] = $row['category'];
        $chart_cat_counts[] = (int)$row['count'];
    }

    // 3. Complaints by Department for Chart.js
    $stmt_dept = $pdo->query("SELECT d.department_name, COUNT(c.id) as count 
        FROM departments d 
        LEFT JOIN complaints c ON d.id = c.department_id 
        GROUP BY d.id, d.department_name");
    $dept_data = $stmt_dept->fetchAll();
    $chart_dept_labels = [];
    $chart_dept_counts = [];
    foreach ($dept_data as $row) {
        $chart_dept_labels[] = $row['department_name'];
        $chart_dept_counts[] = (int)$row['count'];
    }

    // 4. Fetch 5 Recent Urgent or Pending Complaints
    $stmt_recent = $pdo->query("SELECT c.*, u.full_name as citizen_name, u.mobile as citizen_mobile, d.department_name 
        FROM complaints c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN departments d ON c.department_id = d.id 
        ORDER BY (CASE WHEN c.priority = 'Emergency' THEN 1 WHEN c.status = 'PENDING' THEN 2 ELSE 3 END), c.created_at DESC 
        LIMIT 6");
    $recent_complaints = $stmt_recent->fetchAll();

} catch (Exception $e) {
    $recent_complaints = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container-fluid px-lg-5">
        <!-- Admin Dashboard Header -->
        <div class="cirs-card p-4 mb-4 bg-dark text-white shadow-sm border-0" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-warning text-dark px-3 py-1 font-monospace fw-bold">ADMINISTRATION PORTAL</span>
                        <span class="text-secondary small">&bull; Central Municipal Control Room</span>
                    </div>
                    <h2 class="fw-bold mb-1 text-white">Municipal Control Overview</h2>
                    <p class="mb-0 text-secondary small">Real-time civic redressal analytics, department assignments, and complaint monitoring.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-inbox-fill me-1"></i> Manage All Complaints
                    </a>
                    <a href="<?= BASE_URL ?>/admin/departments.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-building me-1"></i> Departments
                    </a>
                </div>
            </div>
        </div>

        <?php renderFlash(); ?>

        <!-- 5 Master KPI Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val"><?= $stats['total'] ?></div>
                        <div class="kpi-lbl">Total Filed</div>
                    </div>
                    <div class="kpi-icon bg-primary-subtle text-primary">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg">
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

            <div class="col-6 col-lg">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val text-info"><?= $stats['in_progress'] ?></div>
                        <div class="kpi-lbl">In Progress</div>
                    </div>
                    <div class="kpi-icon bg-info-subtle text-info">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg">
                <div class="stat-kpi-card">
                    <div>
                        <div class="kpi-val text-success"><?= $stats['resolved'] ?></div>
                        <div class="kpi-lbl">Resolved</div>
                    </div>
                    <div class="kpi-icon bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg">
                <div class="stat-kpi-card border-danger-subtle bg-danger-subtle bg-opacity-25">
                    <div>
                        <div class="kpi-val text-danger"><?= $stats['emergency'] ?></div>
                        <div class="kpi-lbl text-danger fw-bold">Emergency Active</div>
                    </div>
                    <div class="kpi-icon bg-danger-subtle text-danger">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Analytics Chart Section (Chart.js) -->
        <div class="row g-4 mb-4">
            <!-- Category Breakdown Bar Chart -->
            <div class="col-lg-6">
                <div class="cirs-card p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-bar-chart-line text-primary me-2"></i>Complaints by Category</h5>
                    <div style="position: relative; height: 260px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Doughnut Chart -->
            <div class="col-lg-6">
                <div class="cirs-card p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-pie-chart text-success me-2"></i>Resolution Status Distribution</h5>
                    <div style="position: relative; height: 260px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority & Urgent Action Complaints Table -->
        <div class="cirs-card shadow-sm bg-white mb-4">
            <div class="cirs-card-header">
                <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Priority & Recent Action Required</h5>
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-outline-primary btn-sm">
                    View Master List (<?= $stats['total'] ?>) <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="cirs-card-body p-0">
                <?php if (empty($recent_complaints)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-all text-success fs-1 d-block mb-2"></i>
                        <p class="mb-0">All civic complaints have been processed. No urgent issues pending.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Citizen</th>
                                    <th>Category & Title</th>
                                    <th>Location</th>
                                    <th>Dept Assigned</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_complaints as $c): ?>
                                    <tr>
                                        <td class="ps-4 font-monospace fw-bold text-primary">
                                            <a href="<?= BASE_URL ?>/admin/complaint_details.php?id=<?= $c['id'] ?>">
                                                <?= sanitize($c['complaint_id']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= sanitize($c['citizen_name'] ?? 'Citizen') ?></div>
                                            <small class="text-muted"><?= sanitize($c['citizen_mobile'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border me-1">
                                                <?= sanitize($c['category']) ?>
                                            </span>
                                            <span class="fw-semibold text-dark"><?= sanitize($c['title']) ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <i class="bi bi-geo-alt text-danger"></i> <?= sanitize(substr($c['location'], 0, 30)) ?>...
                                        </td>
                                        <td class="small text-secondary">
                                            <?= sanitize($c['department_name'] ?? '<span class="text-danger fw-bold">Unassigned</span>') ?>
                                        </td>
                                        <td>
                                            <?= getPriorityBadge($c['priority']) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($c['status']) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= BASE_URL ?>/admin/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-gear-fill me-1"></i> Process
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
    </div>
</main>

<!-- Chart.js Visualization Scripts -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Categories Chart
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat && typeof Chart !== 'undefined') {
        new Chart(ctxCat, {
            type: 'bar',
            data: {
                labels: <?= json_encode(!empty($chart_cat_labels) ? $chart_cat_labels : ['Pothole', 'Garbage', 'Water', 'Streetlight', 'Drainage']) ?>,
                datasets: [{
                    label: 'Complaints',
                    data: <?= json_encode(!empty($chart_cat_counts) ? $chart_cat_counts : [1, 1, 0, 1, 1]) ?>,
                    backgroundColor: ['#f59e0b', '#10b981', '#0284c7', '#eab308', '#6366f1', '#ef4444', '#8b5cf6'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // 2. Status Doughnut Chart
    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus && typeof Chart !== 'undefined') {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Pending Review', 'In Progress', 'Resolved'],
                datasets: [{
                    data: [<?= $stats['pending'] ?>, <?= $stats['in_progress'] ?>, <?= $stats['resolved'] ?>],
                    backgroundColor: ['#f59e0b', '#0284c7', '#10b981'],
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
