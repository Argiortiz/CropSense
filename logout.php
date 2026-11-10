<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/app_url.php';
require_once __DIR__ . '/config/database.php';

cropsense_start_secure_session();

/*
|--------------------------------------------------------------------------
| Audit the logout before destroying the authenticated session
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['user_id'])) {
    try {
        cropsense_audit_log(
            $conn,
            (int) $_SESSION['user_id'],
            'User signed out'
        );
    } catch (Throwable $error) {
        // Logout must never fail only because audit logging failed.
        error_log('CropSense logout audit failed: ' . $error->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Destroy the session securely
|--------------------------------------------------------------------------
*/

if (function_exists('cropsense_destroy_session_securely')) {
    cropsense_destroy_session_securely();
} else {
    // Safe compatibility fallback.
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/*
|--------------------------------------------------------------------------
| Return to login
|--------------------------------------------------------------------------
*/

header('Location: ' . cropsense_url('login.php?reason=logout'));
exit();
