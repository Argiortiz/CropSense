<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/security.php";

cropsense_apply_security_headers("api");
header("Allow: POST");

function save_response($body, $status = 200)
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    save_response(["ok" => false, "error" => "Method not allowed."], 405);
}

$contentType = strtolower(trim(explode(";", $_SERVER["CONTENT_TYPE"] ?? "")[0]));
$raw = file_get_contents("php://input");
if (strlen((string) $raw) > CROPSENSE_MAX_SENSOR_PAYLOAD_BYTES) {
    save_response(["ok" => false, "error" => "Request body is too large."], 413);
}

if ($contentType === "application/json") {
    $payload = json_decode((string) $raw, true);
    if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
        save_response(["ok" => false, "error" => "Malformed JSON request."], 400);
    }
} else {
    $payload = $_POST;
}

if (CROPSENSE_DEVICE_API_KEY === "") {
    error_log("CropSense sensor API key is not configured.");
    save_response(["ok" => false, "error" => "Sensor API is not configured."], 503);
}

if (!cropsense_device_api_key_is_valid($payload)) {
    save_response(["ok" => false, "error" => "Unauthorized."], 401);
}

$required = ["device_id", "moisture", "temperature", "ec", "ph", "nitrogen", "phosphorus", "potassium"];
foreach ($required as $field) {
    if (!array_key_exists($field, $payload) || $payload[$field] === "" || $payload[$field] === null) {
        save_response(["ok" => false, "error" => "Missing required field: {$field}."], 422);
    }
}

$deviceCode = trim((string) $payload["device_id"]);
if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,49}$/', $deviceCode)) {
    save_response(["ok" => false, "error" => "device_id must be 1-50 letters, numbers, dots, dashes, or underscores."], 422);
}

$ranges = [
    "moisture" => [0, 100], "temperature" => [-40, 85], "ec" => [0, 65535],
    "ph" => [0, 14], "nitrogen" => [0, 65535],
    "phosphorus" => [0, 65535], "potassium" => [0, 65535],
];
$values = [];
foreach ($ranges as $field => [$min, $max]) {
    if (!is_numeric($payload[$field]) || !is_finite((float) $payload[$field])) {
        save_response(["ok" => false, "error" => "{$field} must be numeric."], 422);
    }
    $values[$field] = (float) $payload[$field];
    if ($values[$field] < $min || $values[$field] > $max) {
        save_response(["ok" => false, "error" => "{$field} is outside the accepted range."], 422);
    }
}

$deviceName = substr(trim((string) ($payload["device_name"] ?? $deviceCode)), 0, 100);
$location = substr(trim((string) ($payload["location"] ?? "Field Station")), 0, 150);

$conn->begin_transaction();
try {
    $device = $conn->prepare("SELECT id FROM devices WHERE device_code = ? LIMIT 1");
    $device->bind_param("s", $deviceCode);
    $device->execute();
    $row = $device->get_result()->fetch_assoc();
    $device->close();

    if ($row) {
        $deviceId = (int) $row["id"];
        $update = $conn->prepare("UPDATE devices SET device_name = ?, location = ?, status = 'Online', last_seen = NOW() WHERE id = ?");
        $update->bind_param("ssi", $deviceName, $location, $deviceId);
        $update->execute();
        $update->close();
    } else {
        $insertDevice = $conn->prepare("INSERT INTO devices (device_name, device_code, location, status, last_seen) VALUES (?, ?, ?, 'Online', NOW())");
        $insertDevice->bind_param("sss", $deviceName, $deviceCode, $location);
        $insertDevice->execute();
        $deviceId = (int) $conn->insert_id;
        $insertDevice->close();
    }

    $farmResult = $conn->query("SELECT id FROM farms ORDER BY id LIMIT 1");
    $farm = $farmResult ? $farmResult->fetch_assoc() : null;
    if ($farm) {
        $farmId = (int) $farm["id"];
    } else {
        $farmName = "CropSense Sensor Farm";
        $ownerName = "CropSense User";
        $barangay = "Field Station";
        $municipality = "Echague";
        $province = "Isabela";
        $areaHectares = 1.0;
        $soilType = "Unspecified";
        $remarks = "Automatically created for incoming ESP32 sensor readings";
        $insertFarm = $conn->prepare("INSERT INTO farms (farm_name, owner_name, barangay, municipality, province, area_hectares, soil_type, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$insertFarm) {
            throw new RuntimeException("Unable to prepare default farm insert.");
        }
        $insertFarm->bind_param("sssssdss", $farmName, $ownerName, $barangay, $municipality, $province, $areaHectares, $soilType, $remarks);
        if (!$insertFarm->execute()) {
            throw new RuntimeException("Unable to create default farm.");
        }
        $farmId = (int) $conn->insert_id;
        $insertFarm->close();
    }
    $humidity = 0.0;
    $moisture = $values["moisture"];
    $ph = $values["ph"];
    $ec = $values["ec"];
    $nitrogen = $values["nitrogen"];
    $phosphorus = $values["phosphorus"];
    $potassium = $values["potassium"];
    $temperature = $values["temperature"];
    $reading = $conn->prepare("INSERT INTO sensor_readings (device_id, farm_id, soil_moisture, soil_ph, soil_ec, nitrogen, phosphorus, potassium, temperature, humidity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$reading) {
        throw new RuntimeException("Unable to prepare reading insert.");
    }
    $reading->bind_param("iidddddddd", $deviceId, $farmId, $moisture, $ph, $ec, $nitrogen, $phosphorus, $potassium, $temperature, $humidity);
    if (!$reading->execute()) {
        throw new RuntimeException("Unable to execute reading insert.");
    }
    $readingId = (int) $conn->insert_id;
    $reading->close();
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    error_log("CropSense sensor insert failed: " . $error->getMessage());
    save_response(["ok" => false, "error" => "Unable to store sensor reading."], 500);
}

save_response(["ok" => true, "message" => "Sensor reading saved.", "id" => $readingId], 201);
