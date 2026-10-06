<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Public Complaint Tracking Portal
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Track Complaint Status";
$search_id = isset($_GET['id']) ? trim($_GET['id']) : '';
$complaint = null;
$updates = [];
$error_msg = '';

if (!empty($search_id)) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, d.department_name, d.contact_email as dept_email, d.phone as dept_phone, u.full_name as citizen_name 
            FROM complaints c 
            LEFT JOIN departments d ON c.department_id = d.id 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.complaint_id = ?");
        $stmt->execute([$search_id]);
        $complaint = $stmt->fetch();

        if ($complaint) {
            // Fetch progress updates
            $stmt_up = $pdo->prepare("SELECT u.*, a.name as admin_name 
                FROM complaint_updates u 
                LEFT JOIN admins a ON u.admin_id = a.id 
                WHERE u.complaint_id = ? 
                ORDER BY u.created_at ASC");
            $stmt_up->execute([$complaint['id']]);
            $updates = $stmt_up->fetchAll();
        } else {
            $error_msg = "No complaint found with ID '{$search_id}'. Please check the reference number and try again.";
        }
    } catch (Exception $e) {
        $error_msg = "An error occurred while fetching complaint data.";
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 text-center">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase fs-7">Real-Time Redressal</span>
                <h1 class="fw-bold mt-2 display-6">Track Civic Complaint</h1>
                <p class="text-muted">Enter your complaint reference ID to view live status, assigned department, and official progress remarks.</p>

                <!-- Search Form -->
                <form action="<?= BASE_URL ?>/track.php" method="GET" class="mt-4">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
                        <input type="text" name="id" class="form-control border-start-0 font-monospace" placeholder="Enter Complaint ID (e.g. CIRS-2026-00001)" value="<?= sanitize($search_id) ?>" required>
                        <button class="btn btn-primary px-4 fw-bold" type="submit">
                            Track Status
                        </button>
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-2">
                        <small class="text-muted">Try sample IDs:</small>
                        <a href="<?= BASE_URL ?>/track.php?id=CIRS-2026-00001" class="badge bg-light text-secondary border">CIRS-2026-00001</a>
                        <a href="<?= BASE_URL ?>/track.php?id=CIRS-2026-00002" class="badge bg-light text-secondary border">CIRS-2026-00002</a>
                        <a href="<?= BASE_URL ?>/track.php?id=CIRS-2026-00003" class="badge bg-light text-secondary border">CIRS-2026-00003</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-danger d-flex align-items-center shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Complaint Not Found!</strong><br>
                            <?= sanitize($error_msg) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($complaint): ?>
            <!-- COMPLAINT FOUND DETAILS -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Master Status Summary Card -->
                    <div class="cirs-card p-4 mb-4 shadow-sm">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-4 fw-bold font-monospace text-primary"><?= sanitize($complaint['complaint_id']) ?></span>
                                    <button class="btn btn-sm btn-light border py-0 px-2" onclick="copyToClipboard('<?= sanitize($complaint['complaint_id']) ?>', this)">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar3 me-1"></i> Submitted on <?= formatDate($complaint['created_at']) ?>
                                    <span class="mx-2">&bull;</span>
                                    <i class="bi bi-tag me-1"></i> <?= sanitize($complaint['category']) ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?= getPriorityBadge($complaint['priority']) ?>
                                <?= getStatusBadge($complaint['status']) ?>
                            </div>
                        </div>

                        <!-- Dynamic Visual Progress Timeline -->
                        <?php
                            $status = $complaint['status'];
                            $is_verified = (bool)$complaint['is_verified'];
                            $is_dept = !empty($complaint['department_id']);
                        ?>
                        <div class="horizontal-timeline">
                            <!-- Stage 1: Submitted -->
                            <div class="h-step completed">
                                <div class="h-marker"><i class="bi bi-check-lg"></i></div>
                                <div>
                                    <div class="h-step-title">Submitted</div>
                                    <div class="h-step-desc"><?= formatDate($complaint['created_at'], 'd M, h:i A') ?></div>
                                </div>
                            </div>

                            <!-- Stage 2: Verified -->
                            <div class="h-step <?= $is_verified || $status !== 'PENDING' ? 'completed' : 'active' ?>">
                                <div class="h-marker">
                                    <i class="bi <?= $is_verified || $status !== 'PENDING' ? 'bi-check-lg' : 'bi-shield-check' ?>"></i>
                                </div>
                                <div>
                                    <div class="h-step-title">Admin Verified</div>
                                    <div class="h-step-desc"><?= $complaint['verified_at'] ? formatDate($complaint['verified_at'], 'd M, h:i A') : ($is_verified ? 'Verified' : 'Review Pending') ?></div>
                                </div>
                            </div>

                            <!-- Stage 3: Department Assigned -->
                            <div class="h-step <?= $is_dept || $status !== 'PENDING' ? 'completed' : '' ?>">
                                <div class="h-marker">
                                    <i class="bi <?= $is_dept ? 'bi-check-lg' : 'bi-building' ?>"></i>
                                </div>
                                <div>
                                    <div class="h-step-title">Dept Assigned</div>
                                    <div class="h-step-desc"><?= $complaint['department_name'] ? sanitize($complaint['department_name']) : 'Awaiting Dept' ?></div>
                                </div>
                            </div>

                            <!-- Stage 4: In Progress -->
                            <div class="h-step <?= $status === 'RESOLVED' ? 'completed' : ($status === 'IN PROGRESS' ? 'active' : '') ?>">
                                <div class="h-marker">
                                    <i class="bi <?= $status === 'RESOLVED' ? 'bi-check-lg' : 'bi-gear-wide-connected' ?>"></i>
                                </div>
                                <div>
                                    <div class="h-step-title">Work In Progress</div>
                                    <div class="h-step-desc"><?= $status === 'IN PROGRESS' ? 'Field Unit Active' : ($status === 'RESOLVED' ? 'Work Done' : 'Queued') ?></div>
                                </div>
                            </div>

                            <!-- Stage 5: Resolved -->
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

                    <!-- Details & Map Split Grid -->
                    <div class="row g-4 mb-4">
                        <!-- Left Details Column -->
                        <div class="col-md-7">
                            <div class="cirs-card p-4 h-100 shadow-sm">
                                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i>Issue Summary</h5>
                                
                                <h6 class="fw-bold text-dark fs-5 mb-2"><?= sanitize($complaint['title']) ?></h6>
                                <p class="text-muted leading-relaxed mb-4"><?= nl2br(sanitize($complaint['description'])) ?></p>

                                <div class="row g-3 small bg-light p-3 rounded-3 mb-3">
                                    <div class="col-sm-6">
                                        <span class="text-muted d-block">Assigned Department:</span>
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
                                        <span class="text-muted d-block">Last Activity:</span>
                                        <span class="text-dark"><?= timeAgo($complaint['updated_at']) ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($complaint['image'])): ?>
                                    <div class="mt-3">
                                        <span class="text-muted small fw-semibold d-block mb-2">Citizen Uploaded Photo:</span>
                                        <a href="<?= UPLOAD_URL . sanitize($complaint['image']) ?>" target="_blank" class="d-inline-block position-relative">
                                            <img src="<?= UPLOAD_URL . sanitize($complaint['image']) ?>" alt="Complaint Evidence" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                                            <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 end-0 m-2"><i class="bi bi-arrows-fullscreen me-1"></i> Enlarge</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Location Map Column -->
                        <div class="col-md-5">
                            <div class="cirs-card p-4 h-100 shadow-sm d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Location Map</h5>
                                    <div id="complaint_map_view" class="leaflet-map-view mb-3"></div>
                                    <div class="small text-muted">
                                        <i class="bi bi-pin-map text-danger me-1"></i> <strong>Geo Coordinates:</strong> 
                                        <?= $complaint['latitude'] ? sanitize($complaint['latitude']) . ', ' . sanitize($complaint['longitude']) : 'Not pinned' ?>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-top">
                                    <a href="https://www.google.com/maps?q=<?= urlencode($complaint['latitude'] . ',' . $complaint['longitude']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open in Google Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Updates & Official Activity Log -->
                    <div class="cirs-card p-4 shadow-sm mb-4">
                        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Official Redressal Timeline</h5>

                        <?php if (empty($updates)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-hourglass-split fs-2 d-block mb-2 text-warning"></i>
                                <p class="mb-0">Complaint is queued for administrative review. Updates will appear here as work progresses.</p>
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
            </div>

            <!-- Leaflet Map Init Script -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const lat = <?= $complaint['latitude'] ? (float)$complaint['latitude'] : 19.0760 ?>;
                    const lng = <?= $complaint['longitude'] ? (float)$complaint['longitude'] : 72.8777 ?>;
                    initLocationView('complaint_map_view', lat, lng, "<?= addslashes(sanitize($complaint['title'])) ?>");
                });
            </script>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
