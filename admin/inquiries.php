<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Admin Citizen Inquiries & Helpdesk Messages View
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$admin = getCurrentAdmin($pdo);
$page_title = "Citizen Contact Inquiries";

// Handle POST actions (Send Reply Email, Status update & Delete inquiry)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('danger', 'Security validation token expired. Please try again.');
        header("Location: " . BASE_URL . "/admin/inquiries.php");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $inquiry_id = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;

    // Action 1: Send Direct Email Reply via Gmail SMTP
    if ($action === 'send_reply') {
        $reply_message = trim($_POST['reply_message'] ?? '');
        $reply_subject = trim($_POST['reply_subject'] ?? 'Civic Inquiry Response');

        if (empty($reply_message)) {
            setFlash('danger', 'Please enter a reply message.');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM contact_inquiries WHERE id = ?");
                $stmt->execute([$inquiry_id]);
                $inq = $stmt->fetch();

                if (!$inq) {
                    setFlash('danger', 'Inquiry record not found.');
                } else {
                    // Send Email using direct Gmail SMTP (achaljaiswar198@gmail.com)
                    $mail_res = sendInquiryReplyEmail(
                        $inq['email'], 
                        $inq['name'], 
                        $inq['subject'], 
                        $inq['message'], 
                        $reply_message, 
                        $admin['name'] ?? 'Municipal Commissioner'
                    );

                    // Update status to REPLIED
                    $stmt_up = $pdo->prepare("UPDATE contact_inquiries SET status = 'REPLIED' WHERE id = ?");
                    $stmt_up->execute([$inquiry_id]);

                    setFlash('success', "Official reply successfully dispatched to {$inq['name']} ({$inq['email']}) via " . SMTP_USER . "!");
                }
            } catch (Exception $e) {
                setFlash('danger', 'Error sending reply email: ' . $e->getMessage());
            }
        }
        header("Location: " . BASE_URL . "/admin/inquiries.php");
        exit;
    }

    // Action 2: Update Inquiry Status
    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? 'READ';
        if (in_array($new_status, ['NEW', 'READ', 'REPLIED'])) {
            try {
                $stmt = $pdo->prepare("UPDATE contact_inquiries SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $inquiry_id]);
                setFlash('success', "Inquiry #INQ-{$inquiry_id} status updated to {$new_status}.");
            } catch (Exception $e) {
                setFlash('danger', 'Error updating status: ' . $e->getMessage());
            }
        }
        header("Location: " . BASE_URL . "/admin/inquiries.php");
        exit;
    }

    // Action 3: Delete Inquiry
    if ($action === 'delete_inquiry') {
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id = ?");
            $stmt->execute([$inquiry_id]);
            setFlash('success', "Inquiry #INQ-{$inquiry_id} deleted successfully.");
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting inquiry: ' . $e->getMessage());
        }
        header("Location: " . BASE_URL . "/admin/inquiries.php");
        exit;
    }
}

// 1. Fetch KPI Statistics
$stats = ['total' => 0, 'new' => 0, 'read' => 0, 'replied' => 0];
try {
    $stmt_stat = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'NEW' THEN 1 ELSE 0 END) as `new`,
        SUM(CASE WHEN status = 'READ' THEN 1 ELSE 0 END) as `read`,
        SUM(CASE WHEN status = 'REPLIED' THEN 1 ELSE 0 END) as replied
        FROM contact_inquiries");
    $db_stat = $stmt_stat->fetch();
    if ($db_stat) {
        $stats = array_map('intval', $db_stat);
    }
} catch (Exception $e) {}

// 2. Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'ALL';

