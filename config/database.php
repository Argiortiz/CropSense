<?php

$databaseConfig = [
    "host" => getenv("CROPSENSE_DB_HOST") ?: "localhost",
    "dbname" => getenv("CROPSENSE_DB_NAME") ?: "cropsense_db",
    "username" => getenv("CROPSENSE_DB_USER") ?: "root",
    "password" => getenv("CROPSENSE_DB_PASS") ?: "",
];

$localConfigPath = __DIR__ . "/database.local.php";

if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;

    if (is_array($localConfig)) {
        $databaseConfig = array_replace($databaseConfig, $localConfig);
    }
}

$host = (string) $databaseConfig["host"];
$dbname = (string) $databaseConfig["dbname"];
$username = (string) $databaseConfig["username"];
$password = (string) $databaseConfig["password"];

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection Failed. Please check your database settings.");
}

$conn->set_charset("utf8mb4");

?>
