<?php

$cropsenseSecurityConfig = [];
$cropsenseSecurityConfigPath = __DIR__ . '/../config/security.local.php';

if (is_file($cropsenseSecurityConfigPath)) {
    $loadedSecurityConfig = require $cropsenseSecurityConfigPath;

    if (is_array($loadedSecurityConfig)) {
        $cropsenseSecurityConfig = $loadedSecurityConfig;
    }
}

if (!defined('CROPSENSE_DEVICE_API_KEY')) {
    define(
        'CROPSENSE_DEVICE_API_KEY',
        getenv('CROPSENSE_DEVICE_API_KEY') ?: ($cropsenseSecurityConfig['device_api_key'] ?? '')
    );
}

if (!defined('CROPSENSE_MAX_SENSOR_PAYLOAD_BYTES')) {
    define('CROPSENSE_MAX_SENSOR_PAYLOAD_BYTES', 4096);
}

if (!function_exists('cropsense_start_secure_session')) {
    function cropsense_start_secure_session()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('cropsense_apply_security_headers')) {
    function cropsense_apply_security_headers($context = 'web')
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if ($context === 'api') {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            return;
        }

        header(
            "Content-Security-Policy: " .
            "default-src 'self'; " .
            "script-src 'self' https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
            "font-src 'self' https://cdn.jsdelivr.net data:; " .
            "img-src 'self' data: https://images.unsplash.com; " .
            "connect-src 'self'; " .
            "frame-ancestors 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
        );
    }
}

if (!function_exists('cropsense_client_ip')) {
    function cropsense_client_ip()
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
    }
}

if (!function_exists('cropsense_audit_log')) {
    function cropsense_audit_log($conn, $userId, $activity)
    {
        if (!$conn || !$userId || !$activity) {
            return;
        }

        $safeActivity = substr((string) $activity, 0, 255);
        $ipAddress = cropsense_client_ip();
        $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, activity, ip_address) VALUES (?, ?, ?)');

        if (!$stmt) {
            return;
        }

        $stmt->bind_param('iss', $userId, $safeActivity, $ipAddress);
        $stmt->execute();
    }
}

if (!function_exists('cropsense_device_api_key_is_valid')) {
    function cropsense_device_api_key_is_valid($payload = [])
    {
        $expectedKey = CROPSENSE_DEVICE_API_KEY;

        if ($expectedKey === '') {
            return true;
        }

        $providedKey = $_SERVER['HTTP_X_API_KEY']
            ?? $_SERVER['HTTP_X_CROPSENSE_KEY']
            ?? ($payload['api_key'] ?? '');

        return is_string($providedKey) && hash_equals($expectedKey, $providedKey);
    }
}

?>
