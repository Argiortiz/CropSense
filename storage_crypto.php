<?php

/**
 * CropSense AES-256-GCM database-at-rest helper.
 *
 * The database key is deliberately separate from the ESP32 transport key.
 * Required config/security.local.php entry:
 *
 * 'database_aes_key_base64' => 'BASE64_OF_32_RANDOM_BYTES',
 */

if (!function_exists("cropsense_security_local_config")) {
    function cropsense_security_local_config()
    {
        static $config = null;

        if (is_array($config)) {
            return $config;
        }

        $path = __DIR__ . "/../config/security.local.php";
        if (!is_file($path)) {
            throw new RuntimeException("security.local.php is missing.");
        }

        $loaded = require $path;
        if (!is_array($loaded)) {
            throw new RuntimeException("security.local.php must return an array.");
        }

        $config = $loaded;
        return $config;
    }
}

if (!function_exists("cropsense_database_aes_key")) {
    function cropsense_database_aes_key()
    {
        $config = cropsense_security_local_config();
        $keyB64 = trim((string) ($config["database_aes_key_base64"] ?? ""));
        $key = base64_decode($keyB64, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException("Database AES key must decode to exactly 32 bytes.");
        }

        return $key;
    }
}

if (!function_exists("cropsense_transport_aes_key")) {
    function cropsense_transport_aes_key()
    {
        $config = cropsense_security_local_config();
        $keyB64 = trim((string) ($config["sensor_aes_key_base64"] ?? ""));
        $key = base64_decode($keyB64, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException("Transport AES key must decode to exactly 32 bytes.");
        }

        return $key;
    }
}

if (!function_exists("cropsense_storage_aad")) {
    function cropsense_storage_aad($deviceCode, $version = 1)
    {
        return "cropsense-db-v" . (int) $version . "|" . (string) $deviceCode;
    }
}

if (!function_exists("cropsense_sensor_storage_payload")) {
    function cropsense_sensor_storage_payload($values)
    {
        return [
            "moisture" => (float) $values["moisture"],
            "temperature" => (float) $values["temperature"],
            "ec" => (float) $values["ec"],
            "ph" => (float) $values["ph"],
            "nitrogen" => (float) $values["nitrogen"],
            "phosphorus" => (float) $values["phosphorus"],
            "potassium" => (float) $values["potassium"],
        ];
    }
}

if (!function_exists("cropsense_encrypt_sensor_for_database")) {
    function cropsense_encrypt_sensor_for_database($values, $deviceCode)
    {
        $version = 1;
        $plaintext = json_encode(
            cropsense_sensor_storage_payload($values),
            JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );

        if (!is_string($plaintext)) {
            throw new RuntimeException("Unable to serialize sensor data.");
        }

        $iv = random_bytes(12);
        $tag = "";

        $ciphertext = openssl_encrypt(
            $plaintext,
            "aes-256-gcm",
            cropsense_database_aes_key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            cropsense_storage_aad($deviceCode, $version),
            16
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new RuntimeException("AES-256-GCM database encryption failed.");
        }

        return [
            "encrypted_payload" => base64_encode($ciphertext),
            "encryption_iv" => base64_encode($iv),
            "encryption_tag" => base64_encode($tag),
            "encryption_version" => $version,
        ];
    }
}

if (!function_exists("cropsense_decrypt_sensor_from_database")) {
    function cropsense_decrypt_sensor_from_database($row)
    {
        $encryptedPayload = trim((string) ($row["encrypted_payload"] ?? ""));

        // Backward compatibility while old plaintext rows are being migrated.
        if ($encryptedPayload === "") {
            $row["storage_encrypted"] = false;
            return $row;
        }

        $deviceCode = trim((string) ($row["device_code"] ?? ""));
        if ($deviceCode === "") {
            throw new RuntimeException("device_code is required for AES database decryption.");
        }

        $version = (int) ($row["encryption_version"] ?? 1);
        $ciphertext = base64_decode($encryptedPayload, true);
        $iv = base64_decode((string) ($row["encryption_iv"] ?? ""), true);
        $tag = base64_decode((string) ($row["encryption_tag"] ?? ""), true);

        if ($ciphertext === false || $ciphertext === "") {
            throw new RuntimeException("Stored ciphertext is invalid.");
        }
        if ($iv === false || strlen($iv) !== 12) {
            throw new RuntimeException("Stored AES-GCM IV is invalid.");
        }
        if ($tag === false || strlen($tag) !== 16) {
            throw new RuntimeException("Stored AES-GCM authentication tag is invalid.");
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            "aes-256-gcm",
            cropsense_database_aes_key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            cropsense_storage_aad($deviceCode, $version)
        );

        if ($plaintext === false) {
            throw new RuntimeException("Stored AES-GCM authentication/decryption failed.");
        }

        $values = json_decode($plaintext, true);
        if (!is_array($values) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Decrypted database payload is invalid.");
        }

        foreach (["moisture", "temperature", "ec", "ph", "nitrogen", "phosphorus", "potassium"] as $field) {
            if (!array_key_exists($field, $values) || !is_numeric($values[$field])) {
                throw new RuntimeException("Decrypted sensor field is invalid: " . $field);
            }
        }

        // Restore the field names expected by the existing dashboard.
        $row["soil_moisture"] = (float) $values["moisture"];
        $row["temperature"] = (float) $values["temperature"];
        $row["soil_ec"] = (float) $values["ec"];
        $row["soil_ph"] = (float) $values["ph"];
        $row["nitrogen"] = (float) $values["nitrogen"];
        $row["phosphorus"] = (float) $values["phosphorus"];
        $row["potassium"] = (float) $values["potassium"];
        $row["humidity"] = isset($row["humidity"]) && is_numeric($row["humidity"])
            ? (float) $row["humidity"]
            : 0.0;
        $row["storage_encrypted"] = true;

        return $row;
    }
}

