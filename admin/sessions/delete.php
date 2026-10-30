<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET['id'];


/* Check if session exists */
$sql = "SELECT session_name
        FROM academic_sessions
        WHERE session_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$session_data = mysqli_fetch_assoc($result);

if (!$session_data) {
    header("Location: index.php");
    exit;
}


/* Check if this is the active session */

$check_sql = "SELECT is_active
              FROM academic_sessions
              WHERE session_id = ?";

$check_stmt = mysqli_prepare($conn, $check_sql);

mysqli_stmt_bind_param(
    $check_stmt,
    "i",
    $id
);

mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);

$check = mysqli_fetch_assoc($check_result);


/* Don't allow active session to be deleted */

if ($check['is_active'] == 1) {

    ?>

    <!DOCTYPE html>
    <html>

    <head>

        <title>Cannot Delete Session - UniResult</title>

        <style>

            body {
                font-family: Arial, sans-serif;
                background: #f4f7fb;
                padding: 50px 20px;
            }

            .box {
                max-width: 550px;
                margin: auto;
                background: white;
                padding: 35px;
                border-radius: 14px;
                text-align: center;
                box-shadow: 0 8px 30px rgba(0,0,0,.08);
            }

            .icon {
                font-size: 45px;
            }

            h1 {
                color: #dc2626;
            }

            p {
                color: #475569;
                line-height: 1.6;
            }

            a {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 20px;
                background: #003366;
                color: white;
                text-decoration: none;
                border-radius: 7px;
            }

        </style>

    </head>

    <body>

    <div class="box">

        <div class="icon">⚠️</div>

        <h1>Cannot Delete Session</h1>

        <p>
            The academic session
            <strong>
                <?= htmlspecialchars($session_data['session_name']) ?>
            </strong>
            is currently active.
        </p>

        <p>
            Please deactivate it or activate another session
            before deleting it.
        </p>

        <a href="index.php">
            ← Back to Sessions
        </a>

    </div>

    </body>

    </html>

    <?php

    exit;
}


/* Delete session */

$sql = "DELETE FROM academic_sessions
        WHERE session_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die("Unable to delete academic session: " . mysqli_error($conn));
}
?>