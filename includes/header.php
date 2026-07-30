<?php
require_once __DIR__ . "/security.php";
cropsense_apply_security_headers("web");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CropSense | <?php echo htmlspecialchars($pageTitle ?? "Live Monitoring"); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=20260722-classic-hero-v4">
</head>

<body class="dashboard-body sidebar-initializing<?php echo isset($bodyExtraClass) ? ' ' . htmlspecialchars($bodyExtraClass) : ''; ?>">
