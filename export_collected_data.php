<?php
require_once "includes/session.php";
require_once "config/database.php";

$rangeOptions = [
    "hour" => [
        "label" => "last-hour",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
    ],
    "day" => [
        "label" => "last-24-hours",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
    ],
    "week" => [
        "label" => "last-7-days",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    ],
    "month" => [
        "label" => "last-4-weeks",
        "condition" => "sr.created_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)",
    ],
    "all" => [
        "label" => "all-time",
        "condition" => "1 = 1",
    ],
];
$selectedRange = $_GET["range"] ?? "all";

if (!isset($rangeOptions[$selectedRange])) {
    $selectedRange = "all";
}

$rangeCondition = $rangeOptions[$selectedRange]["condition"];
$filename = "cropsense-collected-data-" . $rangeOptions[$selectedRange]["label"] . "-" . date("Y-m-d-His") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Pragma: no-cache");
header("Expires: 0");

$sql = "
    SELECT
        sr.created_at,
        f.farm_name,
        f.barangay,
        d.device_name,
        d.device_code,
        sr.soil_moisture,
        sr.soil_ph,
        sr.soil_ec,
        sr.nitrogen,
        sr.phosphorus,
        sr.potassium,
        sr.temperature
    FROM sensor_readings sr
    INNER JOIN devices d ON d.id = sr.device_id
    INNER JOIN farms f ON f.id = sr.farm_id
    WHERE {$rangeCondition}
    ORDER BY sr.created_at DESC, sr.id DESC
";

$result = $conn->query($sql);

echo "<table border=\"1\">";
echo "<thead><tr>";
echo "<th>Date/Time</th>";
echo "<th>Farm</th>";
echo "<th>Barangay</th>";
echo "<th>Device</th>";
echo "<th>Device Code</th>";
echo "<th>Soil Moisture (%)</th>";
echo "<th>Soil pH</th>";
echo "<th>Soil EC (uS/cm)</th>";
echo "<th>Nitrogen</th>";
echo "<th>Phosphorus</th>";
echo "<th>Potassium</th>";
echo "<th>Soil Temperature (°C)</th>";
echo "</tr></thead><tbody>";

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["created_at"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["farm_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["barangay"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["device_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["device_code"]) . "</td>";
        echo "<td>" . number_format((float) $row["soil_moisture"], 2, ".", "") . "</td>";
        echo "<td>" . number_format((float) $row["soil_ph"], 2, ".", "") . "</td>";
        echo "<td>" . number_format((float) $row["soil_ec"], 2, ".", "") . "</td>";
        echo "<td>" . number_format((float) $row["nitrogen"], 2, ".", "") . "</td>";
        echo "<td>" . number_format((float) $row["phosphorus"], 2, ".", "") . "</td>";
        echo "<td>" . number_format((float) $row["potassium"], 2, ".", "") . "</td>";
        echo "<td>" . number_format((float) $row["temperature"], 2, ".", "") . "</td>";
        echo "</tr>";
    }
}

echo "</tbody></table>";
exit;
