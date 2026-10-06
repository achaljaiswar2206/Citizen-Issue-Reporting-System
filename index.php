<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Home / Landing Page
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Home - Report Civic Issues";

// Fetch Live Statistics from Database
$stats = [
    'total' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'citizens' => 0
];

try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'IN PROGRESS' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved
        FROM complaints");
    $db_stats = $stmt->fetch();
    if ($db_stats) {
        $stats['total'] = (int)$db_stats['total'];
        $stats['in_progress'] = (int)$db_stats['in_progress'];
        $stats['resolved'] = (int)$db_stats['resolved'];
    }

    $stmt_users = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['citizens'] = (int)$stmt_users->fetch()['count'];
} catch (Exception $e) {
    // Fallback numbers if database is empty initially
}

// Fetch 3 Recent Resolved/Active Complaints for Live Feed
$recent_complaints = [];
try {
    $stmt_feed = $pdo->query("SELECT c.*, d.department_name 
        FROM complaints c 
        LEFT JOIN departments d ON c.department_id = d.id 
        ORDER BY c.created_at DESC LIMIT 3");
    $recent_complaints = $stmt_feed->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="hero-badge">
                        <i class="bi bi-shield-check"></i> Official Municipal Redressal Platform
                    </div>
                    <h1 class="hero-title">
                        Report. Track. <span class="text-info">Resolve.</span>
                    </h1>
                    <p class="hero-subtitle">
                        Help make your city cleaner, safer, and better. Report civic issues like potholes, waste dumps, broken streetlights, or water leakage directly to the concerned municipal departments.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-primary btn-lg shadow">
                            <i class="bi bi-plus-circle me-2"></i> Report an Issue
                        </a>
                        <a href="<?= BASE_URL ?>/track.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-search me-2"></i> Track Complaint
                        </a>
                    </div>

                    <!-- Quick Stats Pill Bar -->
                    <div class="row g-3 mt-4 pt-2">
                        <div class="col-sm-4 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                <div>
                                    <h4 class="mb-0 text-white fw-bold"><?= $stats['resolved'] ?>+</h4>
                                    <span class="text-secondary small">Issues Resolved</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-gear-wide-connected text-info fs-4"></i>
                                <div>
                                    <h4 class="mb-0 text-white fw-bold"><?= $stats['in_progress'] ?></h4>
                                    <span class="text-secondary small">Under Action</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people-fill text-warning fs-4"></i>
                                <div>
                                    <h4 class="mb-0 text-white fw-bold"><?= $stats['citizens'] ?>+</h4>
                                    <span class="text-secondary small">Active Citizens</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Interactive Quick Tracker Box -->
                <div class="col-lg-5">
                    <div class="cirs-card shadow-xl p-4 bg-white text-dark">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="bi bi-search fs-5"></i></span>
                            <div>
                                <h5 class="mb-0 fw-bold">Track Complaint Status</h5>
                                <small class="text-muted">Enter public reference ID to check live progress</small>
                            </div>
                        </div>

                        <form action="<?= BASE_URL ?>/track.php" method="GET">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Complaint Reference ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="id" class="form-control form-control-lg" placeholder="e.g. CIRS-2026-00001" required>
                                </div>
                                <div class="form-text small">Instant tracking without logging in.</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="bi bi-arrow-right-circle me-1"></i> Track Live Status
                            </button>
                        </form>

                        <hr class="my-3 text-muted">
                        <div class="d-flex justify-content-between align-items-center small text-muted">
                            <span>New citizen?</span>
                            <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-primary">Create an Account <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Flash Alert if any -->
    <div class="container mt-4">
        <?php renderFlash(); ?>
    </div>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5">
        <div class="container">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase fs-7">Simple & Transparent</span>
                <h2 class="mt-2 fw-bold display-6">How The Redressal System Works</h2>
                <p class="text-muted">A streamlined 6-step civic pipeline from citizen report to verified resolution.</p>
            </div>

            <div class="row g-4">
                <!-- Step 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="kpi-icon bg-primary-subtle text-primary">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Register / Log In</h5>
                        </div>
                        <p class="text-muted small mb-0">
                            Citizen creates a secure account with mobile/email verification to track complaints with full privacy.
                        </p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="kpi-icon bg-warning-subtle text-warning">
                                <i class="bi bi-camera-fill"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Report Issue</h5>
                        </div>
                        <p class="text-muted small mb-0">
                            Submit complaint details, category, location pin via OpenStreetMap, and upload geo-tagged photos.
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="kpi-icon bg-info-subtle text-info">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Admin Verification</h5>
                        </div>
                        <p class="text-muted small mb-0">
                            Municipal administrators inspect the issue, verify legitimacy, and set initial priority level.
                        </p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="kpi-icon bg-purple-subtle text-indigo" style="background:#ede9fe; color:#6366f1;">
                                <i class="bi bi-building-check"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Department Assignment</h5>
                        </div>
                        <p class="text-muted small mb-0">
                            The complaint is automatically routed to designated departments like Road, Sanitation, Water, or Electricity.
                        </p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="step-card">
                        <div class="step-number">5</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="kpi-icon bg-primary-subtle text-primary">
                                <i class="bi bi-tools"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Work in Progress</h5>
                        </div>
                        <p class="text-muted small mb-0">
                            Field engineers and ground units are dispatched to fix the problem while admin logs live progress remarks.
                        </p>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="col-lg-4 col-md-6">
                    <div class="step-card">
                        <div class="step-number">6</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="kpi-icon bg-success-subtle text-success">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Verified Resolution</h5>
                        </div>
                        <p class="text-muted small mb-0">
                            Issue marked resolved with closure logs. The citizen is notified and complaint is officially closed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Civic Issue Categories Section -->
    <section class="py-5 bg-white border-top border-bottom">
        <div class="container">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold text-uppercase fs-7">Categories</span>
                <h2 class="mt-2 fw-bold display-6">Civic Issues You Can Report</h2>
                <p class="text-muted">Select an issue category to file your grievance directly with the relevant municipal authority.</p>
            </div>

            <div class="row g-4">
                <?php foreach ($CATEGORIES as $cat_name => $cat_info): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="category-card shadow-sm">
                            <div class="category-icon-box" style="background-color: <?= $cat_info['color'] ?>15; color: <?= $cat_info['color'] ?>;">
                                <i class="bi <?= $cat_info['icon'] ?>"></i>
                            </div>
                            <h5 class="fw-bold mb-2"><?= sanitize($cat_name) ?></h5>
                            <p class="text-muted small flex-grow-1"><?= sanitize($cat_info['desc']) ?></p>
                            <a href="<?= BASE_URL ?>/citizen/file_complaint.php?cat=<?= urlencode($cat_name) ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                                <i class="bi bi-plus-lg me-1"></i> Report <?= sanitize($cat_name) ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Recent Grievance Activity Feed -->
    <?php if (!empty($recent_complaints)): ?>
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h3 class="fw-bold mb-1">Recent Civic Activity</h3>
                    <p class="text-muted small mb-0">Live stream of public complaints and municipal updates.</p>
                </div>
                <a href="<?= BASE_URL ?>/track.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Look Up Any Complaint
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($recent_complaints as $c): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="cirs-card h-100 p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light text-secondary border font-monospace fw-bold"><?= sanitize($c['complaint_id']) ?></span>
                                    <?= getStatusBadge($c['status']) ?>
                                </div>
                                <h6 class="fw-bold text-dark mt-2 mb-2"><?= sanitize($c['title']) ?></h6>
                                <p class="text-muted small mb-3"><?= sanitize(substr($c['description'], 0, 110)) ?>...</p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small">
                                <span class="text-muted"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= sanitize(substr($c['location'], 0, 25)) ?>...</span>
                                <a href="<?= BASE_URL ?>/track.php?id=<?= urlencode($c['complaint_id']) ?>" class="fw-bold text-primary">Track <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action Section -->
    <section class="py-5 text-white" style="background: linear-gradient(135deg, #1e40af 0%, #1e293b 100%);">
        <div class="container py-4 text-center">
            <h2 class="display-6 fw-bold text-white mb-3">Notice an Issue in Your Neighborhood?</h2>
            <p class="lead text-light max-w-700 mx-auto mb-4">
                Take a photo, pin the location, and let our dedicated municipal teams fix it. Together we build better cities.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="btn btn-light btn-lg text-primary fw-bold shadow">
                    <i class="bi bi-plus-circle-fill me-2 text-primary"></i> File a Complaint Now
                </a>
                <a href="<?= BASE_URL ?>/contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-telephone me-2"></i> Contact Authorities
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
