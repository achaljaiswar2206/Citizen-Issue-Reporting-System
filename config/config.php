<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Global Configuration File
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Constants
define('APP_NAME', 'Citizen Issue Reporting System');
define('APP_SHORT_NAME', 'CIRS');
define('APP_VERSION', '1.0.0');
define('APP_YEAR', '2026');

// Base URL calculation (automatic for XAMPP / subfolders)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Ensure clean root path
$base_url = rtrim($protocol . $host . $script_name, '/');
if (strpos($base_url, '/citizen') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/citizen'));
} elseif (strpos($base_url, '/admin') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/admin'));
}
define('BASE_URL', $base_url);

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/uploads/complaints/');
define('UPLOAD_URL', BASE_URL . '/uploads/complaints/');

// Issue Categories & Mapping
$CATEGORIES = [
    'Pothole' => [
        'icon' => 'bi-cone-striped',
        'color' => '#f59e0b',
        'dept_default' => 1, // Road / Public Works
        'desc' => 'Damaged road surface, craters, asphalt cracks, and uneven pavements.'
    ],
    'Garbage' => [
        'icon' => 'bi-trash3-fill',
        'color' => '#10b981',
        'dept_default' => 2, // Sanitation
        'desc' => 'Overflowing waste bins, unattended community trash, and foul odor.'
    ],
    'Water Leakage' => [
        'icon' => 'bi-droplet-fill',
        'color' => '#0284c7',
        'dept_default' => 3, // Water Supply
        'desc' => 'Broken supply mains, open hydrants, pipe bursts, and water wastage.'
    ],
    'Streetlight' => [
        'icon' => 'bi-lightbulb-fill',
        'color' => '#eab308',
        'dept_default' => 4, // Electricity
        'desc' => 'Non-functioning street lamps, flickering lights, and dark stretches.'
    ],
    'Drainage' => [
        'icon' => 'bi-water',
        'color' => '#6366f1',
        'dept_default' => 5, // Drainage
        'desc' => 'Clogged storm drains, missing manhole covers, and sewage overflow.'
    ],
    'Illegal Dumping' => [
        'icon' => 'bi-exclamation-triangle-fill',
        'color' => '#ef4444',
        'dept_default' => 2, // Sanitation
        'desc' => 'Unauthorized dumping of commercial or construction debris in public areas.'
    ],
    'Other' => [
        'icon' => 'bi-grid-fill',
        'color' => '#8b5cf6',
        'dept_default' => 6, // Municipal
        'desc' => 'General civic infrastructure issues, encroachments, or public hazards.'
    ]
];

// Status Badges & Colors
$STATUS_CONFIG = [
    'PENDING' => [
        'label' => 'Pending Review',
        'badge' => 'badge-pending',
        'color' => '#f59e0b',
        'icon' => 'bi-clock-history'
    ],
    'IN PROGRESS' => [
        'label' => 'In Progress',
        'badge' => 'badge-in-progress',
        'color' => '#0284c7',
        'icon' => 'bi-gear-wide-connected'
    ],
    'RESOLVED' => [
        'label' => 'Resolved',
        'badge' => 'badge-resolved',
        'color' => '#10b981',
        'icon' => 'bi-check-circle-fill'
    ]
];

// Priority Badges & Colors
$PRIORITY_CONFIG = [
    'Low' => [
        'label' => 'Low Priority',
        'badge' => 'badge-priority-low',
        'color' => '#64748b'
    ],
    'Medium' => [
        'label' => 'Medium Priority',
        'badge' => 'badge-priority-medium',
        'color' => '#0284c7'
    ],
    'High' => [
        'label' => 'High Priority',
        'badge' => 'badge-priority-high',
        'color' => '#ea580c'
    ],
    'Emergency' => [
        'label' => 'Emergency',
        'badge' => 'badge-priority-emergency',
        'color' => '#dc2626'
    ]
];
