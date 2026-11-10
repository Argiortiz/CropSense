<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/roles.php";
require_once __DIR__ . "/includes/device_status.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/latest_sensor.php";
require_once __DIR__ . "/includes/crop_suitability.php";

date_default_timezone_set("Asia/Manila");

/* =========================================================
   SENSOR DATA
========================================================= */

$sensorSnapshot = cropsense_latest_sensor_reading($conn);

$latestSensorReading =
    !empty($sensorSnapshot["is_valid"])
        ? ($sensorSnapshot["reading"] ?? null)
        : null;

$sensorAgeSeconds = $sensorSnapshot["age_seconds"] ?? null;
$sensorStatusLabel = $sensorSnapshot["device_status"] ?? "Offline";
$sensorIsLive = ($sensorSnapshot["device_state"] ?? "") === "online";

/* =========================================================
   CROPS
========================================================= */

$cropConfig = cropsense_crop_config();

$dashboardCrops = array_intersect_key(
    $cropConfig["crops"] ?? [],
    array_flip(["rice", "corn", "tobacco"])
);

$dashboardNpkClassification =
    $cropConfig["npk_classification"] ?? [];

$dashboardSuitability =
    cropsense_assess_crop_suitability(
        $conn,
        $latestSensorReading
    );

$cropIcons = [
    "rice" => "bi-droplet-half",
    "corn" => "bi-flower1",
    "tobacco" => "bi-wind"
];

/* =========================================================
   HELPERS
========================================================= */

function dashboard_value($reading, $key)
{
    if (
        !is_array($reading) ||
        !array_key_exists($key, $reading) ||
        $reading[$key] === null ||
        $reading[$key] === "" ||
        !is_numeric($reading[$key])
    ) {
        return null;
    }

    return (float) $reading[$key];
}

function dashboard_display($reading, $key, $decimals = 1)
{
    $value = dashboard_value($reading, $key);

    if ($value === null) {
        return "--";
    }

    return number_format(
        $value,
        $decimals,
        ".",
        ""
    );
}

function dashboard_raw($reading, $key)
{
    if (
        !is_array($reading) ||
        !array_key_exists($key, $reading) ||
        $reading[$key] === null ||
        $reading[$key] === ""
    ) {
        return "--";
    }

    return (string) $reading[$key];
}

function dashboard_age($seconds)
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

    return floor($seconds / 3600) . " hr ago";
}

/* =========================================================
   VALUES
========================================================= */

$moisture = dashboard_display(
    $latestSensorReading,
    "soil_moisture",
    1
);

$soilPh = dashboard_display(
    $latestSensorReading,
    "soil_ph",
    1
);

$soilTemperature = dashboard_display(
    $latestSensorReading,
    "temperature",
    1
);

$soilEc = dashboard_display(
    $latestSensorReading,
    "soil_ec",
    0
);

$nitrogen = dashboard_raw(
    $latestSensorReading,
    "nitrogen"
);

$phosphorus = dashboard_raw(
    $latestSensorReading,
    "phosphorus"
);

$potassium = dashboard_raw(
    $latestSensorReading,
    "potassium"
);

$readingTime = dashboard_age(
    $sensorAgeSeconds
);

/* =========================================================
   PAGE
========================================================= */

$hour = (int) date("G");

$greeting =
    $hour < 12
        ? "Good morning"
        : (
            $hour < 18
                ? "Good afternoon"
                : "Good evening"
        );

$pageTitle = "Live Monitoring";
$bodyExtraClass = "dashboard-hero-enhanced";

include __DIR__ . "/includes/header.php";

?>

