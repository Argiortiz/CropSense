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
$dashboardNpkClassification = $dashboardCropConfig["npk_classification"] ?? [];
$dashboardSuitability = cropsense_assess_crop_suitability($conn, $latestSensorReading);
$dashboardCropIcons = [
    "rice" => "bi-droplet-half",
    "corn" => "bi-flower1",
    "tobacco" => "bi-wind",
];
function dashboard_sensor_value($reading, $key)
{
    if (
        !$reading ||
        !array_key_exists($key, $reading) ||
        $reading[$key] === null ||
        trim((string) $reading[$key]) === "" ||
        !is_numeric($reading[$key])
    ) {
        return null;
    }

    return (float) $reading[$key];
}

function dashboard_number($reading, $key, $digits = 0)
{
    $value = dashboard_sensor_value($reading, $key);

    return $value === null ? "--" : number_format($value, $digits);
}

function dashboard_telemetry_number($reading, $key, $maxDigits = 2)
{
    $value = dashboard_sensor_value($reading, $key);

    if ($value === null) {
        return "--";
    }

    return rtrim(rtrim(number_format($value, $maxDigits, ".", ""), "0"), ".");
}

function dashboard_raw_sensor_value($reading, $key)
{
    if (
        !$reading ||
        !array_key_exists($key, $reading) ||
        $reading[$key] === null ||
        (is_string($reading[$key]) && trim($reading[$key]) === "")
    ) {
        return null;
    }

    return (string) $reading[$key];
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
$sensorPh = dashboard_telemetry_number($latestSensorReading, "soil_ph");
$sensorPhStatus = $sensorPh === "--" ? "pH sensor not connected" : $sensorUpdatedText;
$sensorEc = dashboard_number($latestSensorReading, "soil_ec");
$sensorEcStatus = $latestSensorReading ? $sensorUpdatedText : "Electrical conductivity";
$sensorNpk = (dashboard_raw_sensor_value($latestSensorReading, "nitrogen") ?? "--")
    . " / " . (dashboard_raw_sensor_value($latestSensorReading, "phosphorus") ?? "--")
    . " / " . (dashboard_raw_sensor_value($latestSensorReading, "potassium") ?? "--");
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
                    <img src="<?php echo htmlspecialchars(cropsense_asset('img/cropsense-logo.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="CropSense">
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

        <section
            class="sensor-grid sensor-grid--soil"
            aria-label="Soil sensor readings"
            data-live-dashboard
            data-initial-moisture="<?php echo htmlspecialchars((string) (dashboard_sensor_value($latestSensorReading, "soil_moisture") ?? "")); ?>"
            data-initial-ph="<?php echo htmlspecialchars((string) (dashboard_sensor_value($latestSensorReading, "soil_ph") ?? "")); ?>"
            data-initial-nitrogen="<?php echo htmlspecialchars(dashboard_raw_sensor_value($latestSensorReading, "nitrogen") ?? ""); ?>"
            data-initial-phosphorus="<?php echo htmlspecialchars(dashboard_raw_sensor_value($latestSensorReading, "phosphorus") ?? ""); ?>"
            data-initial-potassium="<?php echo htmlspecialchars(dashboard_raw_sensor_value($latestSensorReading, "potassium") ?? ""); ?>">
            <article class="sensor-card npk nutrient-card" data-sensor-card="npk">
                <div class="sensor-icon">
                    <i class="bi bi-gem"></i>
                </div>
                <div class="sensor-card-content">
                    <span>NPK Sensor</span>
                    <strong data-reading="npk"><?php echo htmlspecialchars($sensorNpk); ?></strong>
                    <small data-reading-status="npk"><?php echo $latestSensorReading ? "N, P, K mg/kg " . htmlspecialchars($sensorUpdatedText) : "Nitrogen, phosphorus, potassium"; ?></small>
                    <div class="nutrient-values" aria-label="NPK nutrient values">
                        <div class="nutrient-reading">
                            <b>Nitrogen</b>
                            <span class="nutrient-measurement">
                                <output data-reading="nitrogen"><?php echo htmlspecialchars(dashboard_raw_sensor_value($latestSensorReading, "nitrogen") ?? "--"); ?></output>
                                <small class="sensor-unit">mg/kg</small>
                            </span>
                            <span class="status-badge status-unknown" data-status-badge="nitrogen" data-status-label="Nitrogen">NO DATA</span>
                        </div>
                        <div class="nutrient-reading">
                            <b>Phosphorus</b>
                            <span class="nutrient-measurement">
                                <output data-reading="phosphorus"><?php echo htmlspecialchars(dashboard_raw_sensor_value($latestSensorReading, "phosphorus") ?? "--"); ?></output>
                                <small class="sensor-unit">mg/kg</small>
                            </span>
                            <span class="status-badge status-unknown" data-status-badge="phosphorus" data-status-label="Phosphorus">NO DATA</span>
                        </div>
                        <div class="nutrient-reading">
                            <b>Potassium</b>
                            <span class="nutrient-measurement">
                                <output data-reading="potassium"><?php echo htmlspecialchars(dashboard_raw_sensor_value($latestSensorReading, "potassium") ?? "--"); ?></output>
                                <small class="sensor-unit">mg/kg</small>
                            </span>
                            <span class="status-badge status-unknown" data-status-badge="potassium" data-status-label="Potassium">NO DATA</span>
                        </div>
                    </div>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card moisture" data-sensor-card="moisture">
                <div class="sensor-icon">
                    <i class="bi bi-droplet-half"></i>
                </div>
                <div class="sensor-card-content">
                    <span>Soil Moisture</span>
                    <strong class="sensor-value">
                        <output data-reading="soil_moisture"><?php echo dashboard_telemetry_number($latestSensorReading, "soil_moisture"); ?></output>
                        <small class="sensor-unit">%</small>
                    </strong>
                    <div class="telemetry-meta">
                        <small data-reading-status="soil_moisture"><?php echo htmlspecialchars($sensorUpdatedText); ?></small>
                        <span class="status-badge status-unknown" data-status-badge="soil_moisture" data-status-label="Soil moisture">NO DATA</span>
                    </div>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card ph" data-sensor-card="ph">
                <div class="sensor-icon">
                    <i class="bi bi-eyedropper"></i>
                </div>
                <div class="sensor-card-content">
                    <span>Soil pH</span>
                    <strong data-reading="soil_ph"><?php echo $sensorPh; ?></strong>
                    <div class="telemetry-meta">
                        <small data-reading-status="soil_ph"><?php echo htmlspecialchars($sensorPhStatus); ?></small>
                        <span class="status-badge status-unknown" data-status-badge="soil_ph" data-status-label="Soil pH">NO DATA</span>
                    </div>
                </div>
                <span class="sensor-glow"></span>
            </article>

            <article class="sensor-card temperature" data-sensor-card="temperature">
                <div class="sensor-icon">
                    <i class="bi bi-thermometer-sun"></i>
                </div>
                <div>
                    <span>Soil Temperature</span>
                    <strong data-reading="temperature"><?php echo dashboard_number($latestSensorReading, "temperature", 1); ?> °C</strong>
                    <small data-reading-status="temperature"><?php echo $latestSensorReading ? htmlspecialchars($sensorUpdatedText) : "Soil probe reading"; ?></small>
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
            <article class="recommendation-panel crop-assessment-panel">
                <div class="section-heading">
                    <div>
                        <span>Sensor-Based Assessment</span>
                        <h3>Crop Suitability</h3>
                    </div>
                    <i class="bi bi-clipboard2-pulse"></i>
                </div>

                <div class="crop-assessment-summary">
                    <div class="match-summary">
                        <span>Top Sensor-Based Score</span>
                        <strong data-recommendation-score>--</strong>
                        <span class="suitability-badge is-na" data-recommendation-class>Insufficient Data</span>
                    </div>
                    <div class="crop-assessment-summary-copy">
                        <span>Highest sensor-based suitability score</span>
                        <h4 data-recommendation-title>Waiting for an assessable reading</h4>
                        <p data-recommendation-description>
                            Crop-specific results appear when real sensor data and validated thresholds are both available.
                        </p>
                    </div>
                </div>

                <div class="assessment-method-note">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <strong>CropSense score-based suitability classes</strong>
                        <p>
                            S1, S2, S3, and N are FAO-inspired labels applied to the CropSense score and limiting-factor rules;
                            the percentage boundaries are not presented as official FAO thresholds.
                        </p>
                    </div>
                </div>

                <div class="crop-assessment-list" data-crop-assessment-list>
                    <?php foreach ($dashboardCrops as $cropKey => $crop) : ?>
                        <details class="crop-assessment-card is-na" data-crop-assessment="<?php echo htmlspecialchars($cropKey); ?>">
                            <summary>
                                <span class="crop-assessment-rank" data-crop-rank>--</span>
                                <span class="crop-assessment-icon">
                                    <i class="bi <?php echo htmlspecialchars($dashboardCropIcons[$cropKey] ?? "bi-flower2"); ?>"></i>
                                </span>
                                <span class="crop-assessment-name">
                                    <strong><?php echo htmlspecialchars($crop["name"] ?? ucfirst($cropKey)); ?></strong>
                                    <small><?php echo htmlspecialchars($crop["scientific_name"] ?? ""); ?></small>
                                </span>
                                <strong class="crop-assessment-percentage" data-crop-percentage>--</strong>
                                <span class="suitability-badge is-na" data-crop-final-badge>Insufficient Data</span>
                                <i class="bi bi-chevron-down crop-assessment-chevron" aria-hidden="true"></i>
                            </summary>

                            <div class="crop-assessment-details">
                                <div class="crop-assessment-metrics">
                                    <div>
                                        <span>Score</span>
                                        <strong data-crop-score>--</strong>
                                    </div>
                                    <div>
                                        <span>Final Class</span>
                                        <strong data-crop-final-class>Insufficient Data</strong>
                                    </div>
                                    <div>
                                        <span>Parameters Assessed</span>
                                        <strong data-crop-assessed>0 of 7</strong>
                                    </div>
                                    <div class="crop-assessment-metric--limiting">
                                        <span>Main Limiting Factor</span>
                                        <strong data-crop-limiting-factor>Not available</strong>
                                    </div>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>

                <aside class="crop-assessment-disclaimer">
                    <i class="bi bi-shield-check"></i>
                    <p>
                        CropSense recommendations are based only on soil parameters measured by the connected sensor.
                        Final crop selection should also consider factors not measured by the system, including soil texture,
                        soil depth, drainage, flooding risk, slope, climate, water availability, crop variety, and professional
                        agronomic assessment.
                    </p>
                </aside>
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
                    <a href="<?php echo htmlspecialchars(cropsense_url('user_management.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-person-plus"></i>
                        Insert User
                    </a>
                <?php endif; ?>

                <a href="<?php echo htmlspecialchars(cropsense_url('collected_data.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-table"></i>
                    View Collected Data
                </a>

                <a href="<?php echo htmlspecialchars(cropsense_url('export_collected_data.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-file-earmark-excel"></i>
                    Download Data
                </a>
            </article>
        </section>
    </main>
</div>

<script type="application/json" data-initial-crop-suitability><?php
    echo json_encode(
        $dashboardSuitability,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRESERVE_ZERO_FRACTION
    );
?></script>
<script type="application/json" data-npk-classification><?php
    echo json_encode(
        $dashboardNpkClassification,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRESERVE_ZERO_FRACTION
    );
?></script>
<script src="<?php echo htmlspecialchars(cropsense_asset('js/dashboard.js'), ENT_QUOTES, 'UTF-8'); ?>?v=20260827-crop-summary-v2"></script>
<?php include "includes/footer.php"; ?>
