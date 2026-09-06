<?php
header('Content-Type: text/html; charset=utf-8');

function status_row($label, $ok, $detail)
{
    $class = $ok ? 'ok' : 'bad';
    $text = $ok ? 'OK' : 'CHECK';

    echo '<tr>';
    echo '<th>' . htmlspecialchars($label) . '</th>';
    echo '<td class="' . $class . '">' . $text . '</td>';
    echo '<td>' . htmlspecialchars($detail) . '</td>';
    echo '</tr>';
}

$root = __DIR__;
$requiredFiles = array(
    'login.php',
    'includes/security.php',
    'includes/auth.php',
    'includes/session.php',
    'includes/roles.php',
    'includes/device_status.php',
    'includes/header.php',
    'includes/navbar.php',
    'includes/sidebar.php',
    'includes/footer.php',
    'includes/latest_sensor.php',
    'includes/crop_suitability.php',
    'dashboard.php',
    'config/database.php',
    'config/crop_thresholds.php',
    'database/20260826_crop_parameter_thresholds.sql',
    'config/database.local.php',
);

$dbConfig = array(
    'host' => 'localhost',
    'dbname' => '',
    'username' => '',
    'password' => '',
);

$localConfigPath = $root . '/config/database.local.php';
$localConfigLoaded = false;
$localConfigError = '';

if (is_file($localConfigPath)) {
    $loadedConfig = include $localConfigPath;

    if (is_array($loadedConfig)) {
        $dbConfig = array_replace($dbConfig, $loadedConfig);
        $localConfigLoaded = true;
    } else {
        $localConfigError = 'database.local.php must return an array.';
    }
} else {
    $localConfigError = 'database.local.php is missing.';
}

$dbConnected = false;
$dbDetail = 'Not tested.';
$tableDetail = 'Not tested.';
$tablesOk = false;

if ($localConfigLoaded && extension_loaded('mysqli')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $testConn = @new mysqli(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['dbname']
    );

    if ($testConn->connect_error) {
        $dbDetail = 'Connection failed: ' . $testConn->connect_error;
    } else {
        $dbConnected = true;
        $dbDetail = 'Connected to ' . $dbConfig['dbname'] . ' as ' . $dbConfig['username'] . '.';
        $missingTables = array();

        foreach (array('users', 'farms', 'devices', 'sensor_readings', 'audit_logs') as $table) {
            $escapedTable = $testConn->real_escape_string($table);
            $result = $testConn->query("SHOW TABLES LIKE '{$escapedTable}'");

            if (!$result || $result->num_rows < 1) {
                $missingTables[] = $table;
            }
        }

        if ($missingTables) {
            $tableDetail = 'Missing tables: ' . implode(', ', $missingTables) . '.';
        } else {
            $tablesOk = true;
            $tableDetail = 'Required tables found.';
        }

        $testConn->close();
    }
} elseif (!extension_loaded('mysqli')) {
    $dbDetail = 'PHP mysqli extension is not enabled.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CropSense Diagnostics</title>
    <style>
        body { margin: 0; padding: 32px; font-family: Arial, sans-serif; color: #102018; background: #f4faf3; }
        main { max-width: 980px; margin: 0 auto; padding: 24px; border: 1px solid #d6e7d8; border-radius: 16px; background: #fff; }
        h1 { margin-top: 0; color: #075532; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e3efe4; text-align: left; vertical-align: top; }
        th { width: 220px; }
        .ok { color: #047857; font-weight: 800; }
        .bad { color: #b91c1c; font-weight: 800; }
        code { padding: 2px 5px; border-radius: 5px; background: #edf7ee; }
    </style>
</head>
<body>
<main>
    <h1>CropSense Diagnostics</h1>
    <p>Delete this file after checking. It does not show your database password.</p>
    <table>
        <?php status_row('PHP Version', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION . ' - recommended: 8.1 or 8.2'); ?>
        <?php status_row('mysqli Extension', extension_loaded('mysqli'), extension_loaded('mysqli') ? 'Enabled' : 'Not enabled'); ?>
        <?php foreach ($requiredFiles as $file): ?>
            <?php status_row($file, is_file($root . '/' . $file), is_file($root . '/' . $file) ? 'Found' : 'Missing'); ?>
        <?php endforeach; ?>
        <?php status_row('Database Config', $localConfigLoaded, $localConfigLoaded ? 'Loaded database.local.php' : $localConfigError); ?>
        <?php status_row('Database Connection', $dbConnected, $dbDetail); ?>
        <?php status_row('Required Tables', $tablesOk, $tableDetail); ?>
    </table>
    <p>After this page is checked, remove <code>cropsense_diagnostics.php</code> from <code>public_html</code>.</p>
</main>
</body>
</html>
