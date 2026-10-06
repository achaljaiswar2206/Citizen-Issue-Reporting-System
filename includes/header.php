<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Common Header Include
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$page_title = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME . ' | Civic Redressal Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Report civic and municipal problems like potholes, garbage, water leakage, and broken streetlights directly to local authorities. Track complaint progress online.">
    <meta name="theme-color" content="#1d4ed8">
    <title><?= sanitize($page_title) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Leaflet CSS (OpenStreetMap - Free) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= APP_VERSION ?>">
</head>
<body>
<div class="d-flex flex-column min-vh-100">
