<?php
require_once __DIR__ . "/includes/security.php";
require_once __DIR__ . "/config/app_url.php";

cropsense_apply_security_headers("web");

$currentYear = date("Y");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CropSense combines IoT soil sensing, secure cloud data management, and crop suitability guidance for smarter agricultural decisions.">
    <meta name="theme-color" content="#0b3b2c">

    <title>CropSense | Smart Soil Monitoring and Crop Recommendations</title>

    <link rel="icon" href="<?php echo htmlspecialchars(cropsense_asset('img/cropsense-logo.svg'), ENT_QUOTES, 'UTF-8'); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cropsense_asset('css/bootstrap.min.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cropsense_asset('css/public.css'), ENT_QUOTES, 'UTF-8'); ?>?v=20260819-landing-v2">
</head>

<body class="public-site" id="home">
    <a class="skip-link" href="#mainContent">Skip to main content</a>

    <header class="public-header" data-public-header>
        <nav class="navbar navbar-expand-lg public-navbar" aria-label="Public navigation">
            <div class="container public-container">
                <a class="public-brand" href="#home" aria-label="CropSense home">
                    <img src="<?php echo htmlspecialchars(cropsense_asset('img/cropsense-logo.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="" width="44" height="44">
                    <span>
                        <strong>CropSense</strong>
                        <small>Smart Agriculture</small>
                    </span>
                </a>

                <button
                    class="navbar-toggler public-menu-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#publicNavigation"
                    aria-controls="publicNavigation"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="collapse navbar-collapse public-nav-collapse" id="publicNavigation">
                    <ul class="navbar-nav ms-auto public-nav-links">
                        <li class="nav-item"><a class="nav-link active" href="#home" aria-current="page">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                        <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                        <li class="nav-item"><a class="nav-link" href="#technology">Technology</a></li>
                        <li class="nav-item"><a class="nav-link" href="#project-purpose">About Project</a></li>
                    </ul>

                    <a class="public-sign-in" href="<?php echo htmlspecialchars(cropsense_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        Sign In
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main id="mainContent">
        <section class="public-hero" aria-labelledby="heroTitle">
            <div class="hero-grid-pattern" aria-hidden="true"></div>
            <div class="hero-glow hero-glow-one" aria-hidden="true"></div>
            <div class="hero-glow hero-glow-two" aria-hidden="true"></div>

            <div class="container public-container">
                <div class="row align-items-center gy-5 gx-xl-5">
                    <div class="col-lg-6">
                        <div class="hero-copy-public">
                            <p class="section-eyebrow hero-eyebrow">
                                <span></span>
                                IoT-powered agricultural decision support
                            </p>

                            <p class="hero-project-title">
                                CropSense: Smart Crop Recommendation and Monitoring System Using IoT Sensors
                            </p>

                            <h1 id="heroTitle">
                                Understand Your Soil.
                                <span>Grow With Confidence.</span>
                            </h1>

                            <p class="hero-summary">
                                CropSense combines IoT soil sensing, real-time monitoring,
                                historical data records, and crop suitability recommendations
                                to support smarter agricultural decisions.
                            </p>

                            <div class="hero-actions">
                                <a class="public-button public-button-primary" href="<?php echo htmlspecialchars(cropsense_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">
                                    Sign In to CropSense
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                                <a class="public-button public-button-secondary" href="#about">
                                    Explore the System
                                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                </a>
                            </div>

                            <ul class="hero-capabilities" aria-label="CropSense capabilities">
                                <li><i class="bi bi-broadcast-pin" aria-hidden="true"></i> IoT Monitoring</li>
                                <li><i class="bi bi-activity" aria-hidden="true"></i> Real-Time Soil Data</li>
                                <li><i class="bi bi-flower2" aria-hidden="true"></i> Crop Recommendations</li>
                                <li><i class="bi bi-cloud-check" aria-hidden="true"></i> Cloud Dashboard</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="system-visual" aria-labelledby="systemVisualTitle">
                            <div class="visual-topline">
                                <div>
                                    <span>CropSense architecture</span>
                                    <strong id="systemVisualTitle">Field-to-cloud workflow</strong>
                                </div>
                                <span class="visual-status"><i></i> Secure path</span>
                            </div>

                            <ol class="system-path">
                                <li>
                                    <span class="path-icon"><i class="bi bi-moisture" aria-hidden="true"></i></span>
                                    <div><small>01</small><strong>Soil Sensor</strong></div>
                                </li>
                                <li>
                                    <span class="path-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></span>
                                    <div><small>02</small><strong>MAX3485</strong></div>
                                </li>
                                <li>
                                    <span class="path-icon"><i class="bi bi-cpu" aria-hidden="true"></i></span>
                                    <div><small>03</small><strong>ESP32</strong></div>
                                </li>
                                <li>
                                    <span class="path-icon"><i class="bi bi-wifi" aria-hidden="true"></i></span>
                                    <div><small>04</small><strong>Wi-Fi</strong></div>
                                </li>
                                <li>
                                    <span class="path-icon"><i class="bi bi-braces" aria-hidden="true"></i></span>
                                    <div><small>05</small><strong>PHP API</strong></div>
                                </li>
                                <li>
                                    <span class="path-icon"><i class="bi bi-database" aria-hidden="true"></i></span>
                                    <div><small>06</small><strong>MySQL</strong></div>
                                </li>
                                <li>
                                    <span class="path-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
                                    <div><small>07</small><strong>Dashboard</strong></div>
                                </li>
                            </ol>

                            <div class="parameter-panel">
                                <div>
                                    <span>Supported field parameters</span>
                                    <small>Labels shown for system capability only</small>
                                </div>
                                <ul>
                                    <li>Soil Moisture</li>
                                    <li>Soil Temperature</li>
                                    <li>EC</li>
                                    <li>pH</li>
                                    <li>Nitrogen</li>
                                    <li>Phosphorus</li>
                                    <li>Potassium</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-proof" aria-label="CropSense system scope">
            <div class="container public-container">
                <div class="proof-grid">
                    <div>
                        <strong>7</strong>
                        <span>Measured soil parameters</span>
                    </div>
                    <div>
                        <strong>7</strong>
                        <span>Secure field-to-dashboard stages</span>
                    </div>
                    <div>
                        <strong>3</strong>
                        <span>Configured crop profiles</span>
                    </div>
                    <div>
                        <strong>Protected</strong>
                        <span>Authorized access to field data</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section about-section" id="about" aria-labelledby="aboutTitle">
            <div class="container public-container">
                <div class="section-intro section-intro-wide">
                    <p class="section-eyebrow"><span></span> About CropSense</p>
                    <h2 id="aboutTitle">From field conditions to clearer decisions.</h2>
                    <p>
                        CropSense turns a connected soil-sensing workflow into an accessible
                        monitoring experience for farmers, municipal agriculture teams,
                        instructors, evaluators, and authorized field personnel.
                    </p>
                </div>

                <div class="about-grid">
                    <article class="about-card">
                        <span class="public-card-number" aria-hidden="true">01</span>
                        <div class="public-icon"><i class="bi bi-moisture" aria-hidden="true"></i></div>
                        <h3>Smart Soil Monitoring</h3>
                        <p>
                            An RS485 multi-parameter soil sensor connects through a MAX3485
                            transceiver to an ESP32, collecting moisture, soil temperature, EC, pH,
                            nitrogen, phosphorus, and potassium.
                        </p>
                    </article>

                    <article class="about-card">
                        <span class="public-card-number" aria-hidden="true">02</span>
                        <div class="public-icon"><i class="bi bi-cloud-check" aria-hidden="true"></i></div>
                        <h3>Cloud Data Management</h3>
                        <p>
                            The ESP32 validates readings before transmitting them over HTTPS to
                            the native PHP API, where MySQL stores the field records securely.
                        </p>
                    </article>

                    <article class="about-card">
                        <span class="public-card-number" aria-hidden="true">03</span>
                        <div class="public-icon"><i class="bi bi-compass" aria-hidden="true"></i></div>
                        <h3>Decision Support</h3>
                        <p>
                            Stored readings support condition monitoring, time-based record review,
                            device awareness, reports, and configured crop suitability comparisons.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="public-section features-section" id="features" aria-labelledby="featuresTitle">
            <div class="container public-container">
                <div class="section-intro section-intro-row">
                    <div>
                        <p class="section-eyebrow"><span></span> Core capabilities</p>
                        <h2 id="featuresTitle">A practical toolkit for soil intelligence.</h2>
                    </div>
                    <p>
                        Each capability reflects the current CropSense system and remains available
                        only to authorized users after sign-in.
                    </p>
                </div>

                <div class="feature-grid-public">
                    <article class="feature-card-public feature-card-wide">
                        <div class="feature-card-top">
                            <div class="public-icon"><i class="bi bi-broadcast" aria-hidden="true"></i></div>
                            <span>Live</span>
                        </div>
                        <h3>Real-Time Soil Monitoring</h3>
                        <p>
                            View the latest validated field measurements in a protected dashboard
                            that refreshes without reloading the page.
                        </p>
                        <ul class="feature-parameters" aria-label="Monitored parameters">
                            <li>Soil Moisture</li>
                            <li>Soil Temperature</li>
                            <li>EC</li>
                            <li>pH</li>
                            <li>Nitrogen</li>
                            <li>Phosphorus</li>
                            <li>Potassium</li>
                        </ul>
                    </article>

                    <article class="feature-card-public">
                        <div class="public-icon"><i class="bi bi-flower2" aria-hidden="true"></i></div>
                        <h3>Crop Suitability Recommendation</h3>
                        <p>
                            Compares direct soil conditions with configured crop requirements and
                            clearly identifies matches, near thresholds, or conditions for review.
                        </p>
                    </article>

                    <article class="feature-card-public">
                        <div class="public-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></div>
                        <h3>Historical Record Review</h3>
                        <p>
                            Stores readings in MySQL and provides time filters so users can review
                            recent and long-term field records without exposing them publicly.
                        </p>
                    </article>

                    <article class="feature-card-public">
                        <div class="public-icon"><i class="bi bi-router" aria-hidden="true"></i></div>
                        <h3>Device Monitoring</h3>
                        <p>
                            Tracks the latest ESP32 communication and presents online, stale, and
                            offline states based on reading freshness.
                        </p>
                    </article>

                    <article class="feature-card-public">
                        <div class="public-icon"><i class="bi bi-shield-exclamation" aria-hidden="true"></i></div>
                        <h3>Alerts &amp; System Status</h3>
                        <p>
                            Surfaces stale data, offline devices, refresh problems, and invalid
                            sensor conditions so operators know when a reading needs attention.
                        </p>
                    </article>

                    <article class="feature-card-public">
                        <div class="public-icon"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i></div>
                        <h3>Reports &amp; Data Records</h3>
                        <p>
                            Provides a protected collected-data table and downloadable spreadsheet
                            export for authorized field reporting workflows.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="public-section workflow-section" id="how-it-works" aria-labelledby="workflowTitle">
            <div class="workflow-grid-pattern" aria-hidden="true"></div>
            <div class="container public-container">
                <div class="section-intro section-intro-centered section-intro-light">
                    <p class="section-eyebrow"><span></span> How CropSense works</p>
                    <h2 id="workflowTitle">One connected journey from soil to screen.</h2>
                    <p>
                        Measurements move through a seven-stage validation and storage workflow
                        before authorized users review them in CropSense.
                    </p>
                </div>

                <ol class="workflow-steps">
                    <li>
                        <span class="workflow-step-number">01</span>
                        <div class="workflow-icon"><i class="bi bi-moisture" aria-hidden="true"></i></div>
                        <h3>Soil Sensor</h3>
                        <p>Collects seven measured soil parameters from the field.</p>
                    </li>
                    <li>
                        <span class="workflow-step-number">02</span>
                        <div class="workflow-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
                        <h3>MAX3485</h3>
                        <p>Bridges the RS485 soil sensor signal to the ESP32 interface.</p>
                    </li>
                    <li>
                        <span class="workflow-step-number">03</span>
                        <div class="workflow-icon"><i class="bi bi-cpu" aria-hidden="true"></i></div>
                        <h3>ESP32</h3>
                        <p>Reads RS485 sensor registers using the Modbus RTU protocol.</p>
                    </li>
                    <li>
                        <span class="workflow-step-number">04</span>
                        <div class="workflow-icon"><i class="bi bi-wifi" aria-hidden="true"></i></div>
                        <h3>Wi-Fi</h3>
                        <p>Transmits only validated readings over an encrypted connection.</p>
                    </li>
                    <li>
                        <span class="workflow-step-number">05</span>
                        <div class="workflow-icon"><i class="bi bi-braces" aria-hidden="true"></i></div>
                        <h3>PHP API</h3>
                        <p>Authenticates and validates incoming field measurements.</p>
                    </li>
                    <li>
                        <span class="workflow-step-number">06</span>
                        <div class="workflow-icon"><i class="bi bi-database-check" aria-hidden="true"></i></div>
                        <h3>MySQL</h3>
                        <p>Stores and organizes validated readings for protected use.</p>
                    </li>
                    <li>
                        <span class="workflow-step-number">07</span>
                        <div class="workflow-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></div>
                        <h3>CropSense Dashboard</h3>
                        <p>Displays monitoring, recommendations, records, status, and reports.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="public-section technology-section" id="technology" aria-labelledby="technologyTitle">
            <div class="container public-container">
                <div class="technology-layout">
                    <div class="section-intro technology-intro">
                        <p class="section-eyebrow"><span></span> Technology</p>
                        <h2 id="technologyTitle">Built with practical, proven tools.</h2>
                        <p>
                            CropSense keeps the field device, web platform, and data layer focused
                            on maintainability, secure communication, and straightforward deployment.
                        </p>
                        <div class="technology-note">
                            <i class="bi bi-code-slash" aria-hidden="true"></i>
                            <span><strong>Native PHP architecture</strong> — no Laravel framework or unnecessary application layer.</span>
                        </div>
                    </div>

                    <div class="technology-groups">
                        <article class="technology-group">
                            <div class="technology-group-heading">
                                <i class="bi bi-cpu" aria-hidden="true"></i>
                                <div><span>Hardware</span><h3>Field sensing</h3></div>
                            </div>
                            <ul>
                                <li>ESP32</li>
                                <li>RS485 soil sensor</li>
                                <li>MAX3485</li>
                                <li>Modbus RTU</li>
                            </ul>
                        </article>

                        <article class="technology-group">
                            <div class="technology-group-heading">
                                <i class="bi bi-window" aria-hidden="true"></i>
                                <div><span>Web</span><h3>Application layer</h3></div>
                            </div>
                            <ul>
                                <li>PHP</li>
                                <li>HTML5</li>
                                <li>CSS3</li>
                                <li>JavaScript</li>
                                <li>Bootstrap 5</li>
                                <li class="technology-planned">Chart.js <small>planned</small></li>
                            </ul>
                        </article>

                        <article class="technology-group">
                            <div class="technology-group-heading">
                                <i class="bi bi-database" aria-hidden="true"></i>
                                <div><span>Data</span><h3>Structured storage</h3></div>
                            </div>
                            <ul>
                                <li>MySQL</li>
                                <li>Prepared statements</li>
                                <li>Time-filtered records</li>
                                <li>Spreadsheet export</li>
                            </ul>
                        </article>

                        <article class="technology-group">
                            <div class="technology-group-heading">
                                <i class="bi bi-cloud" aria-hidden="true"></i>
                                <div><span>Infrastructure</span><h3>Delivery workflow</h3></div>
                            </div>
                            <ul>
                                <li>Hostinger</li>
                                <li>HTTPS</li>
                                <li>Git</li>
                                <li>GitHub</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="public-section purpose-section" id="project-purpose" aria-labelledby="purposeTitle">
            <div class="container public-container">
                <div class="purpose-panel">
                    <div class="purpose-copy">
                        <p class="section-eyebrow"><span></span> Project Purpose</p>
                        <h2 id="purposeTitle">Agricultural data that is easier to understand and act on.</h2>
                        <p>
                            CropSense centralizes soil information and presents it in a form that
                            supports thoughtful crop-selection and field-monitoring decisions.
                        </p>

                        <div class="purpose-caution">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span>CropSense provides decision support. It does not replace agronomist guidance, field validation, or local growing expertise.</span>
                        </div>
                    </div>

                    <ul class="purpose-list">
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Provide accessible soil monitoring</li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Support data-driven crop selection</li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Help users understand soil conditions</li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Centralize soil data and field records</li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Improve agricultural decision support</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="public-cta" aria-labelledby="ctaTitle">
            <div class="cta-grid-pattern" aria-hidden="true"></div>
            <div class="container public-container">
                <div class="cta-panel">
                    <div>
                        <p class="section-eyebrow"><span></span> Authorized access</p>
                        <h2 id="ctaTitle">Ready to monitor your field?</h2>
                        <p>
                            Sign in to view protected soil readings, crop recommendations,
                            device status, historical records, and reports.
                        </p>
                    </div>
                    <a class="public-button public-button-primary" href="<?php echo htmlspecialchars(cropsense_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        Sign In to CropSense
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="public-footer">
        <div class="container public-container">
            <div class="footer-main">
                <a class="public-brand footer-brand" href="#home" aria-label="CropSense home">
                    <img src="<?php echo htmlspecialchars(cropsense_asset('img/cropsense-logo.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="" width="44" height="44">
                    <span>
                        <strong>CropSense</strong>
                        <small>Smart Crop Recommendation and Monitoring System Using IoT Sensors</small>
                    </span>
                </a>

                <nav class="footer-links" aria-label="Footer navigation">
                    <a href="#about">About</a>
                    <a href="#features">Features</a>
                    <a href="#technology">Technology</a>
                    <a href="<?php echo htmlspecialchars(cropsense_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Sign In</a>
                </nav>
            </div>

            <div class="footer-bottom">
                <span>&copy; <?php echo htmlspecialchars((string) $currentYear); ?> CropSense. All rights reserved.</span>
                <span>Designed for responsible agricultural decision support.</span>
            </div>
        </div>
    </footer>

    <script src="<?php echo htmlspecialchars(cropsense_asset('js/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars(cropsense_asset('js/public.js'), ENT_QUOTES, 'UTF-8'); ?>?v=20260819-landing-v1"></script>
</body>

</html>
