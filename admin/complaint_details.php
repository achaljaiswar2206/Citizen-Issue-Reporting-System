<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Complaint Processing & Progress Monitoring View
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$admin = getCurrentAdmin($pdo);
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$page_title = "Process Complaint";

try {
    // 1. Fetch Complaint with Citizen and Department info
    $stmt = $pdo->prepare("SELECT c.*, u.full_name as citizen_name, u.email as citizen_email, u.mobile as citizen_mobile, u.address as citizen_address, d.department_name 
        FROM complaints c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN departments d ON c.department_id = d.id 
        WHERE c.id = ?");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        setFlash('danger', 'Complaint record not found.');
        header("Location: " . BASE_URL . "/admin/complaints.php");
        exit;
    }

    // 2. Fetch All Predefined Departments for Assignment Dropdown
    $stmt_depts = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC");
    $departments = $stmt_depts->fetchAll();

    // 3. Auto-suggested department based on category
    $suggested_dept_id = suggestDepartmentId($complaint['category'], $pdo);

    // 4. Fetch Progress Updates History
    $stmt_up = $pdo->prepare("SELECT u.*, a.name as admin_name 
        FROM complaint_updates u 
        LEFT JOIN admins a ON u.admin_id = a.id 
        WHERE u.complaint_id = ? 
        ORDER BY u.created_at ASC");
    $stmt_up->execute([$complaint['id']]);
    $updates = $stmt_up->fetchAll();

} catch (Exception $e) {
    setFlash('danger', 'Database error fetching complaint details: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/admin/complaints.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container-fluid px-lg-5">
        <!-- Breadcrumb Navigation -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-1">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/complaints.php">Complaints Registry</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= sanitize($complaint['complaint_id']) ?></li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders text-primary me-2"></i>Process & Monitor Complaint</h3>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/track.php?id=<?= urlencode($complaint['complaint_id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Public Tracking View
                </a>
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-light border btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Registry
                </a>
            </div>
        </div>

        <?php renderFlash(); ?>

        <!-- Master Status Header Card -->
        <div class="cirs-card p-4 mb-4 bg-white shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4 fw-bold font-monospace text-primary"><?= sanitize($complaint['complaint_id']) ?></span>
                        <button class="btn btn-sm btn-light border py-0 px-2" onclick="copyToClipboard('<?= sanitize($complaint['complaint_id']) ?>', this)">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                        <?php if ($complaint['is_verified']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>Unverified</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-calendar3 me-1"></i> Submitted: <?= formatDate($complaint['created_at']) ?>
                        <span class="mx-2">&bull;</span>
                        <i class="bi bi-person me-1"></i> Citizen: <strong><?= sanitize($complaint['citizen_name']) ?></strong> (<?= sanitize($complaint['citizen_mobile']) ?>)
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
                        <div class="h-step-desc"><?= $complaint['verified_at'] ? formatDate($complaint['verified_at'], 'd M, h:i A') : ($is_verified ? 'Verified' : 'Pending Verification') ?></div>
                    </div>
                </div>

                <!-- Step 3: Department Assigned -->
                <div class="h-step <?= $is_dept || $status !== 'PENDING' ? 'completed' : '' ?>">
                    <div class="h-marker">
                        <i class="bi <?= $is_dept ? 'bi-check-lg' : 'bi-building' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Dept Assigned</div>
                        <div class="h-step-desc"><?= $complaint['department_name'] ? sanitize($complaint['department_name']) : 'Awaiting Dept' ?></div>
                    </div>
                </div>

                <!-- Step 4: In Progress -->
                <div class="h-step <?= $status === 'RESOLVED' ? 'completed' : ($status === 'IN PROGRESS' ? 'active' : '') ?>">
                    <div class="h-marker">
                        <i class="bi <?= $status === 'RESOLVED' ? 'bi-check-lg' : 'bi-gear-wide-connected' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Work In Progress</div>
                        <div class="h-step-desc"><?= $status === 'IN PROGRESS' ? 'Field Team Active' : ($status === 'RESOLVED' ? 'Completed' : 'Queued') ?></div>
                    </div>
                </div>

                <!-- Step 5: Resolved -->
                <div class="h-step <?= $status === 'RESOLVED' ? 'completed' : '' ?>">
                    <div class="h-marker">
                        <i class="bi <?= $status === 'RESOLVED' ? 'bi-check2-all' : 'bi-flag' ?>"></i>
                    </div>
                    <div>
                        <div class="h-step-title">Resolved & Closed</div>
                        <div class="h-step-desc"><?= $complaint['resolved_at'] ? formatDate($complaint['resolved_at'], 'd M, h:i A') : 'Open Ticket' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left 7 Columns: Complaint Information & Maps -->
            <div class="col-lg-7">
                <!-- Grievance Details Card -->
                <div class="cirs-card p-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-file-text-fill text-primary me-2"></i>Reported Grievance Information</h5>
                    
                    <div class="mb-3">
                        <span class="badge bg-light text-dark border me-2"><i class="bi <?= $CATEGORIES[$complaint['category']]['icon'] ?? 'bi-tag' ?> me-1"></i><?= sanitize($complaint['category']) ?></span>
                        <h5 class="fw-bold text-dark d-inline"><?= sanitize($complaint['title']) ?></h5>
                    </div>

                    <p class="text-muted leading-relaxed mb-4"><?= nl2br(sanitize($complaint['description'])) ?></p>

                    <div class="row g-3 bg-light p-3 rounded-3 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Citizen Full Name:</span>
                            <strong class="text-dark"><?= sanitize($complaint['citizen_name']) ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Contact Phone:</span>
                            <strong class="text-dark"><?= sanitize($complaint['citizen_mobile']) ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Email Address:</span>
                            <span class="text-dark"><?= sanitize($complaint['citizen_email']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Incident Location / Landmark:</span>
                            <strong class="text-dark"><?= sanitize($complaint['location']) ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($complaint['image'])): ?>
                        <div class="mt-3 pt-3 border-top">
                            <span class="text-muted small fw-semibold d-block mb-2">Citizen Uploaded Photo Evidence:</span>
                            <a href="<?= UPLOAD_URL . sanitize($complaint['image']) ?>" target="_blank">
                                <img src="<?= UPLOAD_URL . sanitize($complaint['image']) ?>" alt="Evidence" class="img-fluid rounded border shadow-sm" style="max-height: 250px;">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- OpenStreetMap Location Card -->
                <div class="cirs-card p-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Geographic Incident Location</h5>
                    <div id="admin_map_view" class="leaflet-map-view mb-3"></div>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-pin-map text-danger me-1"></i> <strong>GPS Coordinates:</strong> <?= $complaint['latitude'] ? sanitize($complaint['latitude']) . ', ' . sanitize($complaint['longitude']) : 'Not pinned' ?></span>
                        <a href="https://www.google.com/maps?q=<?= urlencode($complaint['latitude'] . ',' . $complaint['longitude']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Google Maps
                        </a>
                    </div>
                </div>

                <!-- Activity Progress Log -->
                <div class="cirs-card p-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Official Redressal Progress Log</h5>

                    <?php if (empty($updates)): ?>
                        <p class="text-muted small mb-0">No progress updates logged yet. Use the Action Panel to add the first update.</p>
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

            <!-- Right 5 Columns: Administrative Control Action Panel -->
            <div class="col-lg-5">
                <div class="cirs-card p-4 bg-white shadow-sm sticky-top" style="top: 80px; z-index: 10;">
                    <div class="d-flex align-items-center gap-2 pb-3 mb-3 border-bottom">
                        <div class="kpi-icon bg-primary-subtle text-primary" style="width:40px; height:40px; font-size:1.2rem;">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Administrative Action Panel</h5>
                    </div>

                    <form action="<?= BASE_URL ?>/admin/update_complaint.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">

                        <!-- 1. Verification Checkbox -->
                        <div class="mb-3 p-3 bg-light rounded-3 border">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="is_verified" <?= $complaint['is_verified'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-dark" for="is_verified">
                                    Mark Complaint as Verified
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">Verifies complaint authenticity before dispatching field workforce.</small>
                        </div>

                        <!-- 2. Predefined Department Assignment -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Assign Municipal Department <span class="text-danger">*</span></label>
                                <?php if ($suggested_dept_id): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle small" id="dept_suggestion_badge">
                                        <i class="bi bi-magic me-1"></i>Auto-Suggested
                                    </span>
                                <?php endif; ?>
                            </div>
                            <select name="department_id" id="department_id" class="form-select" required>
                                <option value="">-- Select Available Department --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($complaint['department_id'] == $d['id'] || (empty($complaint['department_id']) && $suggested_dept_id == $d['id'])) ? 'selected' : '' ?>>
                                        <?= sanitize($d['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Select from predefined municipal departments already available in the system.</div>
                        </div>

                        <!-- 3. Priority Assignment -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Assign Priority Level</label>
                            <select name="priority" class="form-select" required>
                                <option value="Low" <?= ($complaint['priority'] === 'Low') ? 'selected' : '' ?>>Low Priority</option>
                                <option value="Medium" <?= ($complaint['priority'] === 'Medium') ? 'selected' : '' ?>>Medium Priority</option>
                                <option value="High" <?= ($complaint['priority'] === 'High') ? 'selected' : '' ?>>High Priority</option>
                                <option value="Emergency" <?= ($complaint['priority'] === 'Emergency') ? 'selected' : '' ?>>Emergency (Immediate)</option>
                            </select>
                        </div>

                        <!-- 4. Complaint Status -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Update Redressal Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select fw-bold" required>
                                <option value="PENDING" <?= ($complaint['status'] === 'PENDING') ? 'selected' : '' ?>>PENDING (Awaiting Action)</option>
                                <option value="IN PROGRESS" <?= ($complaint['status'] === 'IN PROGRESS') ? 'selected' : '' ?>>IN PROGRESS (Department Working)</option>
                                <option value="RESOLVED" <?= ($complaint['status'] === 'RESOLVED') ? 'selected' : '' ?>>RESOLVED (Problem Fixed & Closed)</option>
                            </select>
                        </div>

                        <!-- 5. Progress Remarks Message -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-dark">Progress Update / Official Remarks</label>
                            <textarea name="progress_message" class="form-control" rows="3" placeholder="Enter progress log (e.g. Field crew dispatched, road asphalt patching in progress, streetlight driver unit replaced)..."></textarea>
                            <div class="form-text small">This message will be visible to the citizen on their tracking timeline.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow">
                            <i class="bi bi-check2-circle me-1"></i> Save & Update Complaint
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Initialize Leaflet Map View -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const lat = <?= $complaint['latitude'] ? (float)$complaint['latitude'] : 19.0760 ?>;
        const lng = <?= $complaint['longitude'] ? (float)$complaint['longitude'] : 72.8777 ?>;
        initLocationView('admin_map_view', lat, lng, "<?= addslashes(sanitize($complaint['title'])) ?>");
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
