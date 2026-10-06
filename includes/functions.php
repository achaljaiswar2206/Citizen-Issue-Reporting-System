<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Core Helper Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize string for output (XSS protection)
 */
function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token and store in session
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render Hidden CSRF Input Field
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate CSRF Token
 */
function validate_csrf($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set Flash Message in Session
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and Clear Flash Message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render Flash Alert HTML
 */
function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $icon = 'bi-info-circle-fill';
        if ($type === 'success') $icon = 'bi-check-circle-fill';
        if ($type === 'danger') $icon = 'bi-exclamation-triangle-fill';
        if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';

        echo '<div class="alert alert-' . sanitize($type) . ' alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert">
            <i class="bi ' . $icon . ' fs-5 me-2"></i>
            <div>' . sanitize($flash['message']) . '</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

/**
 * Generate Unique Complaint Public ID: CIRS-YYYY-XXXXX
 */
function generateComplaintId($pdo) {
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM complaints WHERE complaint_id LIKE 'CIRS-{$year}-%'");
    $row = $stmt->fetch();
    $next_num = ($row['count'] ?? 0) + 1;
    return sprintf("CIRS-%s-%05d", $year, $next_num);
}

/**
 * Intelligent Suggestion: Category -> Department ID
 */
function suggestDepartmentId($category, $pdo) {
    global $CATEGORIES;
    if (isset($CATEGORIES[$category])) {
        return $CATEGORIES[$category]['dept_default'];
    }

    // Fallback: match category keyword in departments table
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE category_keyword LIKE ? OR department_name LIKE ? LIMIT 1");
    $stmt->execute(["%{$category}%", "%{$category}%"]);
    $dept = $stmt->fetch();
    return $dept ? $dept['id'] : 6; // Default to Municipal
}

/**
 * Format Status Badge
 */
function getStatusBadge($status) {
    global $STATUS_CONFIG;
    $status = strtoupper($status);
    $conf = $STATUS_CONFIG[$status] ?? [
        'label' => $status,
        'badge' => 'badge-pending',
        'icon' => 'bi-clock'
    ];
    return '<span class="status-badge ' . $conf['badge'] . '"><i class="bi ' . $conf['icon'] . ' me-1"></i>' . $conf['label'] . '</span>';
}

/**
 * Format Priority Badge
 */
function getPriorityBadge($priority) {
    global $PRIORITY_CONFIG;
    $conf = $PRIORITY_CONFIG[$priority] ?? [
        'label' => $priority,
        'badge' => 'badge-priority-medium'
    ];
    return '<span class="priority-badge ' . $conf['badge'] . '">' . $conf['label'] . '</span>';
}

/**
 * Format relative time (e.g. 2 hours ago)
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hrs = floor($diff / 3600);
        return $hrs . ' hr' . ($hrs > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('d M Y, h:i A', $timestamp);
    }
}

/**
 * Safe Date Formatter
 */
function formatDate($datetime, $format = 'd M Y, h:i A') {
    if (!$datetime) return 'N/A';
    return date($format, strtotime($datetime));
}
