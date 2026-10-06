<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * File New Civic Complaint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser($pdo);
$page_title = "Report Civic Issue";

$selected_cat = $_GET['cat'] ?? 'Pothole';
if (!array_key_exists($selected_cat, $CATEGORIES)) {
    $selected_cat = 'Pothole';
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
                <li class="breadcrumb-item active" aria-current="page">Report Issue</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="cirs-card shadow-sm bg-white p-4 p-md-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 mb-4 border-bottom">
                        <div>
                            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-megaphone-fill text-primary me-2"></i>Report a Civic Grievance</h3>
                            <p class="text-muted small mb-0">Fill in the accurate issue details and location to help municipal teams resolve it promptly.</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small">
                            <i class="bi bi-shield-lock me-1"></i> Official Submission
                        </span>
                    </div>

                    <?php renderFlash(); ?>

                    <form action="<?= BASE_URL ?>/citizen/submit_complaint.php" method="POST" enctype="multipart/form-data" id="complaintForm">
                        <?= csrf_field() ?>

                        <div class="row g-4">
                            <!-- Left Column: Details -->
                            <div class="col-lg-7">
                                <!-- Category Selection -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark">Issue Category <span class="text-danger">*</span></label>
                                    <select name="category" id="category" class="form-select form-select-lg" required>
                                        <?php foreach ($CATEGORIES as $cat_key => $cat_val): ?>
                                            <option value="<?= sanitize($cat_key) ?>" <?= ($selected_cat === $cat_key) ? 'selected' : '' ?>>
                                                <?= sanitize($cat_key) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text small">Select the category that best describes the problem.</div>
                                </div>

                                <!-- Complaint Title -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark">Complaint Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Dangerous crater pothole near school gate" required maxlength="180">
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark">Detailed Description <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue in detail, how long it has existed, and the exact hazard it causes..." required></textarea>
                                </div>

                                <!-- Image Upload Box -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark">Photo Evidence (Optional, Recommended)</label>
                                    <div class="image-preview-box" id="image_preview_box" onclick="document.getElementById('complaint_image').click();">
                                        <div id="upload_placeholder" class="text-center p-3">
                                            <i class="bi bi-cloud-arrow-up-fill fs-1 text-primary mb-2 d-block"></i>
                                            <span class="fw-semibold text-dark">Click or Drag & Drop photo here</span>
                                            <small class="d-block text-muted mt-1">Supported formats: JPG, PNG, WEBP (Max 5MB)</small>
                                        </div>
                                        <img id="image_preview" src="#" alt="Preview" class="img-fluid d-none" style="max-height: 170px; object-fit: contain;">
                                    </div>
                                    <input type="file" name="image" id="complaint_image" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp">
                                </div>
                            </div>

                            <!-- Right Column: Location & Map -->
                            <div class="col-lg-5">
                                <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold text-dark mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Location & Landmark <span class="text-danger">*</span></label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <input type="text" name="location" id="location_address" class="form-control" placeholder="Street name, landmark, Ward number" required value="<?= sanitize($user['address'] ?? '') ?>">
                                            <div class="form-text small">Enter landmark or drag the pin on map.</div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-semibold text-secondary">Pin Point on Map:</span>
                                            <button type="button" id="btn_locate_me" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i> Locate Me
                                            </button>
                                        </div>

                                        <!-- Interactive Leaflet Map Picker -->
                                        <div id="location_map_picker" class="leaflet-map-picker mb-3"></div>

                                        <input type="hidden" name="latitude" id="map_lat" value="19.0760">
                                        <input type="hidden" name="longitude" id="map_lng" value="72.8777">

                                        <div class="small text-muted bg-white p-2 rounded border">
                                            <i class="bi bi-info-circle text-primary me-1"></i>
                                            Drag the red marker or click anywhere on the map to set exact GPS coordinates.
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow">
                                            <i class="bi bi-send-fill me-2"></i> Submit Grievance
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Initialize Leaflet Map for Location Picker -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        initLocationPicker('location_map_picker', 'map_lat', 'map_lng', 'location_address', 19.0760, 72.8777);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
