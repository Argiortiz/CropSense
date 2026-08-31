<?php
require_once "includes/session.php";
require_once "config/database.php";

$pageTitle = "Collected Data";
$pageKicker = ($_SESSION["role"] ?? "") === "Administrator" ? "Administrator Dashboard" : "MAO Staff Dashboard";
$hideSensorIndicator = true;

$rangeOptions = [
    "hour" => [
        "label" => "Last hour",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
    ],
    "day" => [
        "label" => "Last 24 hours",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
    ],
    "week" => [
        "label" => "Last 7 days",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    ],
    "month" => [
        "label" => "Last 4 weeks",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)",
    ],
    "all" => [
        "label" => "All time",
        "condition" => "1 = 1",
    ],
];
$selectedRange = $_GET["range"] ?? "all";

if (!isset($rangeOptions[$selectedRange])) {
    $selectedRange = "all";
}

$selectedRangeLabel = $rangeOptions[$selectedRange]["label"];
$rangeCondition = $rangeOptions[$selectedRange]["condition"];
$readings = [];
$readingSql = "
    SELECT
        sr.id,
        sr.soil_moisture,
        sr.soil_ph,
        sr.soil_ec,
        sr.nitrogen,
        sr.phosphorus,
        sr.potassium,
        sr.temperature,
        sr.created_at,
        d.device_name,
        d.device_code,
        f.farm_name,
        f.barangay
    FROM sensor_readings sr
    INNER JOIN devices d ON d.id = sr.device_id
    INNER JOIN farms f ON f.id = sr.farm_id
    WHERE {$rangeCondition}
    ORDER BY sr.created_at DESC, sr.id DESC
    LIMIT 250
";

$readingResult = $conn->query($readingSql);

if ($readingResult) {
    while ($row = $readingResult->fetch_assoc()) {
        $readings[] = $row;
    }
}

include "includes/header.php";
?>

<div class="dashboard-shell">
    <?php include "includes/sidebar.php"; ?>

    <main class="dashboard-main">
        <?php include "includes/navbar.php"; ?>

        <section class="management-hero data-hero">
            <div>
                <span class="hero-badge">
                    <i class="bi bi-database-check"></i>
                    Field Collection Records
                </span>
                <h2>Review collected soil sensor data.</h2>
                <p>Monitor pH, EC, NPK, soil temperature, and moisture records from connected field devices.</p>
            </div>

            <a class="download-action" href="export_collected_data.php?range=<?php echo urlencode($selectedRange); ?>">
                <i class="bi bi-file-earmark-excel"></i>
                Download Data
            </a>
        </section>

        <section class="management-panel full">
            <nav class="data-filter-tabs" aria-label="Collected data categories">
                <a href="collected_data.php?range=hour" class="<?php echo $selectedRange === "hour" ? "active" : ""; ?>">Last hour</a>
                <a href="collected_data.php?range=day" class="<?php echo $selectedRange === "day" ? "active" : ""; ?>">Last 24 hours</a>
                <a href="collected_data.php?range=week" class="<?php echo $selectedRange === "week" ? "active" : ""; ?>">Last 7 days</a>
                <details class="data-filter-more">
                    <summary>More</summary>
                    <div>
                        <a href="collected_data.php?range=month" class="<?php echo $selectedRange === "month" ? "active" : ""; ?>">Last 4 weeks</a>
                        <a href="collected_data.php?range=all" class="<?php echo $selectedRange === "all" ? "active" : ""; ?>">All time</a>
                    </div>
                </details>
            </nav>

            <div class="section-heading">
                <div>
                    <span>Sensor Logs</span>
                    <h3><?php echo htmlspecialchars($selectedRangeLabel); ?></h3>
                </div>
                <i class="bi bi-table"></i>
            </div>

            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Farm</th>
                            <th>Device</th>
                            <th>Moisture</th>
                            <th>pH</th>
                            <th>EC</th>
                            <th>N</th>
                            <th>P</th>
                            <th>K</th>
                            <th>Soil Temp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readings as $reading) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reading["created_at"]); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($reading["farm_name"]); ?>
                                    <small><?php echo htmlspecialchars($reading["barangay"]); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($reading["device_name"]); ?>
                                    <small><?php echo htmlspecialchars($reading["device_code"]); ?></small>
                                </td>
                                <td><?php echo number_format((float) $reading["soil_moisture"], 0); ?>%</td>
                                <td><?php echo (float) $reading["soil_ph"] > 0 ? number_format((float) $reading["soil_ph"], 1) : "--"; ?></td>
                                <td><?php echo (float) $reading["soil_ec"] > 0 ? number_format((float) $reading["soil_ec"], 0) : "--"; ?></td>
                                <td><?php echo number_format((float) $reading["nitrogen"], 0); ?></td>
                                <td><?php echo number_format((float) $reading["phosphorus"], 0); ?></td>
                                <td><?php echo number_format((float) $reading["potassium"], 0); ?></td>
                                <td><?php echo number_format((float) $reading["temperature"], 1); ?> °C</td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$readings) : ?>
                            <tr>
                                <td colspan="10">No collected data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<?php include "includes/footer.php"; ?>
