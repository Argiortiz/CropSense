<?php
// TEMPORARY CropSense dashboard diagnostic.
// Delete this file immediately after capturing the error.

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "CropSense Dashboard Diagnostic\n";
echo "=============================\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "File: " . __FILE__ . "\n\n";

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        echo "\n\n--- SHUTDOWN ERROR ---\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
    }
});

echo "Loading dashboard.php...\n\n";
require __DIR__ . '/dashboard.php';
