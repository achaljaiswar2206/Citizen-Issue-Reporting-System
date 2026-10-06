<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen's Complaints List ("My Complaints")
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);
$page_title = "My Complaints";

$status_filter = $_GET['status'] ?? 'ALL';
$search_query = trim($_GET['search'] ?? '');

$sql = "SELECT c.*, d.department_name 
        FROM complaints c 
        LEFT JOIN departments d ON c.department_id = d.id 
        WHERE c.user_id = :user_id";

$params = [':user_id' => $user['id']];

if (in_array(strtoupper($status_filter), ['PENDING', 'IN PROGRESS', 'RESOLVED'])) {
    $sql .= " AND c.status = :status";
    $params[':status'] = strtoupper($status_filter);
}

if (!empty($search_query)) {
    $sql .= " AND (c.complaint_id LIKE :search OR c.title LIKE :search OR c.location LIKE :search OR c.category LIKE :search)";
    $params[':search'] = "%{$search_query}%";
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
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/citizen/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Complaints</li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-card-list text-primary me-2"></i>My Reported Grievances</h3>
                <p class="text-muted small mb-0">View all civic issues you have reported along with current municipal status.</p>
            </div>
            <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> File New Complaint
            </a>
        </div>

        <?php renderFlash(); ?>

        <!-- Search & Filter Toolbar -->
        <div class="cirs-card p-3 mb-4 bg-white shadow-sm">
            <form action="<?= BASE_URL ?>/citizen/complaints.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by ID, title, location..." value="<?= sanitize($search_query) ?>">
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="btn-group w-100" role="group">
                        <a href="<?= BASE_URL ?>/citizen/complaints.php?status=ALL" class="btn btn-sm <?= ($status_filter === 'ALL' || empty($status_filter)) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            All
                        </a>
                        <a href="<?= BASE_URL ?>/citizen/complaints.php?status=PENDING" class="btn btn-sm <?= ($status_filter === 'PENDING') ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">
                            Pending
                        </a>
                        <a href="<?= BASE_URL ?>/citizen/complaints.php?status=IN PROGRESS" class="btn btn-sm <?= ($status_filter === 'IN PROGRESS') ? 'btn-info text-white' : 'btn-outline-secondary' ?>">
                            In Progress
                        </a>
                        <a href="<?= BASE_URL ?>/citizen/complaints.php?status=RESOLVED" class="btn btn-sm <?= ($status_filter === 'RESOLVED') ? 'btn-success' : 'btn-outline-secondary' ?>">
                            Resolved
                        </a>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Complaints List -->
        <div class="cirs-card shadow-sm bg-white">
            <div class="cirs-card-body p-0">
                <?php if (empty($complaints)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted fs-1 d-block mb-3"></i>
                        <h6 class="fw-bold text-dark">No complaints found matching your criteria</h6>
                        <p class="text-muted small mb-3">Try clearing filters or search parameters.</p>
                        <a href="<?= BASE_URL ?>/citizen/complaints.php" class="btn btn-outline-secondary btn-sm me-2">Clear Filters</a>
                        <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-primary btn-sm">Report New Issue</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Complaint ID</th>
                                    <th>Category</th>
                                    <th>Complaint Title</th>
                                    <th>Location</th>
                                    <th>Date Filed</th>
                                    <th>Department</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $c): ?>
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
                                            <small class="text-muted"><?= sanitize(substr($c['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td class="small text-muted">
                                            <i class="bi bi-geo-alt text-danger me-1"></i> <?= sanitize($c['location']) ?>
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
                                                <i class="bi bi-eye-fill me-1"></i> Details
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
