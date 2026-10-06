<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Backend Admin Complaint Action Handler
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$admin = getCurrentAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/complaints.php");
    exit;
}

if (!validate_csrf()) {
    setFlash('danger', 'Security validation token expired. Please try again.');
    header("Location: " . BASE_URL . "/admin/complaints.php");
    exit;
}

$complaint_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
$department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$priority = $_POST['priority'] ?? 'Medium';
$status = strtoupper($_POST['status'] ?? 'PENDING');
$is_verified = isset($_POST['is_verified']) ? 1 : 0;
$progress_message = trim($_POST['progress_message'] ?? '');

try {
    // Fetch current complaint state with citizen and department details
    $stmt = $pdo->prepare("SELECT c.*, u.full_name as citizen_name, u.email as citizen_email 
                           FROM complaints c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           WHERE c.id = ?");
    $stmt->execute([$complaint_id]);
    $current = $stmt->fetch();

    if (!$current) {
        setFlash('danger', 'Complaint not found.');
        header("Location: " . BASE_URL . "/admin/complaints.php");
        exit;
    }

    // Timestamps logic
    $verified_at = $current['verified_at'];
    if ($is_verified && empty($verified_at)) {
        $verified_at = date('Y-m-d H:i:s');
    } elseif (!$is_verified) {
        $verified_at = null;
    }

    $assigned_at = $current['assigned_at'];
    if ($department_id && empty($assigned_at)) {
        $assigned_at = date('Y-m-d H:i:s');
    }

    $resolved_at = $current['resolved_at'];
    if ($status === 'RESOLVED' && empty($resolved_at)) {
        $resolved_at = date('Y-m-d H:i:s');
    } elseif ($status !== 'RESOLVED') {
        $resolved_at = null;
    }

    // Update Complaint record
    $update_sql = "UPDATE complaints SET 
        department_id = ?, 
        priority = ?, 
        status = ?, 
        is_verified = ?, 
        verified_at = ?, 
        assigned_at = ?, 
        resolved_at = ? 
        WHERE id = ?";

    $stmt_update = $pdo->prepare($update_sql);
    $stmt_update->execute([
        $department_id,
        $priority,
        $status,
        $is_verified,
        $verified_at,
        $assigned_at,
        $resolved_at,
        $complaint_id
    ]);

    // Fetch department name for notifications
    $dept_name = 'Unassigned';
    if ($department_id) {
        $stmt_dept = $pdo->prepare("SELECT department_name FROM departments WHERE id = ?");
        $stmt_dept->execute([$department_id]);
        $dept_row = $stmt_dept->fetch();
        if ($dept_row) {
            $dept_name = $dept_row['department_name'];
        }
    }

    // Construct Progress Log message if not entered by admin
    if (empty($progress_message)) {
        if ($status !== $current['status']) {
            if ($status === 'RESOLVED') {
                $progress_message = "Complaint verified and marked as RESOLVED by Municipal Administration. Work has been completed.";
            } elseif ($status === 'IN PROGRESS') {
                $progress_message = "Complaint verified and assigned to {$dept_name}. Redressal work is actively in progress.";
            } else {
                $progress_message = "Status updated to PENDING by administrator.";
            }
        } elseif ($department_id !== $current['department_id']) {
            $progress_message = "Complaint assigned to {$dept_name} by administrator.";
        } elseif ($is_verified && !$current['is_verified']) {
            $progress_message = "Complaint authenticity has been officially verified by Municipal Control Room.";
        }
    }

    // Insert into complaint_updates if there is a message
    if (!empty($progress_message)) {
        $stmt_log = $pdo->prepare("INSERT INTO complaint_updates (complaint_id, admin_id, status, message) VALUES (?, ?, ?, ?)");
        $stmt_log->execute([$complaint_id, $admin['id'], $status, $progress_message]);
    }

    // AUTOMATED CITIZEN EMAIL DISPATCH LOGIC
    $email_sent_note = '';
    if (!empty($current['citizen_email'])) {
        $citizen_email = $current['citizen_email'];
        $citizen_name = $current['citizen_name'] ?? 'Citizen';
        $updated_complaint = array_merge($current, [
            'department_id' => $department_id,
            'priority' => $priority,
            'status' => $status,
            'is_verified' => $is_verified
        ]);

        // 1. Complaint Just Verified
        if ($is_verified && !$current['is_verified']) {
            sendComplaintVerifiedEmail($citizen_email, $citizen_name, $updated_complaint, $dept_name, $status, $progress_message);
            $email_sent_note = " and Verification Notice email sent to citizen ({$citizen_email})";
        }
        // 2. Complaint Marked as Resolved & Closed
        elseif ($status === 'RESOLVED' && $current['status'] !== 'RESOLVED') {
            sendComplaintResolvedEmail($citizen_email, $citizen_name, $updated_complaint, $dept_name, $progress_message);
            $email_sent_note = " and 'Resolved & Closed' email sent to citizen ({$citizen_email})";
        }
        // 3. Complaint Moved to In Progress or Status Progress Update
        elseif ($status === 'IN PROGRESS' && ($current['status'] !== 'IN PROGRESS' || !empty($progress_message))) {
            sendComplaintInProgressEmail($citizen_email, $citizen_name, $updated_complaint, $dept_name, $progress_message);
            $email_sent_note = " and 'Work In Progress' email update sent to citizen ({$citizen_email})";
        }
    }

    setFlash('success', "Complaint {$current['complaint_id']} updated successfully{$email_sent_note}!");
    header("Location: " . BASE_URL . "/admin/complaint_details.php?id=" . $complaint_id);
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Error updating complaint: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/admin/complaint_details.php?id=" . $complaint_id);
    exit;
}

