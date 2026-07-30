<?php

if (!function_exists("cropsense_relative_age")) {
    function cropsense_relative_age($seconds)
    {
        if ($seconds === null) {
            return "No recent reading";
        }

        $seconds = max(0, (int) $seconds);

        if ($seconds < 60) {
            return "just now";
        }

        if ($seconds < 3600) {
            return floor($seconds / 60) . " min ago";
        }

        if ($seconds < 86400) {
            return floor($seconds / 3600) . " hr ago";
        }

        return floor($seconds / 86400) . " days ago";
    }
}

if (!function_exists("cropsense_sensor_state")) {
    function cropsense_sensor_state($ageSeconds, $hasReading)
    {
        if (!$hasReading || $ageSeconds === null || $ageSeconds > 60) {
            return ["state" => "offline", "label" => "Offline"];
        }

        if ($ageSeconds > 30) {
            return ["state" => "stale", "label" => "Stale"];
        }

        return ["state" => "online", "label" => "Online"];
    }
}

if (!function_exists("cropsense_validate_sensor_reading")) {
    function cropsense_validate_sensor_reading($reading)
    {
        $errors = [];

        if (!$reading) {
            return $errors;
        }

        $ranges = [
            "soil_ph" => [0, 14, "pH below 0 or above 14"],
            "soil_moisture" => [0, 100, "Moisture below 0% or above 100%"],
            "temperature" => [-20, 80, "Temperature outside accepted sensor range"],
            "soil_ec" => [0, null, "Negative EC value"],
            "nitrogen" => [0, null, "Negative nitrogen value"],
            "phosphorus" => [0, null, "Negative phosphorus value"],
            "potassium" => [0, null, "Negative potassium value"],
        ];

        foreach ($ranges as $key => $range) {
            if (!isset($reading[$key]) || $reading[$key] === "" || !is_numeric($reading[$key])) {
                continue;
            }

            $value = (float) $reading[$key];
            $min = $range[0];
            $max = $range[1];

            if ($value < $min || ($max !== null && $value > $max)) {
                $errors[$key] = $range[2];
            }
        }

        return $errors;
    }
}

if (!function_exists("cropsense_latest_sensor_reading")) {
    function cropsense_latest_sensor_reading($connection)
    {
        $emptyState = cropsense_sensor_state(null, false);
        $result = [
            "reading" => null,
            "age_seconds" => null,
            "age_label" => "No recent reading",
            "device_status" => $emptyState["label"],
            "device_state" => $emptyState["state"],
            "is_valid" => true,
            "validation_errors" => [],
            "columns" => [
                "temperature" => "sensor_readings.temperature",
                "soil_moisture" => "sensor_readings.soil_moisture",
                "soil_ph" => "sensor_readings.soil_ph",
                "soil_ec" => "sensor_readings.soil_ec",
                "nitrogen" => "sensor_readings.nitrogen",
                "phosphorus" => "sensor_readings.phosphorus",
                "potassium" => "sensor_readings.potassium",
                "timestamp" => "sensor_readings.created_at",
                "device_name" => "devices.device_name",
                "farm_name" => "farms.farm_name",
            ],
        ];

        if (!$connection instanceof mysqli || $connection->connect_error) {
            error_log("CropSense latest sensor lookup skipped: database is unavailable.");
            return $result;
        }

        $sql = "
            SELECT
                sr.id,
                sr.device_id,
                sr.farm_id,
                sr.soil_moisture,
                sr.soil_ph,
                sr.soil_ec,
                sr.nitrogen,
                sr.phosphorus,
                sr.potassium,
                sr.temperature,
                sr.humidity,
                sr.created_at,
                TIMESTAMPDIFF(SECOND, sr.created_at, NOW()) AS age_seconds,
                d.device_name,
                d.device_code,
                d.status AS device_table_status,
                d.last_seen,
                f.farm_name
            FROM sensor_readings sr
            LEFT JOIN devices d ON d.id = sr.device_id
            LEFT JOIN farms f ON f.id = sr.farm_id
            ORDER BY sr.created_at DESC, sr.id DESC
            LIMIT 1
        ";

        $stmt = $connection->prepare($sql);

        if (!$stmt) {
            error_log("CropSense latest sensor prepare failed: " . $connection->error);
            return $result;
        }

        if (!$stmt->execute()) {
            error_log("CropSense latest sensor execute failed: " . $stmt->error);
            $stmt->close();
            return $result;
        }

        $queryResult = $stmt->get_result();
        $reading = $queryResult ? $queryResult->fetch_assoc() : null;
        $stmt->close();

        if (!$reading) {
            return $result;
        }

        $ageSeconds = isset($reading["age_seconds"]) ? (int) $reading["age_seconds"] : null;
        $state = cropsense_sensor_state($ageSeconds, true);
        $validationErrors = cropsense_validate_sensor_reading($reading);

        if ($validationErrors) {
            $state = ["state" => "invalid", "label" => "Invalid sensor reading"];
        }

        $result["reading"] = $reading;
        $result["age_seconds"] = $ageSeconds;
        $result["age_label"] = cropsense_relative_age($ageSeconds);
        $result["device_status"] = $state["label"];
        $result["device_state"] = $state["state"];
        $result["is_valid"] = count($validationErrors) === 0;
        $result["validation_errors"] = $validationErrors;

        return $result;
    }
}
