<?php

session_start();
require_once "config/database.php";
require_once "includes/send_email.php";

$message = "";
$error = "";

/* CSRF token */
if (empty($_SESSION['forgot_csrf'])) {
    $_SESSION['forgot_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['forgot_csrf'];


/* Find a student or lecturer account by email */
function findAccountByEmail($conn, $email)
{
    $queries = [
        "SELECT u.id, s.first_name AS first_name
         FROM users u
         INNER JOIN students s ON s.user_id = u.id
         WHERE s.email = ? AND u.status = 'active'
         LIMIT 1",

        "SELECT u.id, l.first_name AS first_name
         FROM users u
         INNER JOIN lecturers l ON l.user_id = u.id
         WHERE l.email = ? AND u.status = 'active'
         LIMIT 1"
    ];

    foreach ($queries as $sql) {

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row) {
            return $row;
        }
    }

    return null;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submitted_token = $_POST['csrf_token'] ?? '';

    if (empty($submitted_token) || !hash_equals($_SESSION['forgot_csrf'], $submitted_token)) {

        $error = "Security verification failed. Please try again.";

    } else {

        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            $account = findAccountByEmail($conn, $email);

            if ($account) {

                /* Raw token goes in the email; only its hash is stored */
                $token      = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);

                $update_sql = "UPDATE users
                               SET reset_token = ?,
                                   reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                               WHERE id = ?";

                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $token_hash, $account['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);

                $link = SITE_URL . "/reset_password.php?token=" . $token;
                $name = htmlspecialchars($account['first_name']);

                $body = "
                    <p>Hello {$name},</p>
                    <p>We received a request to reset your COT-Result password.</p>
                    <p><a href=\"{$link}\"
                          style=\"display:inline-block;padding:12px 22px;background:#003366;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;\">
                          Reset my password</a></p>
                    <p>Or copy this link into your browser:<br>{$link}</p>
                    <p>This link expires in <strong>1 hour</strong>. If you didn't request this, you can safely ignore this email and your password will stay the same.</p>
                    <p>Regards,<br><strong>COT-Result</strong></p>
                ";

                sendEmail($email, $account['first_name'], "COT-Result: Reset your password", $body);
            }

            /* Same message whether or not the email exists, so nobody can use this page to find out who has an account */
            $message = "If an account exists with that email, a password reset link has been sent. Check your inbox (and spam folder). The link expires in 1 hour.";

            $_SESSION['forgot_csrf'] = bin2hex(random_bytes(32));
            $csrf_token = $_SESSION['forgot_csrf'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - COT-Result</title>

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

        <h2>Forgot your password?</h2>
        <p class="subtitle">
            Enter the email address on your account and we'll send you a link to set a new password.
        </p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php else: ?>

        <form method="POST">

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <button type="submit" class="submit-btn">Send reset link</button>

        </form>

        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to login</a>

    </div>

</div>

</body>
</html>