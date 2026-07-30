<?php
// Backward-compatible route. New integrations should use the explicit endpoints.
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "GET") {
    require __DIR__ . "/get_latest_reading.php";
}
require __DIR__ . "/save_sensor_reading.php";
