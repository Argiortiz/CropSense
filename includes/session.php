<?php
require_once __DIR__ . "/security.php";

cropsense_start_secure_session();

if(!isset($_SESSION['user_id'])){

    header("Location: login.php");
    exit();

}

require_once __DIR__ . "/../config/database.php";

$sessionUserId = (int) $_SESSION['user_id'];
$sessionUserStatement = $conn->prepare("SELECT fullname, role, status FROM users WHERE id = ? LIMIT 1");
$sessionUser = null;

if ($sessionUserStatement) {
    $sessionUserStatement->bind_param("i", $sessionUserId);
    $sessionUserStatement->execute();
    $sessionUserResult = $sessionUserStatement->get_result();
    $sessionUser = $sessionUserResult ? $sessionUserResult->fetch_assoc() : null;
    $sessionUserStatement->close();
}

if (!$sessionUser || ($sessionUser['status'] ?? 'Inactive') !== 'Active') {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit();
}

$_SESSION['fullname'] = $sessionUser['fullname'];
$_SESSION['role'] = $sessionUser['role'];
?>
