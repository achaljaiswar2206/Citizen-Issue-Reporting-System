<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Contact & Helplines Page
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Contact Civic Helpline & Support";
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('danger', 'Security validation token expired. Please try again.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            setFlash('danger', 'Please fill in all inquiry fields.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Please provide a valid email address.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'NEW')");
                $stmt->execute([$name, $email, $subject, $message]);
                $inquiry_id = $pdo->lastInsertId();

                $sent = true;
                setFlash('success', "Thank you, {$name}! Your inquiry (#INQ-{$inquiry_id}) has been recorded. Our municipal support desk will review and follow up with you shortly.");
            } catch (Exception $e) {
                setFlash('danger', 'Error submitting inquiry: ' . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-5">
    <div class="container">
        <!-- Header -->
        <div class="row justify-content-center mb-5 text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase fs-7">Help & Support</span>
                <h1 class="fw-bold mt-2 display-5">Contact Civic Authorities</h1>
                <p class="text-muted lead">Have questions or require emergency municipal assistance? Reach out to our 24/7 civic helpdesk.</p>
            </div>
        </div>

        <?php renderFlash(); ?>

        <div class="row g-4 mb-5">
            <!-- Left Contact Info Cards -->
            <div class="col-lg-5">
                <div class="d-flex flex-column gap-3">
                    <div class="cirs-card p-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-primary-subtle text-primary">
                                <i class="bi bi-headset"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Central Control Room</h6>
                                <p class="text-muted small mb-0">Toll-Free 24/7 Helpline: <strong>1800-111-2000</strong></p>
                            </div>
                        </div>
                    </div>

                    <div class="cirs-card p-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-success-subtle text-success">
                                <i class="bi bi-envelope-check"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Official Grievance Email</h6>
                                <p class="text-muted small mb-0">support@cirs.gov / grievances@cirs.gov</p>
                            </div>
                        </div>
                    </div>

                    <div class="cirs-card p-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-danger-subtle text-danger">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Municipal Headquarters</h6>
                                <p class="text-muted small mb-0">Civic Centre, Municipal Corporation Avenue, City Main Area</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Query Form -->
            <div class="col-lg-7">
                <div class="cirs-card p-4 p-md-5 shadow-sm bg-white">
                    <h4 class="fw-bold mb-3">Send General Inquiry</h4>
                    <p class="text-muted small mb-4">Note: To report specific civic issues like potholes or garbage, please use the <a href="<?= BASE_URL ?>/citizen/file_complaint.php" class="fw-bold">Report Issue Form</a>.</p>

                    <form action="<?= BASE_URL ?>/contact.php" method="POST">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Your Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Full name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="Subject of your inquiry" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Message / Query</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="How can we assist you?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-send me-1"></i> Submit Inquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
