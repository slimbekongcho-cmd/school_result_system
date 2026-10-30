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

$programme_id = (int) $_GET['id'];


/* Get programme */

$sql = "SELECT programme_name
        FROM programmes
        WHERE programme_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $programme_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$programme = mysqli_fetch_assoc($result);

if (!$programme) {
    die("Programme not found.");
}


/*
 * Check whether courses are using this programme
 */

$course_sql = "SELECT COUNT(*) AS total
               FROM courses
               WHERE programme_id = ?";

$course_stmt = mysqli_prepare($conn, $course_sql);

mysqli_stmt_bind_param(
    $course_stmt,
    "i",
    $programme_id
);

mysqli_stmt_execute($course_stmt);

$course_result = mysqli_stmt_get_result($course_stmt);

$course_row = mysqli_fetch_assoc($course_result);

$course_count = (int) $course_row['total'];


/*
 * Check whether students are using this programme
 */

$student_sql = "SELECT COUNT(*) AS total
                FROM students
                WHERE programme_id = ?";

$student_stmt = mysqli_prepare($conn, $student_sql);

mysqli_stmt_bind_param(
    $student_stmt,
    "i",
    $programme_id
);

mysqli_stmt_execute($student_stmt);

$student_result = mysqli_stmt_get_result($student_stmt);

$student_row = mysqli_fetch_assoc($student_result);

$student_count = (int) $student_row['total'];


/*
 * Don't delete if programme is being used
 */

if ($course_count > 0 || $student_count > 0) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cannot Delete Programme - UniResult</title>

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
            justify-content: center;
            align-items: center;

            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 560px;

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
            margin-bottom: 12px;
        }

        .programme {
            color: #003366;
            font-size: 18px;
            font-weight: 700;
            margin: 10px 0 20px;
        }

        .warning {
            background: #fff7ed;
            border-left: 4px solid #f97316;

            padding: 16px;

            text-align: left;

            border-radius: 7px;

            color: #7c2d12;

            margin-bottom: 25px;
        }

        .warning p {
            margin-bottom: 8px;
        }

        .warning p:last-child {
            margin-bottom: 0;
        }

        .btn {
            display: inline-block;

            background: #003366;
            color: white;

            padding: 12px 22px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: 600;

            transition: 0.25s;
        }

        .btn:hover {
            background: #00509e;
            transform: translateY(-1px);
        }

    </style>

</head>

<body>

    <div class="card">

        <div class="icon">
            ⚠️
        </div>

        <h1>
            Cannot Delete Programme
        </h1>

        <p>
            This programme is currently being used:
        </p>

        <div class="programme">

            <?= htmlspecialchars(
                $programme['programme_name']
            ) ?>

        </div>


        <div class="warning">

            <?php if ($course_count > 0): ?>

                <p>
                    📚
                    <strong>
                        <?= $course_count ?>
                    </strong>
                    course(s) are linked to this programme.
                </p>

            <?php endif; ?>


            <?php if ($student_count > 0): ?>

                <p>
                    👨‍🎓
                    <strong>
                        <?= $student_count ?>
                    </strong>
                    student(s) are linked to this programme.
                </p>

            <?php endif; ?>

            <p>
                Deleting it could affect existing academic records.
            </p>

        </div>


        <a
            href="index.php"
            class="btn"
        >
            ← Back to Programmes
        </a>

    </div>

</body>

</html>

<?php

    exit;
}


/*
 * Delete programme
 */

$delete_sql = "DELETE FROM programmes
               WHERE programme_id = ?";

$delete_stmt = mysqli_prepare(
    $conn,
    $delete_sql
);

mysqli_stmt_bind_param(
    $delete_stmt,
    "i",
    $programme_id
);


if (mysqli_stmt_execute($delete_stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Unable to delete programme: "
        . mysqli_error($conn)
    );
}
?>