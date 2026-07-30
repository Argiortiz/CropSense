<?php

if (!function_exists("cropsense_crop_config")) {
    function cropsense_crop_config()
    {
        $configPath = __DIR__ . "/../config/crop_thresholds.php";
        $config = is_file($configPath) ? require $configPath : [];

        return is_array($config) ? $config : ["near_tolerance" => [], "crops" => []];
    }
}

if (!function_exists("cropsense_number_value")) {
    function cropsense_number_value($reading, $key)
    {
        if (!$reading || !isset($reading[$key]) || $reading[$key] === "" || !is_numeric($reading[$key])) {
            return null;
        }

        return (float) $reading[$key];
    }
}

if (!function_exists("cropsense_format_number")) {
    function cropsense_format_number($value, $digits = 1)
    {
        if ($value === null || !is_numeric($value)) {
            return "No recent reading";
        }

        return number_format((float) $value, $digits);
    }
}

if (!function_exists("cropsense_range_text")) {
    function cropsense_range_text($range, $digits = 1, $unit = "")
    {
        if (!isset($range["min"], $range["max"])) {
            return "No approved range configured";
        }

        $text = number_format((float) $range["min"], $digits) . "-" . number_format((float) $range["max"], $digits);

        return trim($text . " " . $unit);
    }
}

if (!function_exists("cropsense_compare_direct_parameter")) {
    function cropsense_compare_direct_parameter($value, $range, $tolerance)
    {
        if ($value === null) {
            return ["status" => "no-reading", "label" => "No reading"];
        }

        $min = (float) ($range["min"] ?? 0);
        $max = (float) ($range["max"] ?? 0);

        if ($value >= $min && $value <= $max) {
            return ["status" => "suitable", "label" => "Suitable"];
        }

        if ($value >= ($min - $tolerance) && $value <= ($max + $tolerance)) {
            return ["status" => "near", "label" => "Near threshold"];
        }

        return ["status" => "not-suitable", "label" => "Not suitable"];
    }
}

if (!function_exists("cropsense_crop_result")) {
    function cropsense_crop_result($reading, $crop, $config)
    {
        $ph = cropsense_number_value($reading, "soil_ph");
        $temperature = cropsense_number_value($reading, "temperature");
        $phResult = cropsense_compare_direct_parameter(
            $ph,
            $crop["ph"],
            (float) ($config["near_tolerance"]["soil_ph"] ?? 0.2)
        );
        $temperatureResult = cropsense_compare_direct_parameter(
            $temperature,
            $crop["temperature"],
            (float) ($config["near_tolerance"]["temperature"] ?? 2.0)
        );

        $directStatuses = [$phResult["status"], $temperatureResult["status"]];

        if (in_array("not-suitable", $directStatuses, true)) {
            $overall = ["status" => "not-suitable", "label" => "Not Suitable"];
            $note = "pH or temperature is outside the approved direct-comparison range.";
        } elseif (in_array("near", $directStatuses, true)) {
            $overall = ["status" => "near", "label" => "Conditionally Suitable"];
            $note = "One direct parameter is near its threshold; field validation is recommended.";
        } elseif (in_array("no-reading", $directStatuses, true)) {
            $overall = ["status" => "no-reading", "label" => "Pending Full Validation"];
            $note = "No recent direct reading is available for pH or temperature.";
        } else {
            $overall = ["status" => "suitable", "label" => "Potentially Suitable"];
            $note = "pH and temperature pass; moisture and NPK still need compatible approved thresholds.";
        }

        return [
            "ph" => $phResult,
            "temperature" => $temperatureResult,
            "moisture" => ["status" => "validation", "label" => "Requires agronomist-validated threshold"],
            "npk" => ["status" => "validation", "label" => "Conversion/validation required"],
            "overall" => $overall,
            "note" => $note,
        ];
    }
}

if (!function_exists("cropsense_status_badge")) {
    function cropsense_status_badge($result)
    {
        $status = htmlspecialchars($result["status"] ?? "validation", ENT_QUOTES, "UTF-8");
        $label = htmlspecialchars($result["label"] ?? "Validation required", ENT_QUOTES, "UTF-8");

        return '<span class="fit-badge ' . $status . '">' . $label . '</span>';
    }
}

if (!function_exists("cropsense_requirement_with_badge")) {
    function cropsense_requirement_with_badge($requirement, $result = null)
    {
        $html = htmlspecialchars($requirement, ENT_QUOTES, "UTF-8");

        if ($result) {
            $html .= " " . cropsense_status_badge($result);
        }

        return $html;
    }
}

