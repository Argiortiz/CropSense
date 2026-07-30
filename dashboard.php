<?php
require_once "includes/session.php";
require_once "includes/roles.php";
require_once "includes/device_status.php";
require_once "config/database.php";
require_once "includes/latest_sensor.php";
require_once "includes/crop_suitability.php";

date_default_timezone_set("Asia/Manila");

$sensorSnapshot = cropsense_latest_sensor_reading($conn);
$latestSensorReading = $sensorSnapshot["is_valid"] ? $sensorSnapshot["reading"] : null;
$rawSensorReading = $sensorSnapshot["reading"];
$sensorAgeSeconds = $sensorSnapshot["age_seconds"];
$sensorIsLive = $sensorSnapshot["device_state"] === "online";
$sensorStatusLabel = $sensorSnapshot["device_status"];
$sensorStatusClass = $sensorSnapshot["device_state"] === "offline" || $sensorSnapshot["device_state"] === "invalid"
    ? "is-offline"
    : ($sensorSnapshot["device_state"] === "stale" ? "is-stale" : "");
$dashboardCropConfig = cropsense_crop_config();
$dashboardCrops = array_intersect_key(
    $dashboardCropConfig["crops"] ?? [],
    array_flip(["rice", "corn", "tobacco"])
);
$dashboardCropIcons = [
    "rice" => "bi-droplet-half",
    "corn" => "bi-flower1",
    "tobacco" => "bi-wind",
];
function dashboard_number($reading, $key, $digits = 0)
{
    if (!$reading || !isset($reading[$key]) || !is_numeric($reading[$key])) {
        return "--";
    }

    return number_format((float) $reading[$key], $digits);
}

function dashboard_relative_time($seconds)
{
    if ($seconds === null) {
        return "waiting for device input";
    }

    if ($seconds < 60) {
        return "just now";
    }

    if ($seconds < 3600) {
        return floor($seconds / 60) . " min ago";
    }

    return floor($seconds / 3600) . " hr ago";
}

function dashboard_status_text($reading, $ageSeconds, $liveText = "Updated")
{
    if (!$reading) {
        return "Awaiting sensor reading";
    }

    $prefix = $ageSeconds !== null && $ageSeconds <= 120 ? $liveText : "Last reading";
    return $prefix . " " . dashboard_relative_time($ageSeconds);
}

$sensorUpdatedText = $sensorSnapshot["is_valid"] ? dashboard_status_text($latestSensorReading, $sensorAgeSeconds) : "Invalid sensor reading";
$sensorPh = $latestSensorReading && (float) $latestSensorReading["soil_ph"] > 0
    ? dashboard_number($latestSensorReading, "soil_ph", 1)
    : "--";
$sensorPhStatus = $sensorPh === "--" ? "pH sensor not connected" : $sensorUpdatedText;
$sensorEc = dashboard_number($latestSensorReading, "soil_ec");
$sensorEcStatus = $latestSensorReading ? $sensorUpdatedText : "Electrical conductivity";
$sensorNpk = dashboard_number($latestSensorReading, "nitrogen")
    . " / " . dashboard_number($latestSensorReading, "phosphorus")
    . " / " . dashboard_number($latestSensorReading, "potassium");
$sensorActivityTitle = $latestSensorReading
    ? ($sensorIsLive ? "Live sensor reading received" : $sensorStatusLabel)
    : ($rawSensorReading && !$sensorSnapshot["is_valid"] ? "Invalid sensor reading" : "Sensor readings pending");
$sensorActivityDetail = $latestSensorReading
    ? (($latestSensorReading["device_name"] ?? "CropSense device") . " " . ($sensorIsLive ? "updated " : "last updated ") . dashboard_relative_time($sensorAgeSeconds))
    : ($rawSensorReading && !$sensorSnapshot["is_valid"] ? implode(", ", $sensorSnapshot["validation_errors"]) : "Waiting for device input");
$dashboardHour = (int) date("G");
$dashboardGreeting = $dashboardHour < 12
    ? "Good morning"
    : ($dashboardHour < 18 ? "Good afternoon" : "Good evening");
