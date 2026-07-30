<?php

if (!function_exists('cropsense_current_role')) {
    function cropsense_current_role()
    {
        return $_SESSION['role'] ?? '';
    }
}

if (!function_exists('cropsense_is_admin')) {
    function cropsense_is_admin()
    {
        return cropsense_current_role() === 'Administrator';
    }
}

if (!function_exists('cropsense_require_admin')) {
    function cropsense_require_admin()
    {
        if (!cropsense_is_admin()) {
            http_response_code(403);
            header('Location: dashboard.php');
            exit();
        }
    }
}

?>
