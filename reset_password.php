<?php

session_start();
require_once "config/database.php";

$error = "";
$success = "";
$show_form = false;

/* CSRF token */
if (empty($_SESSION['reset_csrf'])) {
    $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['reset_csrf'];

/* The token arrives from the email link (GET) or the hidden field (POST) */
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));


/* Find an active account with a valid, unexpired reset token */
function findUserByResetToken($conn, $token)
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    /* Only the hash is stored in the database */
    $token_hash = hash('sha256', $token);

    $sql = "SELECT id
            FROM users
            WHERE reset_token = ?
              AND reset_token_expires > NOW()
              AND status = 'active'
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token_hash);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row ?: null;
}


$user = findUserByResetToken($conn, $token);

if (!$user) {

    $error = "This reset link is invalid or has expired. Please request a new one.";

} else {

    $show_form = true;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $submitted_token = $_POST['csrf_token'] ?? '';

        if (empty($submitted_token) || !hash_equals($_SESSION['reset_csrf'], $submitted_token)) {

            $error = "Security verification failed. Please try again.";

        } else {

            $new_password     = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($new_password === '' || $confirm_password === '') {

                $error = "Please fill in both password fields.";

            } elseif (strlen($new_password) < 8) {

                $error = "Your new password must be at least 8 characters long.";

            } elseif ($new_password !== $confirm_password) {

                $error = "Passwords do not match.";

            } else {

                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                /* Set the new password and burn the token so the link can't be reused */
                $update_sql = "UPDATE users
                               SET password = ?,
                                   password_changed = 1,
                                   reset_token = NULL,
                                   reset_token_expires = NULL
                               WHERE id = ?";

                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $password_hash, $user['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);

                $success   = "Your password has been reset. You can now log in with your new password.";
                $show_form = false;

                $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['reset_csrf'];
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
<meta name="referrer" content="no-referrer">
<title>Reset Password - COT Result</title>

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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    h2 { font-family: "Sora", sans-serif; }

    .wrap { width: 100%; max-width: 440px; }

    .top-logo { display: flex; justify-content: center; margin-bottom: 24px; }

    .card {
        background: white;
        padding: 36px;
        border-radius: 14px;
        box-shadow: 0 15px 40px rgba(0,0,0,.12);
    }

    .card h2 { color: var(--navy); font-size: 24px; margin-bottom: 6px; }

    .subtitle {
        color: var(--muted);
        font-size: 14.5px;
        margin-bottom: 24px;
        line-height: 1.5;
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
        margin-top: 6px;
    }

    .submit-btn:hover { background: var(--blue); }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: var(--navy);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }

    .back-link:hover { text-decoration: underline; }

</style>
</head>

<body>

<div class="wrap">

    <div class="top-logo">
        <div style="background: var(--navy); padding: 12px 22px; border-radius: 10px; display: inline-flex;">
            <?php include __DIR__ . '/brand.html'; ?>
        </div>
    </div>

    <div class="card">

        <h2>Set a new password</h2>

        <?php if ($show_form): ?>
            <p class="subtitle">Choose a new password for your account.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($show_form): ?>

        <form method="POST">

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="new_password">New password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <button type="button" class="toggle-password" data-target="new_password" aria-label="Show password">👁️</button>
                </div>
                <div class="hint" id="password_hint">At least 8 characters.</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm new password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">👁️</button>
                </div>
            </div>

            <button type="submit" class="submit-btn">Reset my password</button>

        </form>

        <?php elseif (!$success): ?>

            <a href="forgot_password.php" class="back-link">Request a new reset link</a>

        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to login</a>

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

const newPasswordInput = document.getElementById("new_password");
const passwordHint = document.getElementById("password_hint");

if (newPasswordInput) {

    newPasswordInput.addEventListener("input", function () {

        const length = this.value.length;

        if (length === 0) {
            passwordHint.textContent = "At least 8 characters.";
            passwordHint.style.color = "";
        } else if (length < 8) {
            passwordHint.textContent = `Too short — ${length}/8 characters minimum.`;
            passwordHint.style.color = "#b91c1c";
        } else {
            passwordHint.textContent = "✓ Looks good.";
            passwordHint.style.color = "#166534";
        }

    });

}

</script>

</body>
</html>