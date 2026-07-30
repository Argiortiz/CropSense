<?php
$currentPage = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . "/roles.php";

if (!isset($deviceSummary)) {
    require_once __DIR__ . "/device_status.php";
}
?>

<aside class="dashboard-sidebar" id="cropsenseSidebar" aria-label="CropSense navigation">
    <div class="sidebar-header">
        <a
            class="sidebar-brand"
            href="dashboard.php"
            data-sidebar-brand
            aria-label="CropSense Live Monitoring"
            title="CropSense Live Monitoring">
            <span class="sidebar-logo-mark">
                <img src="/assets/img/cropsense-logo.svg" alt="" data-logo-image>
                <i class="bi bi-flower2" aria-hidden="true"></i>
            </span>
            <div>
                <strong>CropSense</strong>
                <small>Version 2.0</small>
            </div>
        </a>

        <button
            type="button"
            class="sidebar-close-button"
            data-sidebar-toggle
            aria-controls="cropsenseSidebar"
            aria-expanded="true"
            aria-label="Close sidebar"
            title="Close sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" title="Live Monitoring">
            <i class="bi bi-broadcast"></i>
            Live Monitoring
        </a>

        <?php if (cropsense_is_admin()) : ?>
            <a href="user_management.php?section=access-control" class="<?php echo $currentPage === 'user_management.php' ? 'active' : ''; ?>" title="Users">
                <i class="bi bi-people"></i>
                Users
            </a>
            <?php if ($currentPage === 'user_management.php') : ?>
                <div class="sidebar-submenu" aria-label="User management sections">
                    <a
                        href="user_management.php?section=access-control"
                        class="<?php echo ($managementSection ?? 'access-control') === 'access-control' ? 'active' : ''; ?>"
                        <?php echo ($managementSection ?? 'access-control') === 'access-control' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-person-plus"></i>
                        Access Control
                    </a>
                    <a
                        href="user_management.php?section=accounts"
                        class="<?php echo ($managementSection ?? 'access-control') === 'accounts' ? 'active' : ''; ?>"
                        <?php echo ($managementSection ?? 'access-control') === 'accounts' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-person-lines-fill"></i>
                        Accounts
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <a href="collected_data.php" class="<?php echo $currentPage === 'collected_data.php' ? 'active' : ''; ?>" title="Collected Data">
            <i class="bi bi-file-earmark-bar-graph"></i>
            Collected Data
        </a>

        <a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" title="Settings">
            <i class="bi bi-gear"></i>
            Settings
        </a>

        <a href="logout.php" class="nav-logout" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>
    </nav>

    <a href="logout.php" class="logout-link mobile-logout">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>
</aside>

<button
    type="button"
    class="sidebar-backdrop"
    data-sidebar-dismiss
    aria-label="Close sidebar"
    aria-hidden="true"></button>
