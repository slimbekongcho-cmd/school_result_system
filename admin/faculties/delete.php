<?php

session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}


/* Check ID */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid faculty ID.");
}

$faculty_id = (int) $_GET['id'];


/* Check if faculty exists */

$sql = "SELECT faculty_name
        FROM faculties
        WHERE faculty_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$faculty = mysqli_fetch_assoc($result);

if (!$faculty) {
    die("Faculty not found.");
}


/* Check if faculty has departments */

$sql = "SELECT COUNT(*) AS total
        FROM departments
        WHERE faculty_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

$department_count = $row['total'];


/* If departments exist */

if ($department_count > 0) {

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cannot Delete Faculty - UniResult</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f7fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 550px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 51, 102, 0.10);
            text-align: center;
        }

        .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        h1 {
            color: #b91c1c;
            font-size: 26px;
            margin-bottom: 15px;
        }

        p {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .faculty-name {
            font-weight: 700;
            color: #003366;
        }

        .warning {
            background: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            border-radius: 6px;
        }

        .button {
            display: inline-block;
            padding: 12px 20px;
            background: #003366;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .button:hover {
            background: #00509e;
        }

    </style>

</head>

<body>

<div class="card">

    <div class="icon">⚠️</div>

    <h1>Cannot Delete Faculty</h1>

    <p>
        The faculty
        <span class="faculty-name">
            <?= htmlspecialchars($faculty['faculty_name']) ?>
        </span>
        cannot be deleted.
    </p>

    <div class="warning">

        <strong>
            This faculty has <?= $department_count ?> department(s).
        </strong>

        <br><br>

        You must remove or reassign the departments before deleting
        this faculty.

    </div>

    <a href="index.php" class="button">
        ← Back to Faculties
    </a>

</div>

</body>

</html>

<?php

    exit;
}


/* Delete faculty */

$sql = "DELETE FROM faculties
        WHERE faculty_id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $faculty_id
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Unable to delete faculty: " .
        htmlspecialchars(mysqli_stmt_error($stmt))
    );
}

?>