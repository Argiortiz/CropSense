<?php

return [
    "near_tolerance" => [
        "soil_ph" => 0.20,
        "temperature" => 2.00,
    ],
    "crops" => [
        "rice" => [
            "name" => "Rice",
            "scientific_name" => "Oryza sativa",
            "ph" => ["min" => 5.50, "max" => 6.50],
            "temperature" => ["min" => 26.00, "max" => 38.00, "unit" => "C"],
            "moisture_requirement" => "Flooded / standing water required",
            "nitrogen_requirement" => "80-120 kg/hectare",
            "phosphorus_requirement" => "20-40 kg/hectare",
            "potassium_requirement" => "20-40 kg/hectare",
            "ec_requirement" => "No approved EC range configured",
        ],
        "corn" => [
            "name" => "Corn",
            "scientific_name" => "Zea mays",
            "ph" => ["min" => 5.80, "max" => 7.00],
            "temperature" => ["min" => 24.00, "max" => 35.00, "unit" => "C"],
            "moisture_requirement" => "Moderate moisture",
            "nitrogen_requirement" => "120-180 kg/hectare",
            "phosphorus_requirement" => "40-60 kg/hectare",
            "potassium_requirement" => "40-60 kg/hectare",
            "ec_requirement" => "No approved EC range configured",
        ],
        "tobacco" => [
            "name" => "Tobacco",
            "scientific_name" => "Nicotiana tabacum",
            "ph" => ["min" => 6.00, "max" => 7.00],
            "temperature" => ["min" => 25.00, "max" => 32.00, "unit" => "C"],
            "moisture_requirement" => "Well-drained soil; avoid waterlogging",
            "nitrogen_requirement" => "50-80 kg/hectare",
            "phosphorus_requirement" => "20-40 kg/hectare",
            "potassium_requirement" => "20-40 kg/hectare",
            "ec_requirement" => "No approved EC range configured",
        ],
    ],
];

