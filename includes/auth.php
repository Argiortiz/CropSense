<?php
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    header("Location: /login.php");
    exit();
}

require_once __DIR__ . "/security.php";
require_once __DIR__ . "/roles.php";

cropsense_start_secure_session();

require_once __DIR__ . "/../config/database.php";

    $login = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($login) || empty($password)) {
        $_SESSION['error'] = "Please enter your username/email and password.";
        header("Location: ../login.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = "Login is not ready. Please check the database setup.";
        header("Location: ../login.php");
        exit();
    }

    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();

    $result = $stmt->get_result();

    if (!$result) {
        $_SESSION['error'] = "Unable to check account. Please check the database setup.";
        header("Location: ../login.php");
        exit();
    }

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (($user['status'] ?? 'Active') !== 'Active') {
            $_SESSION['error'] = "Your account is inactive.";

            cropsense_audit_log($conn, (int) $user['id'], "Blocked login attempt for inactive account");

            header("Location: ../login.php");
            exit();
        }

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            cropsense_audit_log($conn, (int) $user['id'], "User signed in");

            header("Location: ../dashboard.php");
            exit();

        } else {

            $_SESSION['error'] = "Invalid password.";

            cropsense_audit_log($conn, (int) $user['id'], "Failed login attempt");

            header("Location: ../login.php");
            exit();

        }

    } else {

        $_SESSION['error'] = "Account not found.";

        header("Location: ../login.php");
        exit();

    }

?>
