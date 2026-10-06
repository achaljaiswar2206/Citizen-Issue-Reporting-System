<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Common Footer Include
 */
require_once __DIR__ . '/../config/config.php';
?>

</div> <!-- End d-flex flex-column min-vh-100 -->

<footer class="cirs-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="cirs-logo-badge">
                        <i class="bi bi-buildings"></i>
                    </div>
                    <h4 class="text-white mb-0 fw-bold"><?= APP_NAME ?></h4>
                </div>
                <p class="text-secondary small leading-relaxed">
                    A smart civic initiative connecting citizens with local municipal authorities for quick resolution of public infrastructure issues. Transparent, accountable, and community-driven.
                </p>
                <div class="d-flex gap-3 text-secondary mt-3">
                    <span class="badge bg-dark border border-secondary text-secondary p-2"><i class="bi bi-shield-check text-success me-1"></i> Verified Redressal</span>
                    <span class="badge bg-dark border border-secondary text-secondary p-2"><i class="bi bi-clock-history text-primary me-1"></i> 24/7 Portal</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/index.php"><i class="bi bi-chevron-right small me-1"></i> Home</a></li>
                    <li><a href="<?= BASE_URL ?>/citizen/file_complaint.php"><i class="bi bi-chevron-right small me-1"></i> Report Issue</a></li>
                    <li><a href="<?= BASE_URL ?>/track.php"><i class="bi bi-chevron-right small me-1"></i> Track Status</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php"><i class="bi bi-chevron-right small me-1"></i> About CIRS</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php"><i class="bi bi-chevron-right small me-1"></i> Contact & Help</a></li>
                </ul>
            </div>

            <!-- Common Categories -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5>Civic Categories</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/citizen/file_complaint.php?cat=Pothole"><i class="bi bi-cone-striped me-2 text-warning"></i> Pothole & Road</a></li>
                    <li><a href="<?= BASE_URL ?>/citizen/file_complaint.php?cat=Garbage"><i class="bi bi-trash3-fill me-2 text-success"></i> Garbage Accumulation</a></li>
                    <li><a href="<?= BASE_URL ?>/citizen/file_complaint.php?cat=Water+Leakage"><i class="bi bi-droplet-fill me-2 text-info"></i> Water Leakage</a></li>
                    <li><a href="<?= BASE_URL ?>/citizen/file_complaint.php?cat=Streetlight"><i class="bi bi-lightbulb-fill me-2 text-warning"></i> Broken Streetlight</a></li>
                    <li><a href="<?= BASE_URL ?>/citizen/file_complaint.php?cat=Drainage"><i class="bi bi-water me-2 text-primary"></i> Open Drainage</a></li>
                </ul>
            </div>

            <!-- Helplines & Admin -->
            <div class="col-lg-3 col-md-6">
                <h5>Emergency Helplines</h5>
                <ul class="text-secondary small">
                    <li class="mb-2"><i class="bi bi-telephone-fill text-primary me-2"></i> Civic Control Room: <strong>1800-111-2000</strong></li>
                    <li class="mb-2"><i class="bi bi-envelope-fill text-primary me-2"></i> Support: <strong>support@cirs.gov</strong></li>
                    <li class="mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Municipal Headquarters, City Centre</li>
                </ul>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#adminRestrictedModal">
                        <i class="bi bi-shield-lock me-1"></i> Municipal Staff Portal
                    </button>
                </div>
            </div>
        </div>

        <!-- Municipal Staff Portal Restricted Access Modal -->
        <div class="modal fade text-dark" id="adminRestrictedModal" tabindex="-1" aria-labelledby="adminRestrictedModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white border-0 py-3">
                        <h5 class="modal-title d-flex align-items-center gap-2 fw-bold" id="adminRestrictedModalLabel">
                            <i class="bi bi-shield-lock-fill text-warning"></i> Municipal Staff Portal
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="kpi-icon bg-warning-subtle text-warning mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.75rem;">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Restricted Access Area</h5>
                        <p class="text-muted small mb-3">
                            This section is strictly restricted to authorized Municipal Corporation administrative officers and field department supervisors. General citizens are not permitted to access the administrative control room.
                        </p>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-danger text-start small mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-person-x-fill fs-5"></i>
                                <div>
                                    <strong>Access Denied:</strong> You are currently logged in as Citizen (<strong><?= sanitize($_SESSION['user_name'] ?? 'User') ?></strong>). Citizen accounts are barred from administrative portals.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary text-start small mb-3">
                                <i class="bi bi-info-circle me-1"></i> Only authorized staff with municipal commissioner credentials may proceed.
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                                <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-dark btn-sm px-3 fw-bold">
                                    <i class="bi bi-key-fill me-1"></i> Staff Login Portal
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Copyright -->
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <p class="mb-0 text-secondary">&copy; <?= APP_YEAR ?> <?= APP_NAME ?> (CIRS). All rights reserved. Built for municipal transparency.</p>
            <div class="d-flex gap-3 text-secondary small">
                <a href="<?= BASE_URL ?>/about.php" class="text-secondary">Privacy Policy</a>
                <span>&bull;</span>
                <a href="<?= BASE_URL ?>/about.php" class="text-secondary">Citizen Charter</a>
                <span>&bull;</span>
                <a href="<?= BASE_URL ?>/contact.php" class="text-secondary">Support</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Leaflet JS (Free OpenStreetMap) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Chart.js (for Admin Dashboard Analytics) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<!-- CIRS Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/script.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
