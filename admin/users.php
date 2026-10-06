<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Citizens Management & Account Maintenance
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$admin = getCurrentAdmin($pdo);
$page_title = "Registered Citizens Directory";

// 1. AUTOMATED CLEANUP: Delete unverified accounts older than 7 days
$auto_purged_count = 0;
try {
    $stmt_auto = $pdo->query("DELETE FROM users WHERE is_verified = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $auto_purged_count = $stmt_auto->rowCount();
} catch (Exception $e) {
    error_log("Auto purge unverified users failed: " . $e->getMessage());
}

// 2. HANDLE POST ACTIONS (Manual Delete & Manual Maintenance Purge)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('danger', 'Security token expired. Please try again.');
        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Action A: Manual Delete Single Citizen
    if ($action === 'delete_citizen') {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        try {
            // Fetch citizen details for flash feedback
            $stmt_u = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
            $stmt_u->execute([$user_id]);
            $target_user = $stmt_u->fetch();

            if (!$target_user) {
                setFlash('danger', 'Citizen account not found.');
            } else {
                // Delete associated OTP records first
                $stmt_otp = $pdo->prepare("DELETE FROM otp_verifications WHERE user_id = ? OR email = ?");
                $stmt_otp->execute([$user_id, $target_user['email']]);

                // Delete the user record (foreign keys cascade to complaints and complaint_updates)
                $stmt_del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt_del->execute([$user_id]);

                setFlash('success', "Citizen account '{$target_user['full_name']}' ({$target_user['email']}) and all associated records were permanently deleted.");
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting citizen account: ' . $e->getMessage());
        }

        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }

    // Action B: Manual Purge of Unverified Accounts older than 7 days
    if ($action === 'purge_unverified_7days') {
        try {
            $stmt_purge = $pdo->query("DELETE FROM users WHERE is_verified = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $purged_count = $stmt_purge->rowCount();

            if ($purged_count > 0) {
                setFlash('success', "Maintenance complete: Successfully deleted {$purged_count} unverified citizen accounts older than 7 days.");
            } else {
                setFlash('info', 'No unverified citizen accounts older than 7 days were found.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error purging unverified accounts: ' . $e->getMessage());
        }

        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }
}

// 3. STATS QUERY
$total_citizens_count = 0;
$verified_citizens_count = 0;
$unverified_citizens_count = 0;
$expired_unverified_count = 0;

try {
    $stmt_stat = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as unverified,
        SUM(CASE WHEN is_verified = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expired_unverified
        FROM users");
    $stats_row = $stmt_stat->fetch();
    if ($stats_row) {
        $total_citizens_count = (int)$stats_row['total'];
        $verified_citizens_count = (int)$stats_row['verified'];
        $unverified_citizens_count = (int)$stats_row['unverified'];
        $expired_unverified_count = (int)$stats_row['expired_unverified'];
    }
} catch (Exception $e) {}

// 4. FETCH CITIZENS LIST
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'ALL';

$sql = "SELECT u.*, COUNT(c.id) as total_complaints 
        FROM users u 
        LEFT JOIN complaints c ON u.id = c.user_id 
        WHERE 1=1";

$params = [];
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR u.mobile LIKE :search OR u.address LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter === 'VERIFIED') {
    $sql .= " AND u.is_verified = 1";
} elseif ($status_filter === 'UNVERIFIED') {
    $sql .= " AND u.is_verified = 0";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $citizens = $stmt->fetchAll();
} catch (Exception $e) {
    $citizens = [];
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
                        <li class="breadcrumb-item active" aria-current="page">Registered Citizens</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Registered Citizens Directory</h3>
                <small class="text-muted">Manage citizen accounts, verify records, or delete inactive profiles with automated 7-day pruning.</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#purgeExpiredModal" title="Delete unverified accounts older than 7 days">
                    <i class="bi bi-clock-history me-1"></i> Purge Unverified (&gt;7 Days)
                </button>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-light border btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </a>
            </div>
        </div>

        <?php renderFlash(); ?>

        <?php if ($auto_purged_count > 0): ?>
            <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Automated System Maintenance:</strong> <?= $auto_purged_count ?> unverified citizen account(s) older than 7 days were automatically pruned.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Summary Metrics Bar -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary-subtle text-primary" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Registered</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $total_citizens_count ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success-subtle text-success" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Verified Accounts</div>
                        <h4 class="fw-bold mb-0 text-success"><?= $verified_citizens_count ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-warning-subtle text-warning" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Unverified Accounts</div>
                        <h4 class="fw-bold mb-0 text-warning"><?= $unverified_citizens_count ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-danger-subtle text-danger" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Auto-Prune Policy</div>
                        <div class="fw-bold text-dark small"><i class="bi bi-check2-circle text-success me-1"></i>Active (7 Days)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Toolbar -->
        <div class="cirs-card p-3 mb-4 bg-white shadow-sm">
            <form action="<?= BASE_URL ?>/admin/users.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, mobile, ward..." value="<?= sanitize($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-funnel"></i></span>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>All Account Statuses</option>
                            <option value="VERIFIED" <?= $status_filter === 'VERIFIED' ? 'selected' : '' ?>>Verified Only</option>
                            <option value="UNVERIFIED" <?= $status_filter === 'UNVERIFIED' ? 'selected' : '' ?>>Unverified Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Search</button>
                    <?php if (!empty($search) || $status_filter !== 'ALL'): ?>
                        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Citizens Table -->
        <div class="cirs-card shadow-sm bg-white">
            <div class="cirs-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">Citizen Records</h5>
                    <span class="badge bg-primary rounded-pill"><?= count($citizens) ?> Listed</span>
                </div>
                <small class="text-muted"><i class="bi bi-shield-lock me-1"></i> Admin Directory & Data Controls</small>
            </div>
            <div class="cirs-card-body p-0">
                <?php if (empty($citizens)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                        <p class="mb-0">No registered citizens found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Citizen Name</th>
                                    <th>Email Address</th>
                                    <th>Mobile Number</th>
                                    <th>Residential Ward / Address</th>
                                    <th>Total Complaints</th>
                                    <th>Account Status</th>
                                    <th>Registered On</th>
                                    <th class="text-end pe-4" style="min-width: 170px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citizens as $u): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="cirs-logo-badge" style="width:34px; height:34px; font-size:0.95rem;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block"><?= sanitize($u['full_name']) ?></strong>
                                                    <small class="text-muted font-monospace">UID: #<?= $u['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted">
                                            <a href="mailto:<?= sanitize($u['email']) ?>" class="text-dark">
                                                <?= sanitize($u['email']) ?>
                                            </a>
                                        </td>
                                        <td class="text-dark font-monospace"><?= sanitize($u['mobile']) ?></td>
                                        <td class="small text-muted" style="max-width: 200px;"><?= sanitize($u['address'] ?? 'Not provided') ?></td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fw-bold">
                                                <?= $u['total_complaints'] ?> Reported
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['is_verified']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Will auto-delete after 7 days if unverified"><i class="bi bi-clock me-1"></i>Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= formatDate($u['created_at'], 'd M Y') ?></td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex gap-1">
                                                <a href="<?= BASE_URL ?>/admin/complaints.php?search=<?= urlencode($u['full_name']) ?>" class="btn btn-outline-primary btn-sm" title="View Citizen Complaints">
                                                    <i class="bi bi-inbox me-1"></i> Issues
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="openDeleteCitizenModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', <?= (int)$u['total_complaints'] ?>)" title="Delete Citizen Account">
                                                    <i class="bi bi-trash3 me-1"></i> Delete
                                                </button>
                                            </div>
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

<!-- MODAL 1: Single Citizen Delete Confirmation -->
<div class="modal fade" id="deleteCitizenModal" tabindex="-1" aria-labelledby="deleteCitizenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="deleteCitizenModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Citizen Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/users.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_citizen">
                <input type="hidden" name="user_id" id="delete_modal_user_id" value="">

                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">Are you sure you want to permanently delete this citizen account from CIRS?</p>
                    
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="mb-1"><span class="text-muted small">Citizen Name:</span> <strong class="text-dark" id="delete_modal_user_name">-</strong></div>
                        <div class="mb-1"><span class="text-muted small">Email Address:</span> <span class="text-dark" id="delete_modal_user_email">-</span></div>
                        <div><span class="text-muted small">Associated Complaints:</span> <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" id="delete_modal_complaints_count">0</span></div>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i> <strong>Warning:</strong> Deleting this citizen account will also remove all associated grievances, evidence records, and OTP verification logs. This action cannot be reversed.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash3 me-1"></i> Permanently Delete Citizen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Purge Unverified Accounts (>7 Days) Confirmation -->
<div class="modal fade" id="purgeExpiredModal" tabindex="-1" aria-labelledby="purgeExpiredModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="purgeExpiredModalLabel">
                    <i class="bi bi-clock-history text-warning me-2"></i> Purge Expired Unverified Accounts
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/users.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="purge_unverified_7days">

                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">This maintenance task will purge all unverified citizen registrations that have remained inactive and unverified for <strong>more than 7 days</strong>.</p>
                    
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Currently Unverified Accounts:</span>
                            <span class="badge bg-warning text-dark fw-bold"><?= $unverified_citizens_count ?> Total</span>
                        </div>
                    </div>

                    <p class="small text-muted mb-0">
                        <i class="bi bi-shield-check text-success me-1"></i> Verified citizen accounts and verified active grievances will NOT be affected.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash3 me-1"></i> Run 7-Day Purge Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteCitizenModal(userId, userName, userEmail, complaintsCount) {
    document.getElementById('delete_modal_user_id').value = userId;
    document.getElementById('delete_modal_user_name').innerText = userName;
    document.getElementById('delete_modal_user_email').innerText = userEmail;
    document.getElementById('delete_modal_complaints_count').innerText = complaintsCount + ' Complaints';
    
    var modal = new bootstrap.Modal(document.getElementById('deleteCitizenModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