$dashboardOverview = $dashboardHour < 12
    ? "Morning field overview"
    : ($dashboardHour < 18 ? "Afternoon field overview" : "Evening field overview");
$fieldMoodText = $sensorIsLive
    ? "Ready for today's scan"
    : ($sensorStatusLabel === "Sensor Stale" ? "Waiting for a fresh reading" : "Waiting for sensor connection");
$dashboardUserName = $_SESSION["fullname"] ?? "Administrator";
$pageTitle = "Live Monitoring";
$bodyExtraClass = "dashboard-hero-enhanced";

include "includes/header.php";
?>

<div class="dashboard-shell">
    <?php include "includes/sidebar.php"; ?>

    <main class="dashboard-main">
        <?php include "includes/navbar.php"; ?>

        <section class="dashboard-hero field-command-hero classic-command-hero">
            <div class="hero-copy">
                <div class="hero-eyebrow-row">
                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        <span data-greeting-period><?php echo htmlspecialchars($dashboardOverview); ?></span>
                    </span>
                </div>
                <h2>
                    <span data-dashboard-greeting><?php echo htmlspecialchars($dashboardGreeting); ?></span>,
                    <?php echo htmlspecialchars($dashboardUserName); ?>!
                </h2>
                <p class="hero-lead">Your farm conditions, beautifully in focus.</p>
                <p class="hero-description">
                    Watch soil health, nutrient balance, device activity, and crop signals from
                    one calm command center built for smarter field decisions.
                </p>

                <div class="hero-status-strip" aria-label="Field date, time, and device status">
                    <span>
                        <i class="bi bi-geo-alt"></i>
                        North Field
                    </span>
                    <span>
                        <i class="bi bi-calendar3"></i>
                        <output data-dashboard-date><?php echo date("F j, Y"); ?></output>
                    </span>
                    <span>
                        <i class="bi bi-clock"></i>
                        <output data-dashboard-time><?php echo date("g:i A"); ?></output>
                    </span>
                    <span class="hero-live-state <?php echo $sensorIsLive ? "is-live" : "is-offline"; ?>" data-hero-status>
                        <i></i>
                        <b data-hero-status-label><?php echo htmlspecialchars($sensorStatusLabel); ?></b>
                    </span>
                </div>

                <div class="hero-metrics field-command-metrics classic-hero-metrics" aria-label="Live Monitoring summary">
                    <div>
                        <strong>5</strong>
                        <span>Field Signals</span>
                    </div>
                    <div>
                        <strong data-device-online-count><?php echo (int) $deviceSummary['active_count']; ?></strong>
                        <span>Devices Online</span>
                    </div>
                    <div>
                        <strong>2.0</strong>
                        <span>CropSense Version</span>
                    </div>
                </div>
            </div>

            <div class="hero-orbit classic-hero-orbit" aria-label="CropSense field overview">
                <div class="orbit-ring classic-orbit-ring">
                    <img src="/assets/img/cropsense-logo.svg" alt="CropSense">
                </div>

                <div class="hero-weather classic-field-mood">
                    <i class="bi bi-sun"></i>
                    <div>
                        <small>Field Mood</small>
                        <strong data-field-mood><?php echo htmlspecialchars($fieldMoodText); ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="sensor-grid sensor-grid--soil" aria-label="Soil sensor readings" data-live-dashboard>
            <article class="sensor-card npk nutrient-card" data-sensor-card="npk">
                <div class="sensor-icon">
                    <i class="bi bi-gem"></i>
                </div>
                <div>
                    <span>NPK Sensor</span>
                    <strong data-reading="npk"><?php echo $sensorNpk; ?></strong>
                    <small data-reading-status="npk"><?php echo $latestSensorReading ? "N, P, K mg/kg " . htmlspecialchars($sensorUpdatedText) : "Nitrogen, phosphorus, potassium"; ?></small>
                    <div class="nutrient-values" aria-label="NPK nutrient values">
                        <span><b>N</b> <output data-reading="nitrogen"><?php echo dashboard_number($latestSensorReading, "nitrogen"); ?></output></span>
                        <span><b>P</b> <output data-reading="phosphorus"><?php echo dashboard_number($latestSensorReading, "phosphorus"); ?></output></span>
                        <span><b>K</b> <output data-reading="potassium"><?php echo dashboard_number($latestSensorReading, "potassium"); ?></output></span>
                    </div>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card moisture" data-sensor-card="moisture">
                <div class="sensor-icon">
                    <i class="bi bi-droplet-half"></i>
                </div>
                <div>
                    <span>Soil Moisture</span>
                    <strong data-reading="soil_moisture"><?php echo dashboard_number($latestSensorReading, "soil_moisture"); ?>%</strong>
                    <small data-reading-status="soil_moisture"><?php echo htmlspecialchars($sensorUpdatedText); ?></small>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card ph" data-sensor-card="ph">
                <div class="sensor-icon">
                    <i class="bi bi-eyedropper"></i>
                </div>
                <div>
                    <span>Soil pH</span>
                    <strong data-reading="soil_ph"><?php echo $sensorPh; ?></strong>
                    <small data-reading-status="soil_ph"><?php echo htmlspecialchars($sensorPhStatus); ?></small>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card temperature" data-sensor-card="temperature">
                <div class="sensor-icon">
                    <i class="bi bi-thermometer-sun"></i>
                </div>
                <div>
                    <span>Temperature</span>
                    <strong data-reading="temperature"><?php echo dashboard_number($latestSensorReading, "temperature", 1); ?> C</strong>
                    <small data-reading-status="temperature"><?php echo $latestSensorReading ? htmlspecialchars($sensorUpdatedText) : "Field climate reading"; ?></small>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card ec" data-sensor-card="ec">
                <div class="sensor-icon">
                    <i class="bi bi-lightning-charge"></i>
                </div>
                <div>
                    <span>Soil EC</span>
                    <strong data-reading="soil_ec"><?php echo $sensorEc; ?></strong>
                    <small data-reading-status="soil_ec"><?php echo htmlspecialchars($sensorEcStatus); ?></small>
                </div>
                <span class="sensor-glow"></span>
            </article>

        </section>



        <section class="dashboard-content">
            <article class="recommendation-panel">
                <div class="section-heading">
                    <div>
                        <span>AI Recommendation</span>
                        <h3>Best Crop Match</h3>
                    </div>
                    <i class="bi bi-flower2"></i>
                </div>

                <div class="crop-match">
                    <div class="match-summary">
                        <span>Overall Readiness</span>
                        <strong data-recommendation-score>--</strong>
                        <div class="overall-readiness-track" aria-hidden="true">
                            <i data-readiness-bar="overall"></i>
                        </div>
                    </div>
                    <div>
                        <h4 data-recommendation-title>No crop selected yet</h4>
                        <p>
                            Once sensor data is available, CropSense can highlight crops
                            that fit the current soil and climate profile.
                        </p>
                    </div>
                </div>

                <div class="field-visual field-bar-chart" aria-label="Crop recommendation bar graph">
                    <div class="bar-chart-item">
                        <strong data-readiness-bar="moisture">
                            <span data-readiness-value="moisture">0%</span>
                        </strong>
                        <small>Moisture</small>
                    </div>
                    <div class="bar-chart-item">
                        <strong data-readiness-bar="nutrients">
                            <span data-readiness-value="nutrients">0%</span>
                        </strong>
                        <small>Nutrients</small>
                    </div>
                    <div class="bar-chart-item">
                        <strong data-readiness-bar="climate">
                            <span data-readiness-value="climate">0%</span>
                        </strong>
                        <small>Climate</small>
                    </div>
                </div>

                <div class="dashboard-crop-heading">
                    <div>
                        <span>Crop Candidates</span>
                        <h4>Rice, Corn and Tobacco</h4>
                    </div>
                    <small>Live pH and temperature comparison</small>
                </div>

                <div class="dashboard-crop-grid" aria-label="Crop candidate status">
                    <?php foreach ($dashboardCrops as $cropKey => $crop) : ?>
                        <article
                            class="dashboard-crop-card is-pending"
                            data-crop-card="<?php echo htmlspecialchars($cropKey); ?>"
                            data-ph-min="<?php echo htmlspecialchars((string) ($crop["ph"]["min"] ?? "")); ?>"
                            data-ph-max="<?php echo htmlspecialchars((string) ($crop["ph"]["max"] ?? "")); ?>"
                            data-temperature-min="<?php echo htmlspecialchars((string) ($crop["temperature"]["min"] ?? "")); ?>"
                            data-temperature-max="<?php echo htmlspecialchars((string) ($crop["temperature"]["max"] ?? "")); ?>"
                            data-ph-tolerance="<?php echo htmlspecialchars((string) ($dashboardCropConfig["near_tolerance"]["soil_ph"] ?? 0.2)); ?>"
                            data-temperature-tolerance="<?php echo htmlspecialchars((string) ($dashboardCropConfig["near_tolerance"]["temperature"] ?? 2)); ?>">
                            <div class="dashboard-crop-card-header">
                                <span class="dashboard-crop-icon">
                                    <i class="bi <?php echo htmlspecialchars($dashboardCropIcons[$cropKey] ?? "bi-flower2"); ?>"></i>
                                </span>
                                <span class="dashboard-crop-status" data-crop-status>Awaiting readings</span>
                            </div>
                            <h5><?php echo htmlspecialchars($crop["name"] ?? ucfirst($cropKey)); ?></h5>
                            <em><?php echo htmlspecialchars($crop["scientific_name"] ?? ""); ?></em>
                            <div class="dashboard-crop-ranges">
                                <span>pH <?php echo htmlspecialchars(cropsense_range_text($crop["ph"] ?? [], 1)); ?></span>
                                <span><?php echo htmlspecialchars(cropsense_range_text($crop["temperature"] ?? [], 0, "C")); ?></span>
                            </div>
                            <p data-crop-note>Waiting for live pH and temperature data.</p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="recommendation-list">
                    <div>
                        <i class="bi bi-check2-circle"></i>
                        Connect soil sensor
                    </div>
                    <div>
                        <i class="bi bi-check2-circle"></i>
                        Collect field readings
                    </div>
                    <div>
                        <i class="bi bi-check2-circle"></i>
                        Generate crop match
                    </div>
                </div>
            </article>

            <article class="activity-panel">
                <div class="section-heading">
                    <div>
                        <span>Today</span>
                        <h3>Farm Activity</h3>
                    </div>
                    <i class="bi bi-activity"></i>
                </div>

                <div class="timeline">
                    <div>
                        <span></span>
                        <div>
                            <strong>Live Monitoring opened</strong>
                            <small>System ready for field review</small>
                        </div>
                    </div>
                    <div>
                        <span></span>
                        <div>
                            <strong data-activity-title><?php echo htmlspecialchars($sensorActivityTitle); ?></strong>
                            <small data-activity-detail><?php echo htmlspecialchars($sensorActivityDetail); ?></small>
                        </div>
                    </div>
                    <div>
                        <span></span>
                        <div>
                            <strong>Reports available soon</strong>
                            <small>Charts will appear after data capture</small>
                        </div>
                    </div>
                </div>
            </article>

            <article class="quick-actions">
                <div class="section-heading">
                    <div>
                        <span>Tools</span>
                        <h3>Quick Actions</h3>
                    </div>
                </div>

                <?php if (cropsense_is_admin()) : ?>
                    <a href="user_management.php">
                        <i class="bi bi-person-plus"></i>
                        Insert User
                    </a>
                <?php endif; ?>

                <a href="collected_data.php">
                    <i class="bi bi-table"></i>
                    View Collected Data
                </a>

                <a href="export_collected_data.php">
                    <i class="bi bi-file-earmark-excel"></i>
                    Download Data
                </a>
            </article>
        </section>
    </main>
</div>

<script src="/assets/js/dashboard.js?v=20260722-classic-hero-v4"></script>
<?php include "includes/footer.php"; ?>
