<?php

require_once "includes/security.php";
require_once "config/database.php";

cropsense_start_secure_session();

if (isset($_SESSION['user_id'])) {
    cropsense_audit_log($conn, (int) $_SESSION['user_id'], "User signed out");
}

session_destroy();

header("Location: login.php");

exit();

?>
