<?php
// TEMPORARY CropSense dashboard include-by-include diagnostic.
// Delete immediately after diagnosis.

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "CropSense Dashboard Step Diagnostic\n";
echo "===================================\n";
echo "PHP: " . PHP_VERSION . "\n\n";

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        echo "\n--- FATAL / SHUTDOWN ERROR ---\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
    }
});

$steps = [
    "includes/session.php",
    "includes/roles.php",
    "includes/device_status.php",
    "config/database.php",
    "includes/latest_sensor.php",
    "includes/crop_suitability.php",
];

foreach ($steps as $file) {
    echo "[TEST] {$file} ... ";
    require_once __DIR__ . "/" . $file;
    echo "OK\n";
}

echo "\nAll dashboard dependencies loaded successfully.\n";
echo "Now loading dashboard.php itself...\n\n";

require __DIR__ . "/dashboard.php";
