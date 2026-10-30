<?php

session_start();

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Only accept POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: login.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| Get form data
|--------------------------------------------------------------------------
*/

$username = trim($_POST['username'] ?? '');

$password = $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| Validate input
|--------------------------------------------------------------------------
*/

if ($username === '' || $password === '') {

    header(
        "Location: login.php?error=" .
        urlencode("Please enter your username and password.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Find user
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        username,
        password,
        role,
        status
    FROM users
    WHERE username = ?
    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    header(
        "Location: login.php?error=" .
        urlencode("Something went wrong. Please try again.")
    );

    exit;

}


mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$user = mysqli_fetch_assoc($result);


/*
|--------------------------------------------------------------------------
| Check username
|--------------------------------------------------------------------------
*/

if (!$user) {

    header(
        "Location: login.php?error=" .
        urlencode("Invalid administrator username or password.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| IMPORTANT:
| Only admin accounts can use this login.
|--------------------------------------------------------------------------
*/

if ($user['role'] !== 'admin') {

    header(
        "Location: login.php?error=" .
        urlencode("This login is restricted to administrators.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Check account status
|--------------------------------------------------------------------------
*/

if ($user['status'] !== 'active') {

    header(
        "Location: login.php?error=" .
        urlencode("This administrator account is not active.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Check password
|--------------------------------------------------------------------------
*/

if (!password_verify($password, $user['password'])) {

    header(
        "Location: login.php?error=" .
        urlencode("Invalid administrator username or password.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Login successful
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);


$_SESSION['user_id'] = $user['id'];

$_SESSION['username'] = $user['username'];

$_SESSION['role'] = 'admin';


/*
|--------------------------------------------------------------------------
| Send admin to dashboard
|--------------------------------------------------------------------------
*/

header("Location: dashboard.php");

exit;