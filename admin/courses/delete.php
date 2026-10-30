<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}


/* Check ID */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$course_id = (int) $_GET['id'];


/* Get course */

$sql = "
    SELECT course_code, course_title
    FROM courses
    WHERE course_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $course_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$course = mysqli_fetch_assoc($result);


if (!$course) {
    die("Course not found.");
}


/*
 * Check if this course has registrations
 */

$check_sql = "
    SELECT COUNT(*) AS total
    FROM course_registrations
    WHERE course_id = ?
";

$check_stmt = mysqli_prepare(
    $conn,
    $check_sql
);

mysqli_stmt_bind_param(
    $check_stmt,
    "i",
    $course_id
);

mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);

$row = mysqli_fetch_assoc($check_result);

$registration_count = (int) $row['total'];


/*
 * Don't delete if students are registered
 */

if ($registration_count > 0) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cannot Delete Course - UniResult</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
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

            padding: 35px;

            border-radius: 14px;

            text-align: center;

            box-shadow:
                0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        h1 {
            color: #dc2626;
            font-size: 26px;
            margin-bottom: 15px;
        }

        .description {
            color: #475569;
            line-height: 1.6;
        }

        .course {
            margin-top: 5px;
            font-weight: 700;
            color: #0f172a;
        }

        .warning {
            background: #fff7ed;

            border-left: 4px solid #f97316;

            padding: 15px;

            margin: 25px 0;

            text-align: left;

            border-radius: 6px;

            color: #7c2d12;
        }

        .warning strong {
            display: block;
            margin-bottom: 6px;
        }

        .btn {
            display: inline-block;

            padding: 12px 20px;

            background: #0f172a;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: 600;

            transition: 0.3s;
        }

        .btn:hover {
            background: #1e293b;
        }

        @media (max-width: 600px) {

            .card {
                padding: 25px 20px;
            }

            h1 {
                font-size: 23px;
            }

        }

    </style>

</head>

<body>

    <div class="card">

        <div class="icon">
            ⚠️
        </div>

        <h1>
            Cannot Delete Course
        </h1>

        <p class="description">

            The following course cannot be deleted:

            <div class="course">

                <?= htmlspecialchars($course['course_code']) ?>
                -
                <?= htmlspecialchars($course['course_title']) ?>

            </div>

        </p>


        <div class="warning">

            <strong>
                <?= $registration_count ?> student registration(s) found.
            </strong>

            This course already has students registered for it.
            Deleting it could break existing academic records.

        </div>


        <a
            href="index.php"
            class="btn"
        >
            ← Back to Courses
        </a>

    </div>

</body>

</html>

<?php

    exit;
}


/*
 * Delete course
 */

$delete_sql = "
    DELETE FROM courses
    WHERE course_id = ?
";

$delete_stmt = mysqli_prepare(
    $conn,
    $delete_sql
);

mysqli_stmt_bind_param(
    $delete_stmt,
    "i",
    $course_id
);


if (mysqli_stmt_execute($delete_stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Unable to delete course: "
        . mysqli_error($conn)
    );
}
?>