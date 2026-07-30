<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$requiredTables = [
    'users' => ['id', 'fullname', 'username', 'email', 'password', 'role', 'status', 'created_at'],
    'farms' => ['id', 'farm_name', 'owner_name', 'barangay', 'municipality', 'province', 'area_hectares', 'created_at'],
    'devices' => ['id', 'device_name', 'device_code', 'location', 'status', 'last_seen', 'created_at'],
    'sensor_readings' => ['id', 'device_id', 'farm_id', 'soil_moisture', 'soil_ph', 'soil_ec', 'nitrogen', 'phosphorus', 'potassium', 'temperature', 'humidity', 'created_at'],
    'recommendations' => ['id', 'reading_id', 'recommended_crop', 'suitability', 'remarks', 'created_at'],
    'audit_logs' => ['id', 'user_id', 'activity', 'ip_address', 'created_at'],
    'crop_thresholds' => ['id', 'crop_name', 'ph_min', 'ph_max', 'moisture_min', 'moisture_max', 'temperature_min', 'temperature_max', 'humidity_min', 'humidity_max', 'nitrogen_min', 'nitrogen_max', 'phosphorus_min', 'phosphorus_max', 'potassium_min', 'potassium_max', 'description', 'created_at'],
];

echo "CropSense database connection: OK\n";
echo "Database: " . $conn->real_escape_string($dbname ?? '') . "\n";

$hasErrors = false;

foreach ($requiredTables as $table => $requiredColumns) {
    $escapedTable = $conn->real_escape_string($table);
    $tableResult = $conn->query("SHOW TABLES LIKE '{$escapedTable}'");

    if (!$tableResult || $tableResult->num_rows === 0) {
        echo "[MISSING] table {$table}\n";
        $hasErrors = true;
        continue;
    }

    $columnResult = $conn->query("SHOW COLUMNS FROM `{$escapedTable}`");
    $actualColumns = [];

    if ($columnResult) {
        while ($column = $columnResult->fetch_assoc()) {
            $actualColumns[] = $column['Field'];
        }
    }

    $missingColumns = array_diff($requiredColumns, $actualColumns);

    if ($missingColumns) {
        echo "[MISSING] {$table} columns: " . implode(', ', $missingColumns) . "\n";
        $hasErrors = true;
        continue;
    }

    echo "[OK] {$table}\n";
}

exit($hasErrors ? 1 : 0);