if (!function_exists("cropsense_validate_sensor_values")) {
    function cropsense_validate_sensor_values($payload, $responseFunction)
    {
        $required = ["device_id", "moisture", "temperature", "ec", "ph", "nitrogen", "phosphorus", "potassium"];

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === "" || $payload[$field] === null) {
                $responseFunction(["ok" => false, "error" => "Missing required field: {$field}."], 422);
            }
        }

        $deviceCode = trim((string) $payload["device_id"]);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,49}$/', $deviceCode)) {
            $responseFunction(
                ["ok" => false, "error" => "device_id must be 1-50 letters, numbers, dots, dashes, or underscores."],
                422
            );
        }

        $ranges = [
            "moisture" => [0, 100],
            "temperature" => [-40, 85],
            "ec" => [0, 65535],
            "ph" => [0, 14],
            "nitrogen" => [0, 65535],
            "phosphorus" => [0, 65535],
            "potassium" => [0, 65535],
        ];

        $values = [];
        foreach ($ranges as $field => $range) {
            if (!is_numeric($payload[$field]) || !is_finite((float) $payload[$field])) {
                $responseFunction(["ok" => false, "error" => "{$field} must be numeric."], 422);
            }

            $values[$field] = (float) $payload[$field];
            if ($values[$field] < $range[0] || $values[$field] > $range[1]) {
                $responseFunction(["ok" => false, "error" => "{$field} is outside the accepted range."], 422);
            }
        }

        return [$deviceCode, $values];
    }
}

if (!function_exists("cropsense_store_encrypted_reading")) {
    function cropsense_store_encrypted_reading($conn, $deviceCode, $deviceName, $location, $values)
    {
        $storage = cropsense_encrypt_sensor_for_database($values, $deviceCode);

        $conn->begin_transaction();

        try {
            $device = $conn->prepare("SELECT id FROM devices WHERE device_code = ? LIMIT 1");
            if (!$device) {
                throw new RuntimeException("Unable to prepare device lookup.");
            }

            $device->bind_param("s", $deviceCode);
            $device->execute();
            $row = $device->get_result()->fetch_assoc();
            $device->close();

            if ($row) {
                $deviceId = (int) $row["id"];
                $update = $conn->prepare(
                    "UPDATE devices
                     SET device_name = ?, location = ?, status = 'Online', last_seen = NOW()
                     WHERE id = ?"
                );
                if (!$update) {
                    throw new RuntimeException("Unable to prepare device update.");
                }
                $update->bind_param("ssi", $deviceName, $location, $deviceId);
                $update->execute();
                $update->close();
            } else {
                $insertDevice = $conn->prepare(
                    "INSERT INTO devices (device_name, device_code, location, status, last_seen)
                     VALUES (?, ?, ?, 'Online', NOW())"
                );
                if (!$insertDevice) {
                    throw new RuntimeException("Unable to prepare device insert.");
                }
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

                $insertFarm = $conn->prepare(
                    "INSERT INTO farms
                    (farm_name, owner_name, barangay, municipality, province, area_hectares, soil_type, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$insertFarm) {
                    throw new RuntimeException("Unable to prepare default farm insert.");
                }

                $insertFarm->bind_param(
                    "sssssdss",
                    $farmName,
                    $ownerName,
                    $barangay,
                    $municipality,
                    $province,
                    $areaHectares,
                    $soilType,
                    $remarks
                );

                if (!$insertFarm->execute()) {
                    throw new RuntimeException("Unable to create default farm.");
                }

                $farmId = (int) $conn->insert_id;
                $insertFarm->close();
            }

            $encryptedPayload = $storage["encrypted_payload"];
            $encryptionIv = $storage["encryption_iv"];
            $encryptionTag = $storage["encryption_tag"];
            $encryptionVersion = (int) $storage["encryption_version"];

            // Sensor measurement columns intentionally remain NULL.
            // The seven actual sensor values exist only inside encrypted_payload.
            $reading = $conn->prepare(
                "INSERT INTO sensor_readings
                (
                    device_id,
                    farm_id,
                    soil_moisture,
                    soil_ph,
                    soil_ec,
                    nitrogen,
                    phosphorus,
                    potassium,
                    temperature,
                    humidity,
                    encrypted_payload,
                    encryption_iv,
                    encryption_tag,
                    encryption_version,
                    created_at
                )
                VALUES
                (?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ?, ?, ?, ?, NOW())"
            );

            if (!$reading) {
                throw new RuntimeException("Unable to prepare encrypted reading insert.");
            }

            $reading->bind_param(
                "iisssi",
                $deviceId,
                $farmId,
                $encryptedPayload,
                $encryptionIv,
                $encryptionTag,
                $encryptionVersion
            );

            if (!$reading->execute()) {
                throw new RuntimeException("Unable to execute encrypted reading insert.");
            }

            $readingId = (int) $conn->insert_id;
            $reading->close();

            $conn->commit();

            return [
                "reading_id" => $readingId,
                "database_encrypted" => true,
                "encryption_version" => $encryptionVersion,
            ];
        } catch (Throwable $error) {
            $conn->rollback();
            throw $error;
        }
    }
}
