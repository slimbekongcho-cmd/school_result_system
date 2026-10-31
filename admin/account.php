<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$error = "";
$success = "";

/* CSRF token */
if (empty($_SESSION['account_csrf'])) {
    $_SESSION['account_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['account_csrf'];


/* Load the current admin account */
$stmt = mysqli_prepare($conn, "SELECT username, password FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$current = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$current) {
    die("Admin account not found.");
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submitted_token = $_POST['csrf_token'] ?? '';

    if (empty($submitted_token) || !hash_equals($_SESSION['account_csrf'], $submitted_token)) {

        $error = "Security verification failed. Please try again.";

    } else {

        $current_password = $_POST['current_password'] ?? '';
        $new_username     = trim($_POST['username'] ?? '');
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_password, $current['password'])) {

            $error = "Your current password is incorrect.";

        } elseif (strlen($new_username) < 3 || strlen($new_username) > 50) {

            $error = "Username must be between 3 and 50 characters.";

        } elseif ($new_password !== '' && strlen($new_password) < 8) {

            $error = "Your new password must be at least 8 characters long.";

        } elseif ($new_password !== $confirm_password) {

            $error = "The new passwords do not match.";

        } else {

            /* Make sure nobody else already uses this username */
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            mysqli_stmt_bind_param($check, "si", $new_username, $user_id);
            mysqli_stmt_execute($check);
            $taken = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
            mysqli_stmt_close($check);

            if ($taken) {

                $error = "That username is already taken.";

            } else {

                /* Keep the old password hash if no new password was typed */
                $final_hash = ($new_password !== '')
                    ? password_hash($new_password, PASSWORD_DEFAULT)
                    : $current['password'];

                try {

                    $update = mysqli_prepare($conn, "UPDATE users SET username = ?, password = ? WHERE id = ? AND role = 'admin'");
                    mysqli_stmt_bind_param($update, "ssi", $new_username, $final_hash, $user_id);
                    mysqli_stmt_execute($update);
                    mysqli_stmt_close($update);

                    $_SESSION['username'] = $new_username;
                    session_regenerate_id(true);

                    $_SESSION['account_csrf'] = bin2hex(random_bytes(32));
                    $csrf_token = $_SESSION['account_csrf'];

                    $current['username'] = $new_username;
                    $current['password'] = $final_hash;

                    $success = "Your account has been updated.";

                } catch (Exception $e) {

                    $error = "Could not update your account. Please try again.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account - COT-Result</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>

    :root {
        --navy: #003366;
        --blue: #0066cc;
        --bg: #f5f7fa;
        --text: #1e293b;
        --muted: #64748b;
        --red-bg: #fee2e2;
        --red-text: #991b1b;
        --green-bg: #dcfce7;
        --green-text: #166534;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: "Inter", "Segoe UI", Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
    }

    h1 { font-family: "Sora", sans-serif; }

    .navbar {
        background: var(--navy);
        color: white;
        padding: 16px 30px;
        display: flex;
        align-items: center;
    }

    .container {
        max-width: 520px;
        margin: 0 auto;
        padding: 36px 20px 60px;
    }

    .page-head { margin-bottom: 22px; }
    .page-head h1 { color: var(--navy); font-size: 26px; margin-bottom: 6px; }
    .page-head p { color: var(--muted); font-size: 14.5px; }

    .back-link {
        display: inline-block;
        margin-bottom: 18px;
        color: var(--navy);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }

    .back-link:hover { text-decoration: underline; }

    .card {
        background: white;
        padding: 32px;
        border-radius: 14px;
        box-shadow: 0 8px 30px rgba(15,23,42,.08);
    }

    .error, .success {
        padding: 12px 14px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
        line-height: 1.5;
    }

    .error { background: var(--red-bg); color: var(--red-text); }
    .success { background: var(--green-bg); color: var(--green-text); }

    .divider-label {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--navy);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 26px 0 14px;
        padding-bottom: 8px;
        border-bottom: 2px solid #eaf1fb;
    }

    .divider-label:first-of-type { margin-top: 0; }

    .form-group { margin-bottom: 16px; }

    label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-weight: 600;
        font-size: 13.5px;
    }

    input {
        width: 100%;
        padding: 12px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        outline: none;
    }

    input:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 4px rgba(0,102,204,.10);
    }

    .hint { font-size: 12.5px; color: var(--muted); margin-top: 5px; }

    .password-wrapper { position: relative; }
    .password-wrapper input { padding-right: 44px; }

    .toggle-password {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 16px;
        padding: 6px;
        line-height: 1;
    }

    .submit-btn {
        width: 100%;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: var(--navy);
        color: white;
        font-size: 15px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        margin-top: 10px;
    }

    .submit-btn:hover { background: var(--blue); }

</style>
</head>

<body>

<div class="navbar">
    <?php include __DIR__ . '/../brand.html'; ?>
</div>

<div class="container">

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="page-head">
        <h1>My Account</h1>
        <p>Change your admin username or password.</p>
    </div>

    <div class="card">

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="divider-label">Username</div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required maxlength="50"
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? $current['username']) ?>">
            </div>

            <div class="divider-label">Password</div>

            <div class="form-group">
                <label for="new_password">New password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password">
                    <button type="button" class="toggle-password" data-target="new_password" aria-label="Show password">👁️</button>
                </div>
                <div class="hint" id="password_hint">Leave blank to keep your current password.</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm new password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                    <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">👁️</button>
                </div>
            </div>

            <div class="divider-label">Confirm it's you</div>

            <div class="form-group">
                <label for="current_password">Current password</label>
                <div class="password-wrapper">
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    <button type="button" class="toggle-password" data-target="current_password" aria-label="Show password">👁️</button>
                </div>
                <div class="hint">Required to save any change.</div>
            </div>

            <button type="submit" class="submit-btn">Save changes</button>

        </form>

    </div>

</div>

<script>

document.querySelectorAll(".toggle-password").forEach(function (button) {

    button.addEventListener("click", function () {

        const input = document.getElementById(this.getAttribute("data-target"));

        if (input.type === "password") {
            input.type = "text";
            this.textContent = "🙈";
            this.setAttribute("aria-label", "Hide password");
        } else {
            input.type = "password";
            this.textContent = "👁️";
            this.setAttribute("aria-label", "Show password");
        }

    });

});

const newPassword = document.getElementById("new_password");
const hint = document.getElementById("password_hint");

newPassword.addEventListener("input", function () {

    const length = this.value.length;

    if (length === 0) {
        hint.textContent = "Leave blank to keep your current password.";
        hint.style.color = "";
    } else if (length < 8) {
        hint.textContent = `Too short — ${length}/8 characters minimum.`;
        hint.style.color = "#b91c1c";
    } else {
        hint.textContent = "✓ Looks good.";
        hint.style.color = "#166534";
    }

});

</script>

</body>
</html>