$sql = "SELECT * FROM contact_inquiries WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter !== 'ALL' && in_array($status_filter, ['NEW', 'READ', 'REPLIED'])) {
    $sql .= " AND status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();
} catch (Exception $e) {
    $inquiries = [];
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
                        <li class="breadcrumb-item active" aria-current="page">Citizen Inquiries</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-chat-left-text-fill text-primary me-2"></i>Citizen Helpdesk & Inquiries</h3>
                <small class="text-muted">Review, manage, and respond to general citizen inquiries submitted via the Contact Us portal.</small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/inquiries.php" class="btn btn-light border btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </a>
            </div>
        </div>

        <?php renderFlash(); ?>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary-subtle text-primary" style="width:46px; height:46px; font-size:1.3rem;">
                        <i class="bi bi-inbox-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Inquiries</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $stats['total'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-warning-subtle text-warning" style="width:46px; height:46px; font-size:1.3rem;">
                        <i class="bi bi-envelope-exclamation-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">New / Unread</div>
                        <h4 class="fw-bold mb-0 text-warning"><?= $stats['new'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info-subtle text-info" style="width:46px; height:46px; font-size:1.3rem;">
                        <i class="bi bi-envelope-open-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Reviewed / Read</div>
                        <h4 class="fw-bold mb-0 text-info"><?= $stats['read'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="cirs-card p-3 bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success-subtle text-success" style="width:46px; height:46px; font-size:1.3rem;">
                        <i class="bi bi-reply-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Replied</div>
                        <h4 class="fw-bold mb-0 text-success"><?= $stats['replied'] ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Toolbar -->
        <div class="cirs-card p-3 mb-4 bg-white shadow-sm">
            <form action="<?= BASE_URL ?>/admin/inquiries.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by citizen name, email, subject, keyword..." value="<?= sanitize($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>All Inquiry Statuses</option>
                        <option value="NEW" <?= $status_filter === 'NEW' ? 'selected' : '' ?>>New / Unread</option>
                        <option value="READ" <?= $status_filter === 'READ' ? 'selected' : '' ?>>Reviewed / Read</option>
                        <option value="REPLIED" <?= $status_filter === 'REPLIED' ? 'selected' : '' ?>>Replied</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-4">Search</button>
                    <?php if (!empty($search) || $status_filter !== 'ALL'): ?>
                        <a href="<?= BASE_URL ?>/admin/inquiries.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Inquiries Table -->
        <div class="cirs-card shadow-sm bg-white">
            <div class="cirs-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">Inquiry Messages</h5>
                    <span class="badge bg-primary rounded-pill"><?= count($inquiries) ?> Total</span>
                </div>
                <small class="text-muted"><i class="bi bi-shield-check me-1"></i> Public Helpdesk Logs</small>
            </div>
            <div class="cirs-card-body p-0">
                <?php if (empty($inquiries)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-left-dots fs-1 d-block mb-2"></i>
                        <p class="mb-0">No citizen inquiries found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Inquiry ID</th>
                                    <th>Citizen Name</th>
                                    <th>Email Address</th>
                                    <th>Subject</th>
                                    <th>Message Snippet</th>
                                    <th>Status</th>
                                    <th>Submitted On</th>
                                    <th class="text-end pe-4" style="min-width: 170px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td class="ps-4 font-monospace fw-bold text-primary">#INQ-<?= $inq['id'] ?></td>
                                        <td><strong><?= sanitize($inq['name']) ?></strong></td>
                                        <td><a href="mailto:<?= sanitize($inq['email']) ?>" class="text-dark"><?= sanitize($inq['email']) ?></a></td>
                                        <td class="text-dark fw-semibold"><?= sanitize($inq['subject']) ?></td>
                                        <td class="small text-muted" style="max-width: 240px;">
                                            <?= htmlspecialchars(mb_strimwidth($inq['message'], 0, 65, '...')) ?>
                                        </td>
                                        <td>
                                            <?php if ($inq['status'] === 'NEW'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-dot"></i>New</span>
                                            <?php elseif ($inq['status'] === 'READ'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-check2"></i>Read</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-all"></i>Replied</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= formatDate($inq['created_at'], 'd M Y, h:i A') ?></td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openInquiryModal(<?= htmlspecialchars(json_encode($inq)) ?>)">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="openReplyModal(<?= htmlspecialchars(json_encode($inq)) ?>)" title="Send Reply Email">
                                                    <i class="bi bi-reply-fill me-1"></i> Reply
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDeleteInquiry(<?= $inq['id'] ?>, '<?= htmlspecialchars(addslashes($inq['name'])) ?>')" title="Delete Inquiry">
                                                    <i class="bi bi-trash3"></i>
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

<!-- MODAL 1: View Inquiry Details Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="inquiryModalLabel">
                    <i class="bi bi-chat-left-text-fill me-2"></i> Inquiry Details <span id="modal_inq_id" class="font-monospace"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3 bg-light p-3 rounded-3 border">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Citizen Name:</span>
                        <strong class="text-dark" id="modal_inq_name">-</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Email Address:</span>
                        <strong class="text-dark" id="modal_inq_email">-</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Date Received:</span>
                        <span class="text-dark" id="modal_inq_date">-</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Current Status:</span>
                        <span id="modal_inq_status_badge">-</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Subject:</label>
                    <h5 class="fw-bold text-dark" id="modal_inq_subject">-</h5>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Full Message / Query:</label>
                    <div class="p-3 bg-white border rounded-3 text-dark leading-relaxed" id="modal_inq_message" style="white-space: pre-line; min-height: 120px;">
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pt-3 border-top">
                    <form action="<?= BASE_URL ?>/admin/inquiries.php" method="POST" class="d-flex align-items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="inquiry_id" id="modal_form_inq_id" value="">
                        <label class="form-label small fw-bold text-muted mb-0">Status:</label>
                        <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="NEW" id="opt_new">Mark as New</option>
                            <option value="READ" id="opt_read">Mark as Read</option>
                            <option value="REPLIED" id="opt_replied">Mark as Replied</option>
                        </select>
                    </form>

                    <button type="button" id="modal_trigger_reply_btn" class="btn btn-success btn-sm">
                        <i class="bi bi-reply-fill me-1"></i> Send Reply Email
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: Direct Email Reply Composer Modal -->
<div class="modal fade" id="replyInquiryModal" tabindex="-1" aria-labelledby="replyInquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="replyInquiryModalLabel">
                    <i class="bi bi-envelope-paper-fill me-2"></i> Send Official Reply via CIRS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/inquiries.php" method="POST" id="reply_email_form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send_reply">
                <input type="hidden" name="inquiry_id" id="reply_inquiry_id" value="">

                <div class="modal-body p-4">
                    <div class="alert alert-success-subtle border border-success-subtle p-3 rounded-3 small mb-3">
                        <div><i class="bi bi-person text-dark me-1"></i> <strong>To : </strong> <span id="reply_to_name">-</span> &lt;<span id="reply_to_email" class="font-monospace">-</span>&gt;</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" name="reply_subject" id="reply_subject_input" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Official Response Message <span class="text-danger">*</span></label>
                        <textarea name="reply_message" id="reply_message_input" class="form-control" rows="6" placeholder="Type official response here..." required></textarea>
                        <div class="form-text small">This response will be formatted into an official CIRS Municipal Helpdesk email and delivered directly to the citizen's inbox.</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted fw-bold d-block mb-1">Citizen's Original Inquiry:</small>
                        <div class="small text-dark fst-italic" id="reply_original_msg_preview" style="max-height: 80px; overflow-y: auto;">-</div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4" id="reply_submit_btn">
                        <i class="bi bi-send-fill me-1"></i> Send Reply Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: Delete Inquiry Modal -->
<div class="modal fade" id="deleteInquiryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-trash3-fill me-2"></i> Delete Inquiry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/inquiries.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_inquiry">
                <input type="hidden" name="inquiry_id" id="del_inq_id" value="">
                <div class="modal-body p-4">
                    <p class="mb-0 text-dark">Are you sure you want to permanently delete the inquiry from <strong id="del_inq_name"></strong>?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash3 me-1"></i> Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentInquiryObj = null;

function openInquiryModal(inq) {
    currentInquiryObj = inq;
    document.getElementById('modal_inq_id').innerText = '#INQ-' + inq.id;
    document.getElementById('modal_form_inq_id').value = inq.id;
    document.getElementById('modal_inq_name').innerText = inq.name;
    document.getElementById('modal_inq_email').innerText = inq.email;
    document.getElementById('modal_inq_date').innerText = inq.created_at;
    document.getElementById('modal_inq_subject').innerText = inq.subject;
    document.getElementById('modal_inq_message').innerText = inq.message;
    
    // Status badge
    let badgeHtml = '<span class="badge bg-warning text-dark">New</span>';
    if (inq.status === 'READ') badgeHtml = '<span class="badge bg-info text-dark">Read</span>';
    if (inq.status === 'REPLIED') badgeHtml = '<span class="badge bg-success">Replied</span>';
    document.getElementById('modal_inq_status_badge').innerHTML = badgeHtml;

    // Set dropdown selected
    if (inq.status === 'NEW') document.getElementById('opt_new').selected = true;
    if (inq.status === 'READ') document.getElementById('opt_read').selected = true;
    if (inq.status === 'REPLIED') document.getElementById('opt_replied').selected = true;

    // Trigger reply composer from details view
    document.getElementById('modal_trigger_reply_btn').onclick = function() {
        var viewModalEl = document.getElementById('inquiryModal');
        var viewModal = bootstrap.Modal.getInstance(viewModalEl);
        if (viewModal) viewModal.hide();
        openReplyModal(inq);
    };

    var modal = new bootstrap.Modal(document.getElementById('inquiryModal'));
    modal.show();
}

function openReplyModal(inq) {
    document.getElementById('reply_inquiry_id').value = inq.id;
    document.getElementById('reply_to_name').innerText = inq.name;
    document.getElementById('reply_to_email').innerText = inq.email;
    document.getElementById('reply_subject_input').value = 'Re: [CIRS Helpdesk] ' + inq.subject;
    document.getElementById('reply_original_msg_preview').innerText = inq.message;
    
    // Prepopulate a polite municipal greeting
    document.getElementById('reply_message_input').value = "Dear " + inq.name + ",\n\nThank you for reaching out to the Citizen Issue Reporting System (CIRS) regarding '" + inq.subject + "'.\n\n";

    var modal = new bootstrap.Modal(document.getElementById('replyInquiryModal'));
    modal.show();
}

function confirmDeleteInquiry(id, name) {
    document.getElementById('del_inq_id').value = id;
    document.getElementById('del_inq_name').innerText = name;
    var modal = new bootstrap.Modal(document.getElementById('deleteInquiryModal'));
    modal.show();
}

// Loading state on reply submit
document.getElementById('reply_email_form').addEventListener('submit', function() {
    var btn = document.getElementById('reply_submit_btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending via SMTP...';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
