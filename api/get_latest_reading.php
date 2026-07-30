<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/security.php";

cropsense_apply_security_headers("api");
header("Allow: GET");

function latest_response($body, $status = 200)
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "GET") {
    latest_response(["ok" => false, "error" => "Method not allowed."], 405);
}

$deviceCode = trim((string) ($_GET["device_id"] ?? ""));
if ($deviceCode !== "" && !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,49}$/', $deviceCode)) {
    latest_response(["ok" => false, "error" => "Invalid device_id."], 422);
}

$sql = "SELECT sr.id, d.device_code AS device_id, sr.soil_moisture AS moisture, sr.temperature,
               sr.soil_ec AS ec, sr.soil_ph AS ph, sr.nitrogen, sr.phosphorus, sr.potassium,
               sr.created_at, TIMESTAMPDIFF(SECOND, sr.created_at, NOW()) AS age_seconds,
               d.device_name
        FROM sensor_readings sr INNER JOIN devices d ON d.id = sr.device_id";
$sql .= $deviceCode === "" ? "" : " WHERE d.device_code = ?";
$sql .= " ORDER BY sr.created_at DESC, sr.id DESC LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("CropSense latest reading prepare failed.");
    latest_response(["ok" => false, "error" => "Unable to load sensor reading."], 500);
}
if ($deviceCode !== "") {
    $stmt->bind_param("s", $deviceCode);
}
if (!$stmt->execute()) {
    error_log("CropSense latest reading query failed.");
    latest_response(["ok" => false, "error" => "Unable to load sensor reading."], 500);
}
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    latest_response(["ok" => true, "data" => null, "message" => "No sensor readings found."]);
}

$age = max(0, (int) $row["age_seconds"]);
$row["connection_status"] = $age <= 30 ? "Online" : ($age <= 60 ? "Stale" : "Offline");
$row["is_live"] = $age <= 30;

if ($age > 60) {
    $offlineDeviceCode = $row["device_id"];
    $offlineUpdate = $conn->prepare("UPDATE devices SET status = 'Offline' WHERE device_code = ? AND last_seen < DATE_SUB(NOW(), INTERVAL 60 SECOND)");
    if ($offlineUpdate) {
        $offlineUpdate->bind_param("s", $offlineDeviceCode);
        $offlineUpdate->execute();
        $offlineUpdate->close();
    }
}

// Preserve the original dashboard field names while also returning the
// concise public API contract used by the ESP32 integration.
$row["soil_moisture"] = $row["moisture"];
$row["soil_ph"] = $row["ph"];
$row["soil_ec"] = $row["ec"];
latest_response(["ok" => true, "data" => $row]);
