<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Master Complaints Management & Search/Filter
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$admin = getCurrentAdmin($pdo);
$page_title = "Manage All Complaints";

// Fetch All Departments for Filter Dropdown
$departments = [];
try {
    $stmt_depts = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
    $departments = $stmt_depts->fetchAll();
} catch (Exception $e) {}

// Filter Parameters
$search_query = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'ALL';
$dept_filter = $_GET['dept'] ?? 'ALL';
$priority_filter = $_GET['priority'] ?? 'ALL';
$category_filter = $_GET['category'] ?? 'ALL';

// Build Dynamic SQL Query with Prepared Statements
$sql = "SELECT c.*, u.full_name as citizen_name, u.mobile as citizen_mobile, d.department_name 
        FROM complaints c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN departments d ON c.department_id = d.id 
        WHERE 1=1";

$params = [];

if (!empty($search_query)) {
    $sql .= " AND (c.complaint_id LIKE :search OR c.title LIKE :search OR c.location LIKE :search OR u.full_name LIKE :search OR u.mobile LIKE :search)";
    $params[':search'] = "%{$search_query}%";
}

if ($status_filter !== 'ALL' && in_array(strtoupper($status_filter), ['PENDING', 'IN PROGRESS', 'RESOLVED'])) {
    $sql .= " AND c.status = :status";
    $params[':status'] = strtoupper($status_filter);
}

if ($dept_filter !== 'ALL' && is_numeric($dept_filter)) {
    $sql .= " AND c.department_id = :dept_id";
    $params[':dept_id'] = (int)$dept_filter;
}

if ($priority_filter !== 'ALL' && in_array($priority_filter, ['Low', 'Medium', 'High', 'Emergency'])) {
    $sql .= " AND c.priority = :priority";
    $params[':priority'] = $priority_filter;
}

if ($category_filter !== 'ALL' && array_key_exists($category_filter, $CATEGORIES)) {
    $sql .= " AND c.category = :category";
    $params[':category'] = $category_filter;
}

$sql .= " ORDER BY c.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll();
} catch (Exception $e) {
    $complaints = [];
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
                        <li class="breadcrumb-item active" aria-current="page">Manage Complaints</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-inbox-fill text-primary me-2"></i>Master Complaints Registry</h3>
                <small class="text-muted">Search, verify, assign departments, and monitor progress on all civic issues.</small>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i> Print Table
                </button>
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-light border btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </a>
            </div>
        </div>

        <?php renderFlash(); ?>

        <!-- Search and Multi-Criteria Filtering Box -->
        <div class="cirs-card p-4 bg-white shadow-sm mb-4">
            <form action="<?= BASE_URL ?>/admin/complaints.php" method="GET" class="row g-3">
                <!-- Search text -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Search Keyword</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="ID, Citizen, Location..." value="<?= sanitize($search_query) ?>">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary">Status</label>
                    <select name="status" class="form-select">
                        <option value="ALL" <?= ($status_filter === 'ALL') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="PENDING" <?= ($status_filter === 'PENDING') ? 'selected' : '' ?>>Pending Review</option>
                        <option value="IN PROGRESS" <?= ($status_filter === 'IN PROGRESS') ? 'selected' : '' ?>>In Progress</option>
                        <option value="RESOLVED" <?= ($status_filter === 'RESOLVED') ? 'selected' : '' ?>>Resolved</option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div class="col-lg-3 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary">Department</label>
                    <select name="dept" class="form-select">
                        <option value="ALL" <?= ($dept_filter === 'ALL') ? 'selected' : '' ?>>All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($dept_filter == $d['id']) ? 'selected' : '' ?>>
                                <?= sanitize($d['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="col-lg-2 col-md-6 col-6">
                    <label class="form-label small fw-semibold text-secondary">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="ALL" <?= ($priority_filter === 'ALL') ? 'selected' : '' ?>>All Priorities</option>
                        <option value="Low" <?= ($priority_filter === 'Low') ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= ($priority_filter === 'Medium') ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= ($priority_filter === 'High') ? 'selected' : '' ?>>High</option>
                        <option value="Emergency" <?= ($priority_filter === 'Emergency') ? 'selected' : '' ?>>Emergency</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-lg-2 col-md-6 col-6">
                    <label class="form-label small fw-semibold text-secondary">Category</label>
                    <select name="category" class="form-select">
                        <option value="ALL" <?= ($category_filter === 'ALL') ? 'selected' : '' ?>>All Categories</option>
                        <?php foreach ($CATEGORIES as $cat_k => $cat_v): ?>
                            <option value="<?= sanitize($cat_k) ?>" <?= ($category_filter === $cat_k) ? 'selected' : '' ?>>
                                <?= sanitize($cat_k) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Clear Filters
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                        <i class="bi bi-filter me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Master Table View -->
        <div class="cirs-card shadow-sm bg-white">
            <div class="cirs-card-header">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">Complaints Records</h5>
                    <span class="badge bg-primary rounded-pill"><?= count($complaints) ?> found</span>
                </div>
            </div>
            <div class="cirs-card-body p-0">
                <?php if (empty($complaints)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search text-secondary fs-1 d-block mb-3"></i>
                        <h6 class="fw-bold text-dark">No complaints match your query</h6>
                        <p class="small mb-3">Try adjusting your search keywords or clearing filter constraints.</p>
                        <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-outline-primary btn-sm">Reset All Filters</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 searchable-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Citizen Information</th>
                                    <th>Category</th>
                                    <th>Title & Location</th>
                                    <th>Date Filed</th>
                                    <th>Department</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Verification</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td class="ps-4 font-monospace fw-bold text-primary">
                                            <a href="<?= BASE_URL ?>/admin/complaint_details.php?id=<?= $c['id'] ?>">
                                                <?= sanitize($c['complaint_id']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= sanitize($c['citizen_name'] ?? 'Citizen') ?></div>
                                            <small class="text-muted"><i class="bi bi-phone"></i> <?= sanitize($c['citizen_mobile'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi <?= $CATEGORIES[$c['category']]['icon'] ?? 'bi-tag' ?> me-1"></i>
                                                <?= sanitize($c['category']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= sanitize($c['title']) ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt text-danger"></i> <?= sanitize($c['location']) ?></small>
                                        </td>
                                        <td class="small text-muted">
                                            <?= formatDate($c['created_at'], 'd M Y, h:i A') ?>
                                        </td>
                                        <td class="small text-secondary">
                                            <?= sanitize($c['department_name'] ?? '<span class="badge bg-danger-subtle text-danger">Unassigned</span>') ?>
                                        </td>
                                        <td>
                                            <?= getPriorityBadge($c['priority']) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($c['status']) ?>
                                        </td>
                                        <td>
                                            <?php if ($c['is_verified']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= BASE_URL ?>/admin/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-pencil-square me-1"></i> Review
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
