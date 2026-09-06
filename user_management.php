<?php
require_once "includes/session.php";
require_once "includes/roles.php";
require_once "config/database.php";

cropsense_require_admin();

$pageTitle = "User Management";
$pageKicker = "Administrator Dashboard";
$formMessage = "";
$formError = "";
$currentAdminId = (int) ($_SESSION["user_id"] ?? 0);
$requestedManagementSection = $_POST["management_section"] ?? ($_GET["section"] ?? "access-control");
$managementSection = in_array($requestedManagementSection, ["access-control", "accounts"], true)
    ? $requestedManagementSection
    : "access-control";

if (empty($_SESSION["user_management_token"])) {
    $_SESSION["user_management_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["user_management_token"];

function cropsense_user_management_find_user($conn, $userId)
{
    $stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE id = ? LIMIT 1");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function cropsense_user_management_active_admins_except($conn, $userId)
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'Administrator'
           AND status = 'Active'
           AND id <> ?"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : ["total" => 0];

    return (int) ($row["total"] ?? 0);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";
    $action = $_POST["action"] ?? "create_user";

    if (!hash_equals($csrfToken, $postedToken)) {
        $formError = "Security check failed. Please refresh the page and try again.";
    } elseif ($action === "create_user") {
        $fullname = trim($_POST["fullname"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $role = $_POST["role"] ?? "MAO Staff";
        $status = $_POST["status"] ?? "Active";

        if ($fullname === "" || $username === "" || $email === "" || $password === "") {
            $formError = "Please complete all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = "Please enter a valid email address.";
        } elseif (!in_array($role, ["Administrator", "MAO Staff"], true)) {
            $formError = "Please select a valid role.";
        } elseif (!in_array($status, ["Active", "Inactive"], true)) {
            $formError = "Please select a valid account status.";
        } elseif (strlen($password) < 8) {
            $formError = "Password must be at least 8 characters.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, username, email, password, role, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param("ssssss", $fullname, $username, $email, $passwordHash, $role, $status);

                if ($stmt->execute()) {
                    $formMessage = "User account created.";
                    cropsense_audit_log($conn, $currentAdminId, "Created user account: {$username}");
                } else {
                    $formError = "Username or email already exists.";
                }
            } else {
                $formError = "Unable to prepare user account creation.";
            }
        }
    } elseif ($action === "update_user") {
        $targetUserId = (int) ($_POST["user_id"] ?? 0);
        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $status = $_POST["status"] ?? "Active";
        $targetUser = cropsense_user_management_find_user($conn, $targetUserId);

        if (!$targetUser) {
            $formError = "Selected user was not found.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = "Please enter a valid email address.";
        } elseif (!in_array($status, ["Active", "Inactive"], true)) {
            $formError = "Please select a valid account status.";
        } elseif ($targetUserId === $currentAdminId && $status !== "Active") {
            $formError = "You cannot deactivate the account you are currently using.";
        } elseif (
            $targetUser["role"] === "Administrator"
            && $targetUser["status"] === "Active"
            && $status === "Inactive"
            && cropsense_user_management_active_admins_except($conn, $targetUserId) < 1
        ) {
            $formError = "At least one active Administrator account must remain.";
        } elseif ($password !== "" && strlen($password) < 8) {
            $formError = "New password must be at least 8 characters.";
        } else {
            if ($password === "") {
                $stmt = $conn->prepare("UPDATE users SET email = ?, status = ? WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("ssi", $email, $status, $targetUserId);
                }
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET email = ?, password = ?, status = ? WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("sssi", $email, $passwordHash, $status, $targetUserId);
                }
            }

            if (!$stmt) {
                $formError = "Unable to prepare user reset.";
            } elseif ($stmt->execute()) {
                $formMessage = $password === ""
                    ? "User account updated."
                    : "User account and password were updated.";
                cropsense_audit_log($conn, $currentAdminId, "Updated user account: {$targetUser["username"]} ({$status})");
            } else {
                $formError = "Email already exists.";
            }
        }
    } elseif ($action === "delete_user") {
        $targetUserId = (int) ($_POST["user_id"] ?? 0);
        $targetUser = cropsense_user_management_find_user($conn, $targetUserId);

        if (!$targetUser) {
            $formError = "Selected user was not found.";
        } elseif ($targetUserId === $currentAdminId) {
            $formError = "You cannot remove the account you are currently using.";
        } elseif (
            $targetUser["role"] === "Administrator"
            && $targetUser["status"] === "Active"
            && cropsense_user_management_active_admins_except($conn, $targetUserId) < 1
        ) {
            $formError = "At least one active Administrator account must remain.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");

            if ($stmt) {
                $stmt->bind_param("i", $targetUserId);

                if ($stmt->execute()) {
                    $formMessage = "User account removed.";
                    cropsense_audit_log($conn, $currentAdminId, "Removed user account: {$targetUser["username"]}");
                } else {
                    $formError = "Unable to remove user account.";
                }
            } else {
                $formError = "Unable to prepare user removal.";
            }
        }
    } else {
        $formError = "Please select a valid user action.";
    }
}

$users = [];
$userResult = $conn->query(
    "SELECT id, fullname, username, email, role, status, created_at
     FROM users
     ORDER BY created_at DESC, id DESC"
);

if ($userResult) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

include "includes/header.php";
?>

<div class="dashboard-shell">
    <?php include "includes/sidebar.php"; ?>

    <main class="dashboard-main">
        <?php include "includes/navbar.php"; ?>

        <section class="management-hero">
            <div>
                <span class="hero-badge">
                    <i class="bi bi-shield-check"></i>
                    Head of MAO
                </span>
                <h2>Manage CropSense user access.</h2>
                <p>Create Administrator and MAO Staff accounts, then review who can access farm monitoring data.</p>
            </div>
        </section>

        <section class="management-grid management-grid-single">
            <?php if ($managementSection === "access-control") : ?>
            <article class="management-panel" id="access-control">
                <div class="section-heading">
                    <div>
                        <span>Access Control</span>
                        <h3>Insert User</h3>
                    </div>
                    <i class="bi bi-person-plus"></i>
                </div>

                <?php if ($formMessage) : ?>
                    <div class="form-alert success"><?php echo htmlspecialchars($formMessage); ?></div>
                <?php endif; ?>

                <?php if ($formError) : ?>
                    <div class="form-alert error"><?php echo htmlspecialchars($formError); ?></div>
                <?php endif; ?>

                <form class="management-form" method="post" action="<?php echo htmlspecialchars(cropsense_url('user_management.php?section=access-control'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="create_user">
                    <input type="hidden" name="management_section" value="access-control">

                    <label>
                        Full Name
                        <input type="text" name="fullname" required>
                    </label>

                    <label>
                        Username
                        <input type="text" name="username" required>
                    </label>

                    <label>
                        Email
                        <input type="email" name="email" required>
                    </label>

                    <label>
                        Temporary Password
                        <span class="management-password-field">
                            <input
                                id="temporaryPassword"
                                type="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password"
                                required>
                            <button
                                type="button"
                                class="management-password-toggle"
                                data-password-toggle="temporaryPassword"
                                aria-label="Show temporary password"
                                aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </span>
                    </label>

                    <label>
                        Role
                        <select name="role">
                            <option value="MAO Staff">MAO Staff</option>
                            <option value="Administrator">Administrator</option>
                        </select>
                    </label>

                    <button type="submit">
                        <i class="bi bi-person-plus"></i>
                        Create User
                    </button>
                </form>
            </article>
            <?php endif; ?>

            <?php if ($managementSection === "accounts") : ?>
            <article class="management-panel wide" id="accounts">
                <div class="section-heading">
                    <div>
                        <span>Accounts</span>
                        <h3>System Users</h3>
                    </div>
                    <i class="bi bi-people"></i>
                </div>

                <?php if ($formMessage) : ?>
                    <div class="form-alert success"><?php echo htmlspecialchars($formMessage); ?></div>
                <?php endif; ?>

                <?php if ($formError) : ?>
                    <div class="form-alert error"><?php echo htmlspecialchars($formError); ?></div>
                <?php endif; ?>

                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Admin Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <?php
                                $userId = (int) $user["id"];
                                $isCurrentUser = $userId === $currentAdminId;
                                $passwordFieldId = "resetPassword" . $userId;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user["fullname"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["username"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["role"]); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($user["status"]); ?>"><?php echo htmlspecialchars($user["status"]); ?></span></td>
                                    <td><?php echo htmlspecialchars($user["created_at"]); ?></td>
                                    <td class="user-actions-cell">
                                        <details class="user-actions-menu">
                                            <summary>
                                                <i class="bi bi-sliders"></i>
                                                Manage
                                            </summary>

                                            <div>
                                                <form class="inline-user-form" method="post" action="<?php echo htmlspecialchars(cropsense_url('user_management.php?section=accounts'), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="update_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                                    <input type="hidden" name="management_section" value="accounts">

                                                    <label>
                                                        Reset Email
                                                        <input
                                                            type="email"
                                                            name="email"
                                                            value="<?php echo htmlspecialchars($user["email"]); ?>"
                                                            required>
                                                    </label>

                                                    <label>
                                                        Reset Password
                                                        <span class="management-password-field compact">
                                                            <input
                                                                id="<?php echo htmlspecialchars($passwordFieldId); ?>"
                                                                type="password"
                                                                name="password"
                                                                minlength="8"
                                                                autocomplete="new-password"
                                                                placeholder="Leave blank to keep current password">
                                                            <button
                                                                type="button"
                                                                class="management-password-toggle"
                                                                data-password-toggle="<?php echo htmlspecialchars($passwordFieldId); ?>"
                                                                aria-label="Show reset password"
                                                                aria-pressed="false">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        </span>
                                                    </label>

                                                    <label>
                                                        Account Status
                                                        <select name="status" required>
                                                            <option value="Active" <?php echo $user["status"] === "Active" ? "selected" : ""; ?>>Active</option>
                                                            <option
                                                                value="Inactive"
                                                                <?php echo $user["status"] === "Inactive" ? "selected" : ""; ?>
                                                                <?php echo $isCurrentUser ? "disabled" : ""; ?>>Inactive</option>
                                                        </select>
                                                    </label>

                                                    <button type="submit" class="small-action-button">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                        Save Changes
                                                    </button>
                                                </form>

                                                <form class="remove-user-form" method="post" action="<?php echo htmlspecialchars(cropsense_url('user_management.php?section=accounts'), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                                    <input type="hidden" name="management_section" value="accounts">

                                                    <button
                                                        type="submit"
                                                        class="danger-action-button"
                                                        <?php echo $isCurrentUser ? "disabled" : ""; ?>
                                                        data-confirm-message="Remove this user account? This cannot be undone.">
                                                        <i class="bi bi-trash3"></i>
                                                        <?php echo $isCurrentUser ? "Current Account" : "Remove User"; ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$users) : ?>
                                <tr>
                                    <td colspan="7">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php include "includes/footer.php"; ?>
