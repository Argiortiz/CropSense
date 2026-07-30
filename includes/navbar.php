<?php
if (empty($hideSensorIndicator) && !isset($sensorStatusLabel, $sensorStatusClass)) {
    require_once __DIR__ . "/../config/database.php";
    require_once __DIR__ . "/latest_sensor.php";

    $navbarSensorSnapshot = cropsense_latest_sensor_reading($conn);
    $navbarSensorState = $navbarSensorSnapshot["device_state"] ?? "offline";
    $sensorStatusLabel = $navbarSensorState === "online"
        ? "Sensor Live"
        : ($navbarSensorState === "stale" ? "Sensor Stale" : "Sensor Offline");
    $sensorStatusClass = $navbarSensorState === "online"
        ? ""
        : ($navbarSensorState === "stale" ? "is-stale" : "is-offline");
}
?>

<header class="dashboard-topbar">
    <div class="topbar-title-row">
        <button
            type="button"
            class="topbar-sidebar-toggle"
            data-sidebar-toggle
            aria-controls="cropsenseSidebar"
            aria-expanded="false"
            aria-label="Toggle sidebar"
            title="Toggle sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>

        <div>
            <p class="topbar-kicker"><?php echo isset($pageKicker) ? htmlspecialchars($pageKicker) : 'CropSense Field Studio'; ?></p>
            <h1><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></h1>
        </div>
    </div>

    <div class="topbar-actions">
        <?php if (empty($hideSensorIndicator)) : ?>
            <div class="system-pill <?php echo isset($sensorStatusClass) ? $sensorStatusClass : 'is-offline'; ?>" data-system-pill>
                <span data-system-dot></span>
                <b data-system-label><?php echo isset($sensorStatusLabel) ? htmlspecialchars($sensorStatusLabel) : 'Sensor Offline'; ?></b>
            </div>
        <?php endif; ?>

        <div class="user-chip">
            <i class="bi bi-person-circle"></i>
            <div>
                <small>Welcome back</small>
                <strong><?php echo $_SESSION['fullname']; ?></strong>
                <small><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></small>
            </div>
        </div>
    </div>
</header>
