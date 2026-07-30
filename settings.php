<?php
require_once "includes/session.php";

$hideSensorIndicator = true;

include "includes/header.php";
?>

<div class="dashboard-shell">
    <?php include "includes/sidebar.php"; ?>

    <main class="dashboard-main">
        <?php include "includes/navbar.php"; ?>

        <section class="settings-hero">
            <div>
                <span class="hero-badge">
                    <i class="bi bi-sliders"></i>
                    Personalize CropSense
                </span>
                <h2>Make the dashboard comfortable for your eyes.</h2>
                <p>
                    Choose a dark interface for low-light use and adjust text size
                    so monitoring remains easy to read.
                </p>
            </div>

            <div class="settings-preview" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </section>

        <section class="settings-grid" aria-label="Dashboard settings">
            <article class="settings-card theme-setting">
                <div class="settings-icon">
                    <i class="bi bi-moon-stars"></i>
                </div>

                <div class="settings-copy">
                    <span>Appearance</span>
                    <h3>Dark Theme</h3>
                    <p>Switch to a darker dashboard with softer contrast for night monitoring.</p>
                </div>

                <label class="switch-control" for="themeToggle">
                    <input type="checkbox" id="themeToggle">
                    <span></span>
                </label>
            </article>

            <article class="settings-card font-setting">
                <div class="settings-icon">
                    <i class="bi bi-type"></i>
                </div>

                <div class="settings-copy">
                    <span>Readability</span>
                    <h3>Font Size</h3>
                    <p>Adjust dashboard text size to match your preferred reading comfort.</p>
                </div>

                <div class="font-control">
                    <div class="font-labels">
                        <small>Aa</small>
                        <strong id="fontSizeValue">16px</strong>
                        <small>Aa</small>
                    </div>
                    <input type="range" id="fontSizeRange" min="14" max="20" step="1" value="16">
                </div>
            </article>

            <article class="settings-card setting-summary">
                <div class="settings-icon">
                    <i class="bi bi-check2-circle"></i>
                </div>

                <div class="settings-copy">
                    <span>Saved Automatically</span>
                    <h3>Your preferences stay here</h3>
                    <p>
                        CropSense remembers these choices in this browser and applies
                        them when you return to the dashboard.
                    </p>
                </div>
            </article>
        </section>
    </main>
</div>

<?php include "includes/footer.php"; ?>
