<?php

session_start();


/*
|--------------------------------------------------------------------------
| If already logged in
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {

    if ($_SESSION['role'] === 'admin') {

        header("Location: dashboard.php");

        exit;

    }

}


/*
|--------------------------------------------------------------------------
| If a student or lecturer is trying to access admin login
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['user_id']) &&
    isset($_SESSION['role']) &&
    $_SESSION['role'] !== 'admin'
) {

    session_unset();

    session_destroy();

}


$error = "";


if (isset($_GET['error'])) {

    $error = $_GET['error'];

}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - UniResult</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

<style>

    :root {
        --navy: #003366;
        --navy-deep: #002347;
        --blue: #0066cc;
        --light-blue: #4da3ff;
        --bg: #f5f7fa;
        --text: #1e293b;
        --muted: #64748b;
        --border: #e5e7eb;
        --green: #0f9d68;
        --amber: #b8770a;
        --red-bg: #fee2e2;
        --red-text: #991b1b;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Inter", "Segoe UI", Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3 {
        font-family: "Sora", "Segoe UI", Arial, sans-serif;
    }

    :focus-visible {
        outline: 3px solid var(--light-blue);
        outline-offset: 2px;
    }

    /* SHELL */

    .shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 460px) minmax(0, 1fr);
    }

    /* BRAND PANEL */

    .brand-panel {
        background: linear-gradient(150deg, var(--navy-deep), var(--navy) 55%, #01438c);
        color: white;
        padding: clamp(32px, 5vw, 56px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .brand-panel::after {
        content: "";
        position: absolute;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(77,163,255,.16), transparent 70%);
        top: -140px;
        right: -140px;
        pointer-events: none;
    }

    .logo {
        font-family: "Sora", sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: .3px;
        display: flex;
        align-items: center;
        gap: 9px;
        position: relative;
        z-index: 1;
    }

    .logo-mark {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--light-blue), var(--blue));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 800;
        color: var(--navy-deep);
        flex-shrink: 0;
    }

    .admin-badge {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        font-family: "IBM Plex Mono", monospace;
        font-size: 11.5px;
        font-weight: 600;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: #7fbaff;
        background: rgba(77,163,255,.12);
        border: 1px solid rgba(77,163,255,.3);
        padding: 6px 13px;
        border-radius: 20px;
        margin-top: 18px;
    }

    .brand-copy {
        position: relative;
        z-index: 1;
        max-width: 340px;
    }

    .brand-copy h1 {
        font-size: clamp(26px, 2.6vw, 32px);
        line-height: 1.25;
        font-weight: 800;
        margin-bottom: 14px;
        letter-spacing: -.4px;
    }

    .brand-copy p {
        color: rgba(255,255,255,.7);
        font-size: 15px;
        line-height: 1.7;
    }

    .brand-chip {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.14);
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        color: rgba(255,255,255,.8);
        margin-top: 30px;
        max-width: 340px;
    }

    .brand-chip .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--light-blue);
        flex-shrink: 0;
    }

    .brand-footer {
        position: relative;
        z-index: 1;
        font-size: 12.5px;
        color: rgba(255,255,255,.4);
    }

    /* FORM PANEL */

    .form-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
       
    }

    .login-card {
        width: 100%;
        max-width: 400px;
    }

    .login-card-head {
        margin-bottom: 28px;
    }

    .login-card-head .lock-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: #eef4fb;
        color: var(--navy);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        margin-bottom: 16px;
    }

    .login-card h2 {
        color: var(--navy);
        font-size: 26px;
        font-weight: 800;
        margin-bottom: 6px;
        letter-spacing: -.3px;
    }

    .subtitle {
        color: var(--muted);
        font-size: 15px;
    }

    .error {
        background: var(--red-bg);
        color: var(--red-text);
        padding: 12px 14px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
        border: 1px solid #fecaca;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        margin-bottom: 7px;
        color: #334155;
        font-weight: 600;
        font-size: 14px;
    }

    input {
        width: 100%;
        padding: 13px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease;
        background: white;
        color: var(--text);
    }

    input::placeholder {
        color: #94a3b8;
    }

    input:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 4px rgba(0, 102, 204, .10);
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper input {
        padding-right: 48px;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 17px;
        color: var(--muted);
        padding: 6px;
        border-radius: 6px;
        line-height: 1;
    }

    .toggle-password:hover {
        color: var(--navy);
        background: #eef4fb;
    }

    .login-submit {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 8px;
        background: var(--navy);
        color: white;
        font-size: 15.5px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: background .2s ease, transform .2s ease;
        margin-top: 6px;
    }

    .login-submit:hover {
        background: var(--blue);
        transform: translateY(-1px);
    }

    .divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 24px 0;
        color: var(--muted);
        font-size: 12.5px;
    }

    .divider::before,
    .divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .back-home {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-align: center;
        color: var(--navy);
        text-decoration: none;
        font-weight: 600;
        font-size: 14.5px;
        padding: 11px;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        transition: border-color .2s ease, background .2s ease;
    }

    .back-home:hover {
        border-color: var(--navy);
        background: var(--bg);
    }

    .login-info {
        margin-top: 22px;
        text-align: center;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.6;
    }

    /* MOBILE */

    @media (max-width: 900px) {

        .shell {
            grid-template-columns: 1fr;
        }

        .brand-panel {
            padding: 28px 24px 34px;
            gap: 18px;
        }

        .brand-copy { display: none; }
        .brand-chip { display: none; }

        .brand-panel {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .admin-badge {
            margin-top: 0;
        }

        .brand-footer { display: none; }

        .form-panel {
            padding: 34px 20px 50px;
        }
    }

    @media (max-width: 380px) {
        .login-card-head h2 { font-size: 22px; }
    }

</style>
</head>

<body>

<div class="shell">

    <!-- BRAND PANEL -->
    <div class="brand-panel">

       
    <?php include __DIR__ . '/../brand.html'; ?>
            <div class="admin-badge">
                🔐 Administrator portal
            </div>
        

        <div class="brand-copy">
            <h1>Manage the system that runs behind every result.</h1>
            <p>Sign in with your administrator credentials to manage students, lecturers, courses and result approvals.</p>

            <div class="brand-chip">
                <span class="dot"></span>
                Restricted to administrator accounts
            </div>
        </div>

        <div class="brand-footer">
            &copy; <?= date("Y") ?> COT-Result.
        </div>

    </div>

    <!-- FORM PANEL -->
    <div class="form-panel">

        <div class="login-card">

            <div class="login-card-head">
                <div class="lock-icon">🔐</div>
                <h2>Admin login</h2>
                <p class="subtitle">Login to access the administration dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_process.php">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter admin username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter admin password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-submit">
                    Login as administrator
                </button>

            </form>

            <div class="divider">or</div>

            <a href="../index.php" class="back-home">
                ← Back to home
            </a>

            <p class="login-info">
                This portal is restricted to administrator accounts.
            </p>

        </div>

    </div>

</div>

<script>

const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

togglePassword.addEventListener("click", function () {

    if (passwordInput.type === "password") {

        passwordInput.type = "text";
        togglePassword.textContent = "🙈";
        togglePassword.setAttribute("aria-label", "Hide password");

    } else {

        passwordInput.type = "password";
        togglePassword.textContent = "👁️";
        togglePassword.setAttribute("aria-label", "Show password");

    }

});

</script>

</body>
</html>