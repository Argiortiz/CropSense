<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/app_url.php';

cropsense_start_secure_session();
cropsense_apply_security_headers("web");

if (empty($_SESSION['pending_2fa_user_id'])) {
    header('Location: ' . cropsense_url('login.php'));
    exit();
}

$email = $_SESSION['pending_2fa_email'] ?? '';

function maskEmail(string $email): string
{
    if (!str_contains($email, '@')) {
        return 'your registered email';
    }

    [$name, $domain] = explode('@', $email, 2);

    $visible = substr($name, 0, 1);

    return $visible
        . str_repeat('*', max(strlen($name) - 1, 3))
        . '@'
        . $domain;
}

$maskedEmail = maskEmail($email);
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
        CropSense | Email Verification
    </title>

    <link
        rel="icon"
        href="<?php echo htmlspecialchars(cropsense_asset('img/cropsense-logo.svg'), ENT_QUOTES, 'UTF-8'); ?>"
        type="image/svg+xml"
    >

    <link
        rel="stylesheet"
        href="<?php echo htmlspecialchars(cropsense_asset('css/bootstrap.min.css'), ENT_QUOTES, 'UTF-8'); ?>"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css"
    >

    <style>

        body {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 24px;

            font-family:
                Inter,
                "Segoe UI",
                sans-serif;

            background:
                radial-gradient(
                    circle at 20% 20%,
                    rgba(183, 223, 105, .12),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #03261c,
                    #075338
                );
        }

        .otp-card {
            width: 100%;
            max-width: 470px;

            padding: 38px;

            border-radius: 24px;

            background:
                rgba(255, 255, 255, .98);

            box-shadow:
                0 35px 90px
                rgba(0, 0, 0, .28);
        }

        .otp-logo {
            width: 64px;
            height: 64px;

            display: grid;
            place-items: center;

            margin: 0 auto 20px;

            border-radius: 18px;

            background: #edf8ef;
        }

        .otp-logo img {
            width: 48px;
            height: 48px;
        }

        .otp-card h1 {
            margin: 0;

            text-align: center;

            color: #123d2f;

            font-size: 28px;
            font-weight: 800;
        }

        .otp-description {
            margin: 12px 0 28px;

            text-align: center;

            color: #697c72;
        }

        .otp-email {
            font-weight: 700;

            color: #0f6b46;
        }

        .otp-input {
            width: 100%;

            padding: 17px;

            border: 2px solid #dce8e1;
            border-radius: 14px;

            text-align: center;

            font-size: 30px;
            font-weight: 800;

            letter-spacing: 10px;

            color: #123d2f;
        }

        .otp-input:focus {
            outline: none;

            border-color: #168a54;

            box-shadow:
                0 0 0 4px
                rgba(22, 138, 84, .10);
        }

        .verify-button {
            width: 100%;

            margin-top: 18px;
            padding: 15px;

            border: 0;
            border-radius: 13px;

            color: #04251b;
            background: #b7df69;

            font-weight: 800;
        }

        .verify-button:hover {
            background: #c7ed7e;
        }

        .otp-security {
            margin-top: 22px;

            text-align: center;

            color: #7a8a82;

            font-size: 13px;
        }

        .alert {
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="otp-card">

    <div class="otp-logo">
        <img
            src="<?php echo htmlspecialchars(cropsense_asset('img/cropsense-logo.svg'), ENT_QUOTES, 'UTF-8'); ?>"
            alt="CropSense"
        >
    </div>

    <h1>
        Verify Your Identity
    </h1>

    <p class="otp-description">

        We sent a 6-digit verification code to

        <br>

        <span class="otp-email">
            <?php
            echo htmlspecialchars(
                $maskedEmail,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </span>

        <br><br>

        The code expires in
        <strong>5 minutes</strong>.

    </p>

    <?php if (isset($_SESSION['otp_error'])): ?>

        <div class="alert alert-danger">

            <?php

            echo htmlspecialchars(
                $_SESSION['otp_error'],
                ENT_QUOTES,
                'UTF-8'
            );

            unset($_SESSION['otp_error']);

            ?>

        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="<?php echo htmlspecialchars(cropsense_url('includes/verify_otp.php'), ENT_QUOTES, 'UTF-8'); ?>"
    >

        <input
            class="otp-input"
            type="text"
            name="otp"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            autocomplete="one-time-code"
            placeholder="000000"
            autofocus
            required
        >

        <button
            class="verify-button"
            type="submit"
        >
            <i class="bi bi-shield-check"></i>
            Verify Code
        </button>

    </form>

    <p class="otp-security">

        <i class="bi bi-lock-fill"></i>

        Secure CropSense authentication

    </p>

</div>

</body>
</html>
