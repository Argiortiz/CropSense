<?php

/*
|--------------------------------------------------------------------------
| CropSense Login Diagnostic
|--------------------------------------------------------------------------
| TEMPORARY FILE - DELETE AFTER DEBUGGING
*/

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$results = [];

function debug_step(string $name, callable $callback): void
{
    global $results;

    try {
        $callback();

        $results[] = [
            'status' => 'PASS',
            'name' => $name,
            'message' => 'OK'
        ];

    } catch (Throwable $e) {

        $results[] = [
            'status' => 'FAIL',
            'name' => $name,
            'message' =>
                get_class($e) .
                ': ' .
                $e->getMessage() .
                ' | File: ' .
                $e->getFile() .
                ' | Line: ' .
                $e->getLine()
        ];
    }
}


/* =========================================================
   BASIC PHP
========================================================= */

$results[] = [
    'status' => 'INFO',
    'name' => 'PHP Version',
    'message' => PHP_VERSION
];


/* =========================================================
   REQUIRED FILE CHECKS
========================================================= */

$files = [
    'includes/security.php',
    'config/app_url.php',
    'config/security.local.php',
    'config/database.php',
    'login.php'
];

foreach ($files as $file) {

    $fullPath = __DIR__ . '/' . $file;

    $results[] = [
        'status' =>
            is_file($fullPath)
                ? 'PASS'
                : 'FAIL',

        'name' =>
            'File: ' . $file,

        'message' =>
            is_file($fullPath)
                ? 'FOUND'
                : 'MISSING'
    ];
}


/* =========================================================
   SECURITY LOCAL CONFIG
========================================================= */

debug_step(
    'Load config/security.local.php',
    function () {

        $path =
            __DIR__ .
            '/config/security.local.php';

        if (!is_file($path)) {
            throw new RuntimeException(
                'security.local.php does not exist.'
            );
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new RuntimeException(
                'security.local.php must return an array.'
            );
        }
    }
);


/* =========================================================
   SECURITY.PHP
========================================================= */

debug_step(
    'Load includes/security.php',
    function () {

        require_once
            __DIR__ .
            '/includes/security.php';
    }
);


/* =========================================================
   APP URL
========================================================= */

debug_step(
    'Load config/app_url.php',
    function () {

        require_once
            __DIR__ .
            '/config/app_url.php';
    }
);


/* =========================================================
   SECURITY FUNCTIONS
========================================================= */

debug_step(
    'Check security functions',
    function () {

        if (
            !function_exists(
                'cropsense_start_secure_session'
            )
        ) {
            throw new RuntimeException(
                'cropsense_start_secure_session() missing.'
            );
        }

        if (
            !function_exists(
                'cropsense_apply_security_headers'
            )
        ) {
            throw new RuntimeException(
                'cropsense_apply_security_headers() missing.'
            );
        }
    }
);


/* =========================================================
   RECAPTCHA CONSTANTS
========================================================= */

debug_step(
    'Check reCAPTCHA configuration',
    function () {

        if (
            !defined(
                'CROPSENSE_RECAPTCHA_SITE_KEY'
            )
        ) {
            throw new RuntimeException(
                'CROPSENSE_RECAPTCHA_SITE_KEY is not defined.'
            );
        }

        if (
            !defined(
                'CROPSENSE_RECAPTCHA_SECRET_KEY'
            )
        ) {
            throw new RuntimeException(
                'CROPSENSE_RECAPTCHA_SECRET_KEY is not defined.'
            );
        }

        if (
            CROPSENSE_RECAPTCHA_SITE_KEY === ''
        ) {
            throw new RuntimeException(
                'reCAPTCHA Site Key is empty.'
            );
        }

        if (
            CROPSENSE_RECAPTCHA_SECRET_KEY === ''
        ) {
            throw new RuntimeException(
                'reCAPTCHA Secret Key is empty.'
            );
        }
    }
);


/* =========================================================
   CURL
========================================================= */

$results[] = [
    'status' =>
        extension_loaded('curl')
            ? 'PASS'
            : 'FAIL',

    'name' => 'PHP cURL',

    'message' =>
        extension_loaded('curl')
            ? 'ENABLED'
            : 'DISABLED'
];


/* =========================================================
   OPENSSL
========================================================= */

$results[] = [
    'status' =>
        extension_loaded('openssl')
            ? 'PASS'
            : 'FAIL',

    'name' => 'PHP OpenSSL',

    'message' =>
        extension_loaded('openssl')
            ? 'ENABLED'
            : 'DISABLED'
];


/* =========================================================
   LOGIN PAGE EXECUTION TEST
========================================================= */

debug_step(
    'Execute login.php',
    function () {

        ob_start();

        try {

            include
                __DIR__ .
                '/login.php';

        } finally {

            ob_end_clean();
        }
    }
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    CropSense Login Diagnostic
</title>

<style>

body {
    font-family:
        Arial,
        sans-serif;

    background:
        #f3f6f4;

    padding:
        30px;

    color:
        #173d30;
}

.container {
    max-width:
        1000px;

    margin:
        auto;

    background:
        white;

    padding:
        30px;

    border-radius:
        16px;

    box-shadow:
        0 15px 40px
        rgba(0,0,0,.1);
}

h1 {
    margin-top:
        0;
}

.item {
    padding:
        14px;

    margin-bottom:
        10px;

    border-radius:
        8px;
}

.pass {
    background:
        #e8f7ed;

    border-left:
        5px solid
        #178447;
}

.fail {
    background:
        #ffeaea;

    border-left:
        5px solid
        #d1242f;
}

.info {
    background:
        #eef4ff;

    border-left:
        5px solid
        #4078d0;
}

.status {
    font-weight:
        bold;
}

pre {
    white-space:
        pre-wrap;

    overflow-wrap:
        anywhere;
}

</style>

</head>

<body>

<div class="container">

<h1>
    CropSense Login Diagnostic
</h1>

<p>
    Checking the production login dependencies...
</p>

<?php foreach ($results as $result): ?>

<div
    class="item <?= strtolower(
        htmlspecialchars(
            $result['status']
        )
    ) ?>"
>

<div class="status">

<?= htmlspecialchars(
    $result['status']
) ?>

—
<?= htmlspecialchars(
    $result['name']
) ?>

</div>

<pre><?= htmlspecialchars(
    $result['message']
) ?></pre>

</div>

<?php endforeach; ?>

</div>

</body>
</html>