<div class="dashboard-shell">

    <?php
    include __DIR__ . "/includes/sidebar.php";
    ?>

    <main class="dashboard-main">

        <?php
        include __DIR__ . "/includes/navbar.php";
        ?>

        <!-- =================================================
             HERO
        ================================================== -->

        <section class="dashboard-hero field-command-hero classic-command-hero">

            <div class="hero-copy">

                <div class="hero-eyebrow-row">
                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        Live Monitoring
                    </span>
                </div>

                <h2>
                    <?php
                    echo htmlspecialchars($greeting);
                    ?>,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["fullname"] ??
                        "Administrator"
                    );
                    ?>!
                </h2>

                <p class="hero-lead">
                    Your farm conditions,
                    beautifully in focus.
                </p>

                <p class="hero-description">
                    Monitor soil conditions,
                    nutrients and crop suitability
                    from your CropSense dashboard.
                </p>

                <div class="hero-status-strip">

                    <span>
                        <i class="bi bi-calendar3"></i>
                        <?php echo date("F j, Y"); ?>
                    </span>

                    <span>
                        <i class="bi bi-clock"></i>
                        <?php echo date("g:i A"); ?>
                    </span>

                    <span
                        class="hero-live-state
                        <?php
                        echo $sensorIsLive
                            ? "is-live"
                            : "is-offline";
                        ?>"
                    >
                        <i></i>

                        <b>
                            <?php
                            echo htmlspecialchars(
                                $sensorStatusLabel
                            );
                            ?>
                        </b>
                    </span>

                </div>

                <div class="hero-metrics">

                    <div>
                        <strong>7</strong>
                        <span>Soil Parameters</span>
                    </div>

                    <div>
                        <strong>
                            <?php
                            echo $sensorIsLive
                                ? "1"
                                : "0";
                            ?>
                        </strong>
                        <span>Device Online</span>
                    </div>

                    <div>
                        <strong>2.0</strong>
                        <span>CropSense Version</span>
                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             SENSOR VALUES
        ================================================== -->

        <section
            class="sensor-grid sensor-grid--soil"
            data-live-dashboard
            aria-label="Live soil sensor readings"
        >

            <!-- NPK -->

            <article
                class="sensor-card npk nutrient-card"
            >

                <div class="sensor-icon">
                    <i class="bi bi-gem"></i>
                </div>

                <div class="sensor-card-content">

                    <span>NPK Sensor</span>

                    <strong data-reading="npk">
                        <?php
                        echo htmlspecialchars(
                            $nitrogen .
                            " / " .
                            $phosphorus .
                            " / " .
                            $potassium
                        );
                        ?>
                    </strong>

                    <small>
                        N / P / K mg/kg —
                        <?php
                        echo htmlspecialchars(
                            $readingTime
                        );
                        ?>
                    </small>

                    <div class="nutrient-values">

                        <div class="nutrient-reading">
                            <b>Nitrogen</b>

                            <span class="nutrient-measurement">
                                <output
                                    data-reading="nitrogen"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $nitrogen
                                    );
                                    ?>
                                </output>

                                <small>
                                    mg/kg
                                </small>
                            </span>
                        </div>

                        <div class="nutrient-reading">
                            <b>Phosphorus</b>

                            <span class="nutrient-measurement">
                                <output
                                    data-reading="phosphorus"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $phosphorus
                                    );
                                    ?>
                                </output>

                                <small>
                                    mg/kg
                                </small>
                            </span>
                        </div>

                        <div class="nutrient-reading">
                            <b>Potassium</b>

                            <span class="nutrient-measurement">
                                <output
                                    data-reading="potassium"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $potassium
                                    );
                                    ?>
                                </output>

                                <small>
                                    mg/kg
                                </small>
                            </span>
                        </div>

                    </div>

                </div>

            </article>


            <!-- MOISTURE -->

            <article class="sensor-card moisture">

                <div class="sensor-icon">
                    <i class="bi bi-droplet-half"></i>
                </div>

                <div>

                    <span>Soil Moisture</span>

                    <strong>
                        <output
                            data-reading="soil_moisture"
                        >
                            <?php
                            echo htmlspecialchars(
                                $moisture
                            );
                            ?>
                        </output>
                        %
                    </strong>

                    <small>
                        <?php
                        echo htmlspecialchars(
                            $readingTime
                        );
                        ?>
                    </small>

                </div>

            </article>


            <!-- PH -->

            <article class="sensor-card ph">

                <div class="sensor-icon">
                    <i class="bi bi-eyedropper"></i>
                </div>

                <div>

                    <span>Soil pH</span>

                    <strong
                        data-reading="soil_ph"
                    >
                        <?php
                        echo htmlspecialchars(
                            $soilPh
                        );
                        ?>
                    </strong>

                    <small>
                        <?php
                        echo htmlspecialchars(
                            $readingTime
                        );
                        ?>
                    </small>

                </div>

            </article>


            <!-- TEMPERATURE -->

            <article class="sensor-card temperature">

                <div class="sensor-icon">
                    <i class="bi bi-thermometer-sun"></i>
                </div>

                <div>

                    <span>Soil Temperature</span>

                    <strong
                        data-reading="temperature"
                    >
                        <?php
                        echo htmlspecialchars(
                            $soilTemperature
                        );
                        ?>
                        °C
                    </strong>

                    <small>
                        <?php
                        echo htmlspecialchars(
                            $readingTime
                        );
                        ?>
                    </small>

                </div>

            </article>


            <!-- EC -->

            <article class="sensor-card ec">

                <div class="sensor-icon">
                    <i class="bi bi-lightning-charge"></i>
                </div>

                <div>

                    <span>Soil EC</span>

                    <strong
                        data-reading="soil_ec"
                    >
                        <?php
                        echo htmlspecialchars(
                            $soilEc
                        );
                        ?>
                    </strong>

                    <small>
                        µS/cm —
                        <?php
                        echo htmlspecialchars(
                            $readingTime
                        );
                        ?>
                    </small>

                </div>

            </article>

        </section>


        <!-- =================================================
             CROP SUITABILITY
        ================================================== -->

        <section class="dashboard-content">

            <?php
            $cropRecommendations = is_array($dashboardSuitability["recommendations"] ?? null)
                ? $dashboardSuitability["recommendations"]
                : [];

            usort(
                $cropRecommendations,
                static function ($a, $b) {
                    $rankA = isset($a["rank"]) ? (int) $a["rank"] : PHP_INT_MAX;
                    $rankB = isset($b["rank"]) ? (int) $b["rank"] : PHP_INT_MAX;

                    if ($rankA !== $rankB) {
                        return $rankA <=> $rankB;
                    }

                    $percentageA = isset($a["percentage"]) && is_numeric($a["percentage"])
                        ? (float) $a["percentage"]
                        : -1;
                    $percentageB = isset($b["percentage"]) && is_numeric($b["percentage"])
                        ? (float) $b["percentage"]
                        : -1;

                    return $percentageB <=> $percentageA;
                }
            );

            $topCrop = $cropRecommendations[0] ?? null;

            $topClass = strtoupper((string) ($topCrop["final_class"] ?? "NA"));
            $topLabel = (string) ($topCrop["final_label"] ?? "Insufficient Data");
            $topClassCss = in_array(strtolower($topClass), ["s1", "s2", "s3", "n"], true)
                ? "is-" . strtolower($topClass)
                : "is-na";

            $topPercentage = isset($topCrop["percentage"]) && is_numeric($topCrop["percentage"])
                ? number_format((float) $topCrop["percentage"], 1) . "%"
                : "--";
            ?>

            <article class="recommendation-panel crop-assessment-panel">

                <div class="section-heading">
                    <div>
                        <span>Sensor-Based Assessment</span>
                        <h3>Crop Suitability</h3>
                    </div>

                    <i class="bi bi-clipboard2-pulse"></i>
                </div>


                <!-- TOP CROP SUMMARY -->
                <div class="crop-assessment-summary">

                    <div class="match-summary">
                        <span>Top Sensor-Based Score</span>

                        <strong data-recommendation-score>
                            <?php echo htmlspecialchars($topPercentage, ENT_QUOTES, "UTF-8"); ?>
                        </strong>

                        <span
                            class="suitability-badge <?php echo htmlspecialchars($topClassCss, ENT_QUOTES, "UTF-8"); ?>"
                            data-recommendation-class
                        >
                            <?php echo htmlspecialchars($topLabel, ENT_QUOTES, "UTF-8"); ?>
                        </span>
                    </div>


                    <div class="crop-assessment-summary-copy">

                        <span>Highest Suitability Score</span>

                        <h4 data-recommendation-title>
                            <?php if ($topCrop) : ?>
                                <?php
                                echo htmlspecialchars(
                                    ($topCrop["crop"] ?? "Crop") . " — " . $topLabel,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            <?php else : ?>
                                Waiting for assessment
                            <?php endif; ?>
                        </h4>

                        <p data-recommendation-description>
                            <?php if (!$topCrop) : ?>
                                Waiting for a valid soil sensor reading.
                            <?php elseif (in_array($topClass, ["S1", "S2"], true)) : ?>
                                Recommended for the current measured soil condition.
                            <?php else : ?>
                                No crop currently reaches the Suitable classification for the measured soil condition.
                            <?php endif; ?>
                        </p>

                    </div>

                </div>


                <!-- SCORING METHOD -->
                <div class="assessment-method-note">

                    <i class="bi bi-info-circle"></i>

                    <div>
                        <strong>CropSense scoring</strong>

                        <p>
                            Recommended range = 2 points; near range = 1 point; outside range = 0 points.
                            Seven measured soil parameters give a maximum score of 14.
                            Highly Suitable: 85-100%; Suitable: 70-84.99%;
                            Marginally Suitable: 50-69.99%; Not Suitable: below 50%.
                        </p>
                    </div>

                </div>


                <!-- RICE / CORN / TOBACCO CARDS -->
                <div class="crop-assessment-list" data-crop-assessment-list>

                    <?php if (empty($cropRecommendations)) : ?>

                        <div class="crop-assessment-empty">
                            No crop assessment is currently available.
                        </div>

                    <?php else : ?>

                        <?php foreach ($cropRecommendations as $index => $crop) : ?>

                            <?php
                            $cropKey = strtolower((string) ($crop["crop_key"] ?? ""));
                            $cropName = (string) ($crop["crop"] ?? ucfirst($cropKey));
                            $scientificName = (string) ($crop["scientific_name"] ?? "");

                            $rank = isset($crop["rank"])
                                ? (int) $crop["rank"]
                                : ($index + 1);

                            $percentage = isset($crop["percentage"]) && is_numeric($crop["percentage"])
                                ? number_format((float) $crop["percentage"], 1) . "%"
                                : "--";

                            $score = $crop["score"] ?? "--";
                            $maximumScore = $crop["maximum_score"] ?? "--";
                            $assessed = $crop["assessed_parameters"] ?? 0;
                            $totalParameters = $crop["total_parameters"] ?? 7;

                            $finalClass = strtoupper((string) ($crop["final_class"] ?? "NA"));
                            $finalLabel = (string) ($crop["final_label"] ?? "Insufficient Data");

                            $classCode = strtolower($finalClass);
                            $classCss = in_array($classCode, ["s1", "s2", "s3", "n"], true)
                                ? "is-" . $classCode
                                : "is-na";

                            $matchExplanation = (string) ($crop["match_explanation"] ?? "");
                            $limitingFactor = (string) (
                                $crop["limiting_factor_reason"]
                                ?? ($crop["limiting_factors"][0]["guidance"] ?? "Not available")
                            );

                            $missingParameters = is_array($crop["missing_parameters"] ?? null)
                                ? $crop["missing_parameters"]
                                : [];

                            $icon = $cropIcons[$cropKey] ?? "bi-flower2";
                            ?>

                            <details
                                class="crop-assessment-card <?php echo htmlspecialchars($classCss, ENT_QUOTES, "UTF-8"); ?>"
                                data-crop-assessment="<?php echo htmlspecialchars($cropKey, ENT_QUOTES, "UTF-8"); ?>"
                                open
                            >

                                <summary>

                                    <span class="crop-assessment-rank" data-crop-rank>
                                        #<?php echo $rank; ?>
                                    </span>

                                    <span class="crop-assessment-icon">
                                        <i
                                            class="bi <?php echo htmlspecialchars($icon, ENT_QUOTES, "UTF-8"); ?>"
                                        ></i>
                                    </span>

                                    <span class="crop-assessment-name">
                                        <strong>
                                            <?php echo htmlspecialchars($cropName, ENT_QUOTES, "UTF-8"); ?>
                                        </strong>

                                        <small>
                                            <?php echo htmlspecialchars($scientificName, ENT_QUOTES, "UTF-8"); ?>
                                        </small>
                                    </span>

                                    <strong
                                        class="crop-assessment-percentage"
                                        data-crop-percentage
                                    >
                                        <?php echo htmlspecialchars($percentage, ENT_QUOTES, "UTF-8"); ?>
                                    </strong>

                                    <span
                                        class="suitability-badge <?php echo htmlspecialchars($classCss, ENT_QUOTES, "UTF-8"); ?>"
                                        data-crop-final-badge
                                    >
                                        <?php echo htmlspecialchars($finalLabel, ENT_QUOTES, "UTF-8"); ?>
                                    </span>

                                    <i
                                        class="bi bi-chevron-down crop-assessment-chevron"
                                        aria-hidden="true"
                                    ></i>

                                </summary>


                                <div class="crop-assessment-details">

                                    <p data-crop-recommended>
                                        <?php
                                        if (
                                            $index === 0
                                            && in_array($finalClass, ["S1", "S2"], true)
                                        ) {
                                            echo "Recommended for current soil condition.";
                                        }
                                        ?>
                                    </p>

                                    <p data-crop-match>
                                        <?php
                                        echo htmlspecialchars(
                                            $matchExplanation,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </p>

                                    <p data-crop-missing>
                                        <?php
                                        if ($missingParameters) {
                                            echo htmlspecialchars(
                                                "NO DATA: "
                                                . implode(", ", $missingParameters)
                                                . ". Score uses "
                                                . $assessed
                                                . " of "
                                                . $totalParameters
                                                . " parameters.",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                        }
                                        ?>
                                    </p>


                                    <div class="crop-assessment-metrics">

                                        <div>
                                            <span>Score</span>

                                            <strong data-crop-score>
                                                <?php
                                                echo htmlspecialchars(
                                                    $score . " / " . $maximumScore,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                                ?>
                                            </strong>
                                        </div>


                                        <div>
                                            <span>Final Class</span>

                                            <strong data-crop-final-class>
                                                <?php
                                                echo htmlspecialchars(
                                                    $finalLabel,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                                ?>
                                            </strong>
                                        </div>


                                        <div>
                                            <span>Parameters Assessed</span>

                                            <strong data-crop-assessed>
                                                <?php
                                                echo htmlspecialchars(
                                                    $assessed . " of " . $totalParameters,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                                ?>
                                            </strong>
                                        </div>


                                        <div class="crop-assessment-metric--limiting">
                                            <span>Main Limiting Factor</span>

                                            <strong data-crop-limiting-factor>
                                                <?php
                                                echo htmlspecialchars(
                                                    $limitingFactor,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                                ?>
                                            </strong>
                                        </div>

                                    </div>

                                </div>

                            </details>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>


                <aside class="crop-assessment-disclaimer">
                    <i class="bi bi-shield-check"></i>

                    <p>
                        CropSense recommendations are based on soil parameters measured by the connected sensor.
                        Final crop selection should also consider soil texture, soil depth, drainage, flooding risk,
                        slope, climate, water availability, crop variety, and professional agronomic assessment.
                    </p>
                </aside>

            </article>


            <!-- =================================================
                 QUICK ACTIONS
            ================================================== -->

            <article class="quick-actions">

                <div class="section-heading">
                    <div>
                        <span>Tools</span>
                        <h3>Quick Actions</h3>
                    </div>
                </div>


                <a
                    href="<?php
                    echo htmlspecialchars(
                        cropsense_url(
                            "collected_data.php"
                        )
                    );
                    ?>"
                >
                    <i class="bi bi-table"></i>
                    View Collected Data
                </a>


                <a
                    href="<?php
                    echo htmlspecialchars(
                        cropsense_url(
                            "export_collected_data.php"
                        )
                    );
                    ?>"
                >
                    <i
                        class="bi bi-file-earmark-excel"
                    ></i>
                    Download Data
                </a>

            </article>

        </section>

    </main>

</div>


<!-- =====================================================
     INITIAL CROP DATA FOR JAVASCRIPT
===================================================== -->

<script
    type="application/json"
    data-initial-crop-suitability
>
<?php

echo json_encode(
    $dashboardSuitability,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_PRESERVE_ZERO_FRACTION
);

?>
</script>


<script
    type="application/json"
    data-npk-classification
>
<?php

echo json_encode(
    $dashboardNpkClassification,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_PRESERVE_ZERO_FRACTION
);

?>
</script>


<script
    src="<?php
    echo htmlspecialchars(
        cropsense_asset(
            "js/dashboard.js"
        )
    );
    ?>?v=20260906-live-values-final-2"
></script>


<?php

include __DIR__ . "/includes/footer.php";

?>