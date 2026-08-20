<?php
require_once "includes/security.php";

cropsense_start_secure_session();
cropsense_apply_security_headers("web");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CropSense | Login</title>

    <meta name="theme-color" content="#123d2f">

    <!-- Favicon -->
    <link rel="icon" href="/assets/img/cropsense-logo.svg" type="image/svg+xml">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Login CSS -->
    <link rel="stylesheet" href="/assets/css/login.css?v=20260716-command2">
    <link rel="stylesheet" href="/assets/css/design-system.css?v=20260817-ui-refresh">
</head>

<body>
<main class="login-page">
    <div class="ambient-grid" aria-hidden="true"></div>

    <label class="language-pill" for="languageSelect" aria-label="Language selector">
        <i class="bi bi-globe2"></i>
        <select id="languageSelect">
            <option value="en">English</option>
            <option value="tl">Filipino</option>
        </select>
        <i class="bi bi-chevron-down"></i>
    </label>

    <section class="login-hero">
        <section class="brand-area" aria-label="CropSense introduction">
            <div class="brand-lockup">
                <div class="brand-logo" aria-hidden="true">
                    <img src="/assets/img/cropsense-logo.svg" alt="">
                </div>

                <div>
                    <p class="brand-kicker" data-i18n="brandKicker">AgriTech Intelligence</p>
                    <h1>Crop<span>Sense</span></h1>
                    <p class="brand-tagline" data-i18n="brandTagline">Smart Agriculture, Better Tomorrow</p>
                </div>
            </div>

            <div class="hero-copy">
                <h2 data-i18n="heroTitle">Smart Crop Recommendation and Monitoring System</h2>
                <p data-i18n="heroText">
                    Turn live soil readings into clear crop decisions with a secure,
                    farmer-friendly monitoring dashboard.
                </p>
            </div>

            <div class="feature-list" aria-label="CropSense features">
                <article class="feature-item">
                    <span><i class="bi bi-activity"></i></span>
                    <div>
                        <strong data-i18n="featureSoilTitle">Live Soil Monitoring</strong>
                        <p data-i18n="featureSoilText">Real-time data from connected farm sensors</p>
                    </div>
                </article>

                <article class="feature-item">
                    <span><i class="bi bi-stars"></i></span>
                    <div>
                        <strong data-i18n="featureAiTitle">AI Crop Recommendation</strong>
                        <p data-i18n="featureAiText">Smarter crop choices for better yield planning</p>
                    </div>
                </article>

                <article class="feature-item">
                    <span><i class="bi bi-cloud-check"></i></span>
                    <div>
                        <strong data-i18n="featureCloudTitle">Cloud Dashboard</strong>
                        <p data-i18n="featureCloudText">Secure access to farm insights anywhere</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="form-area" aria-label="Login form">
            <div class="sensor-visual" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div class="login-card">
                <div class="card-icon">
                    <img src="/assets/img/cropsense-logo.svg" alt="">
                </div>

                <div class="card-heading">
                    <p data-i18n="welcomePill">Welcome to CropSense</p>
                    <h2 data-i18n="welcomeTitle">WELCOME MABUHAY!</h2>
                    <span data-i18n="welcomeText">Sign in to unlock your farm intelligence dashboard</span>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="includes/auth.php" id="loginForm">
                    <div class="form-field">
                        <label class="form-label" for="username" data-i18n="username">Username or Email</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i>
                            </span>

                            <input
                                id="username"
                                type="text"
                                class="form-control"
                                name="username"
                                placeholder="Enter your username or email"
                                data-i18n-placeholder="usernamePlaceholder"
                                autocomplete="username"
                                required>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password" data-i18n="password">Password</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>

                            <input
                                id="password"
                                type="password"
                                class="form-control"
                                name="password"
                                placeholder="Enter your password"
                                data-i18n-placeholder="passwordPlaceholder"
                                autocomplete="current-password"
                                required>

                            <button
                                id="passwordToggle"
                                class="password-toggle"
                                type="button"
                                aria-label="Show password"
                                aria-pressed="false">
                                <i id="togglePassword" class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="login-options">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="remember"
                                name="remember">

                            <label class="form-check-label" for="remember" data-i18n="remember">
                                Remember Me
                            </label>
                        </div>

                        <a href="#" data-i18n="forgot">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn sign-in-btn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span data-i18n="signIn">Sign In</span>
                    </button>
                </form>

                <p class="security-note">
                    <i class="bi bi-shield-check"></i>
                    <span data-i18n="security">Protected access for your farm data</span>
                </p>
            </div>
        </section>
    </section>
    <p class="copyright-line" data-i18n="copyright">&copy; 2026 CropSense. All rights reserved.</p>

    <footer class="trust-bar" aria-label="Trust signals">
        <span><i class="bi bi-shield-check"></i> <span data-i18n="secure">Secure</span></span>
        <span><i class="bi bi-shield-check"></i> <span data-i18n="reliable">Reliable</span></span>
        <span><i class="bi bi-leaf"></i> <span data-i18n="smartAgri">Smart Agriculture</span></span>
        <span data-i18n="version">Version 2.0</span>
    </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/script.js?v=20260716-command2"></script>
</body>

</html>


