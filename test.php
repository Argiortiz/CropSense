<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/database.php';

echo "<h2>CropSense Database Test</h2>";

$sql = "SELECT COUNT(*) AS total FROM users";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();

    echo "<h3>✅ Database Connected!</h3>";
    echo "<p>Total Users: <strong>{$row['total']}</strong></p>";
    echo "<p>Welcome, <strong>RyTech</strong>! Your CropSense system is connected to MySQL.</p>";
} else {
    echo "Query Failed: " . $conn->error;
}

$conn->close();
?>