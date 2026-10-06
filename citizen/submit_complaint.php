<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Backend Complaint Submission Processor
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/citizen/file_complaint.php");
    exit;
}

if (!validate_csrf()) {
    setFlash('danger', 'Invalid session or CSRF security token. Please try submitting again.');
    header("Location: " . BASE_URL . "/citizen/file_complaint.php");
    exit;
}

$category = trim($_POST['category'] ?? '');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

// Validation
if (empty($category) || empty($title) || empty($description) || empty($location)) {
    setFlash('danger', 'Please fill in all mandatory fields.');
    header("Location: " . BASE_URL . "/citizen/file_complaint.php");
    exit;
}

// File Upload Processing
$image_filename = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_size = $_FILES['image']['size'];
    $file_name = $_FILES['image']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if ($file_size > 5 * 1024 * 1024) {
        setFlash('danger', 'Uploaded photo exceeds the 5MB size limit.');
        header("Location: " . BASE_URL . "/citizen/file_complaint.php");
        exit;
    } elseif (!in_array($file_ext, $allowed_exts) || !in_array($mime_type, $allowed_mimes)) {
        setFlash('danger', 'Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.');
        header("Location: " . BASE_URL . "/citizen/file_complaint.php");
        exit;
    } else {
        // Ensure upload directory exists
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $image_filename = 'cirs_img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        $dest_path = UPLOAD_DIR . $image_filename;

        if (!move_uploaded_file($file_tmp, $dest_path)) {
            $image_filename = null; // Proceed without image if upload fails
        }
    }
}

try {
    // Generate unique complaint ID
    $complaint_id = generateComplaintId($pdo);

    // Auto-suggest department based on category
    $dept_id = suggestDepartmentId($category, $pdo);

    // Initial Priority
    $priority = ($category === 'Drainage' || $category === 'Pothole') ? 'High' : 'Medium';

    // Insert into complaints table
    $stmt = $pdo->prepare("INSERT INTO complaints 
        (complaint_id, user_id, department_id, category, title, description, location, latitude, longitude, image, priority, status, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', 0)");
    
    $stmt->execute([
        $complaint_id,
        $user['id'],
        $dept_id,
        $category,
        $title,
        $description,
        $location,
        $latitude,
        $longitude,
        $image_filename,
        $priority
    ]);

    $inserted_id = $pdo->lastInsertId();

    // Log initial progress update
    $stmt_update = $pdo->prepare("INSERT INTO complaint_updates (complaint_id, admin_id, status, message) VALUES (?, NULL, 'PENDING', ?)");
    $stmt_update->execute([
        $inserted_id,
        "Complaint registered by citizen. Assigned reference ID {$complaint_id} and queued for municipal control room review."
    ]);

    setFlash('success', "Complaint submitted successfully! Your Reference ID is {$complaint_id}.");
    header("Location: " . BASE_URL . "/citizen/complaint_details.php?id=" . $inserted_id);
    exit;

} catch (Exception $e) {
    setFlash('danger', "An error occurred while filing your complaint: " . $e->getMessage());
    header("Location: " . BASE_URL . "/citizen/file_complaint.php");
    exit;
}
