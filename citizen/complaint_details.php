<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Citizen's Complaint Details & Tracking View
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);
$complaint_db_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$page_title = "Complaint Details";

// Fetch Complaint (must belong to this citizen)
try {
    $stmt = $pdo->prepare("SELECT c.*, d.department_name, d.contact_email as dept_email, d.phone as dept_phone 
        FROM complaints c 
        LEFT JOIN departments d ON c.department_id = d.id 
        WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$complaint_db_id, $user['id']]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        setFlash('danger', 'Complaint not found or you do not have permission to view it.');
        header("Location: " . BASE_URL . "/citizen/complaints.php");
        exit;
    }

    // Fetch Updates
    $stmt_updates = $pdo->prepare("SELECT u.*, a.name as admin_name 
        FROM complaint_updates u 
        LEFT JOIN admins a ON u.admin_id = a.id 
        WHERE u.complaint_id = ? 
        ORDER BY u.created_at ASC");
    $stmt_updates->execute([$complaint['id']]);
    $updates = $stmt_updates->fetchAll();

} catch (Exception $e) {
    setFlash('danger', 'A database error occurred.');
    header("Location: " . BASE_URL . "/citizen/complaints.php");
    exit;
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
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/citizen/complaints.php">My Complaints</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= sanitize($complaint['complaint_id']) ?></li>
            </ol>
        </nav>

        <?php renderFlash(); ?>

        <!-- Master Status Card -->
        <div class="cirs-card p-4 mb-4 bg-white shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4 fw-bold font-monospace text-primary"><?= sanitize($complaint['complaint_id']) ?></span>
                        <button class="btn btn-sm btn-light border py-0 px-2" onclick="copyToClipboard('<?= sanitize($complaint['complaint_id']) ?>', this)">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-calendar3 me-1"></i> Filed on <?= formatDate($complaint['created_at']) ?>
                        <span class="mx-2">&bull;</span>
                        <i class="bi bi-tag me-1"></i> <?= sanitize($complaint['category']) ?>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?= getPriorityBadge($complaint['priority']) ?>
                    <?= getStatusBadge($complaint['status']) ?>
                </div>
            </div>

            <!-- Dynamic Horizontal Visual Timeline -->
            <?php
                $status = $complaint['status'];
                $is_verified = (bool)$complaint['is_verified'];
                $is_dept = !empty($complaint['department_id']);
            ?>
            <div class="horizontal-timeline">
                <!-- Step 1: Submitted -->
                <div class="h-step completed">
                    <div class="h-marker"><i class="bi bi-check-lg"></i></div>
                    <div>
                        <div class="h-step-title">Submitted</div>
                        <div class="h-step-desc"><?= formatDate($complaint['created_at'], 'd M, h:i A') ?></div>
                    </div>
                </div>

                <!-- Step 2: Verified -->
                <div class="h-step <?= $is_verified || $status !== 'PENDING' ? 'completed' : 'active' ?>">
                    <div class="h-marker">
                        <i class="bi <?= $is_verified || $status !== 'PENDING' ? 'bi-check-lg' : 'bi-shield-check' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Admin Verified</div>
                        <div class="h-step-desc"><?= $complaint['verified_at'] ? formatDate($complaint['verified_at'], 'd M, h:i A') : ($is_verified ? 'Verified' : 'In Review') ?></div>
                    </div>
                </div>

                <!-- Step 3: Department Assigned -->
                <div class="h-step <?= $is_dept || $status !== 'PENDING' ? 'completed' : '' ?>">
                    <div class="h-marker">
                        <i class="bi <?= $is_dept ? 'bi-check-lg' : 'bi-building' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Dept Assigned</div>
                        <div class="h-step-desc"><?= $complaint['department_name'] ? sanitize($complaint['department_name']) : 'Queued' ?></div>
                    </div>
                </div>

                <!-- Step 4: In Progress -->
                <div class="h-step <?= $status === 'RESOLVED' ? 'completed' : ($status === 'IN PROGRESS' ? 'active' : '') ?>">
                    <div class="h-marker">
                        <i class="bi <?= $status === 'RESOLVED' ? 'bi-check-lg' : 'bi-gear-wide-connected' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Work In Progress</div>
                        <div class="h-step-desc"><?= $status === 'IN PROGRESS' ? 'Field Unit Working' : ($status === 'RESOLVED' ? 'Repaired' : 'Queued') ?></div>
                    </div>
                </div>

                <!-- Step 5: Resolved -->
                <div class="h-step <?= $status === 'RESOLVED' ? 'completed' : '' ?>">
                    <div class="h-marker">
                        <i class="bi <?= $status === 'RESOLVED' ? 'bi-check2-all' : 'bi-flag' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Resolved & Closed</div>
                        <div class="h-step-desc"><?= $complaint['resolved_at'] ? formatDate($complaint['resolved_at'], 'd M, h:i A') : 'Pending Resolution' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Left Column: Complaint Details -->
            <div class="col-lg-7">
                <div class="cirs-card p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-file-text text-primary me-2"></i>Grievance Details</h5>
                    
                    <h5 class="fw-bold text-dark mb-2"><?= sanitize($complaint['title']) ?></h5>
                    <p class="text-muted leading-relaxed mb-4"><?= nl2br(sanitize($complaint['description'])) ?></p>

                    <div class="row g-3 bg-light p-3 rounded-3 mb-3 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Designated Department:</span>
                            <strong class="text-dark"><?= sanitize($complaint['department_name'] ?? 'Not Assigned Yet') ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Location / Landmark:</span>
                            <strong class="text-dark"><?= sanitize($complaint['location']) ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Department Contact:</span>
                            <span class="text-dark"><?= sanitize($complaint['dept_phone'] ?? '1800-111-2000') ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Last Updated:</span>
                            <span class="text-dark"><?= timeAgo($complaint['updated_at']) ?></span>
                        </div>
                    </div>

                    <?php if (!empty($complaint['image'])): ?>
                        <div class="mt-3">
                            <span class="text-muted small fw-semibold d-block mb-2">Uploaded Photo Evidence:</span>
                            <a href="<?= UPLOAD_URL . sanitize($complaint['image']) ?>" target="_blank" class="d-inline-block">
                                <img src="<?= UPLOAD_URL . sanitize($complaint['image']) ?>" alt="Complaint Evidence" class="img-fluid rounded border shadow-sm" style="max-height: 220px;">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Location Map -->
            <div class="col-lg-5">
                <div class="cirs-card p-4 bg-white shadow-sm h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Reported Location Map</h5>
                        <div id="complaint_map_view" class="leaflet-map-view mb-3"></div>
                        <div class="small text-muted">
                            <i class="bi bi-pin-map text-danger me-1"></i> <strong>Coordinates:</strong> 
                            <?= $complaint['latitude'] ? sanitize($complaint['latitude']) . ', ' . sanitize($complaint['longitude']) : 'Location coordinates not specified' ?>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <a href="https://www.google.com/maps?q=<?= urlencode($complaint['latitude'] . ',' . $complaint['longitude']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i> View on Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Official Redressal Timeline / Updates Log -->
        <div class="cirs-card p-4 bg-white shadow-sm mb-4">
            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Official Redressal Progress Log</h5>

            <?php if (empty($updates)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-hourglass-split fs-2 d-block mb-2 text-warning"></i>
                    <p class="mb-0">Complaint is queued for administrative review. Updates from municipal staff will appear here.</p>
                </div>
            <?php else: ?>
                <div class="tracking-timeline">
                    <?php foreach ($updates as $up): ?>
                        <div class="timeline-step completed">
                            <div class="timeline-marker">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <?= getStatusBadge($up['status']) ?>
                                    <small class="text-muted"><?= formatDate($up['created_at']) ?></small>
                                </div>
                                <p class="mb-1 text-dark fw-semibold"><?= nl2br(sanitize($up['message'])) ?></p>
                                <small class="text-muted"><i class="bi bi-person-badge me-1"></i> Logged by: <?= sanitize($up['admin_name'] ?? 'Municipal Control Room') ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Initialize Leaflet Map View -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const lat = <?= $complaint['latitude'] ? (float)$complaint['latitude'] : 19.0760 ?>;
        const lng = <?= $complaint['longitude'] ? (float)$complaint['longitude'] : 72.8777 ?>;
        initLocationView('complaint_map_view', lat, lng, "<?= addslashes(sanitize($complaint['title'])) ?>");
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
