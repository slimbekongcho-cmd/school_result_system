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

$lecturer_id = (int) $_GET['id'];


/* Get lecturer and linked user */

$sql = "SELECT
            l.lecturer_id,
            l.first_name,
            l.last_name,
            l.staff_id,
            l.user_id
        FROM lecturers l
        WHERE l.lecturer_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $lecturer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$lecturer = mysqli_fetch_assoc($result);

if (!$lecturer) {
    die("Lecturer not found.");
}


/*
 * Check if lecturer is assigned to courses
 */

$course_sql = "SELECT COUNT(*) AS total
               FROM courses
               WHERE lecturer_id = ?";

$course_stmt = mysqli_prepare(
    $conn,
    $course_sql
);

mysqli_stmt_bind_param(
    $course_stmt,
    "i",
    $lecturer_id
);

mysqli_stmt_execute($course_stmt);

$course_result = mysqli_stmt_get_result($course_stmt);

$course_row = mysqli_fetch_assoc($course_result);

$course_count = (int) $course_row['total'];


/*
 * If lecturer is assigned to courses,
 * don't delete.
 */

if ($course_count > 0) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cannot Delete Lecturer - UniResult</title>

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

        .lecturer {
            color: #003366;
            font-size: 19px;
            font-weight: 700;
            margin: 12px 0 20px;
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
        Cannot Delete Lecturer
    </h1>

    <p>
        This lecturer is currently assigned to:
    </p>

    <div class="lecturer">

        <?= htmlspecialchars(
            $lecturer['first_name']
            . " "
            . $lecturer['last_name']
        ) ?>

        <br>

        <small>
            <?= htmlspecialchars(
                $lecturer['staff_id']
            ) ?>
        </small>

    </div>


    <div class="warning">

        <p>
            📚
            <strong>
                <?= $course_count ?>
            </strong>
            course(s) are assigned to this lecturer.
        </p>

        <p>
            Please remove or reassign those courses
            before deleting the lecturer.
        </p>

    </div>


    <a
        href="index.php"
        class="btn"
    >
        ← Back to Lecturers
    </a>

</div>

</body>

</html>

<?php

    exit;
}


/*
 * Start transaction
 */

mysqli_begin_transaction($conn);

try {

    /*
     * Delete lecturer
     */

    $delete_lecturer_sql =
        "DELETE FROM lecturers
         WHERE lecturer_id = ?";

    $delete_lecturer_stmt =
        mysqli_prepare(
            $conn,
            $delete_lecturer_sql
        );

    mysqli_stmt_bind_param(
        $delete_lecturer_stmt,
        "i",
        $lecturer_id
    );

    mysqli_stmt_execute(
        $delete_lecturer_stmt
    );


    /*
     * Delete lecturer's user account
     */

    if (!empty($lecturer['user_id'])) {

        $delete_user_sql =
            "DELETE FROM users
             WHERE user_id = ?";

        $delete_user_stmt =
            mysqli_prepare(
                $conn,
                $delete_user_sql
            );

        mysqli_stmt_bind_param(
            $delete_user_stmt,
            "i",
            $lecturer['user_id']
        );

        mysqli_stmt_execute(
            $delete_user_stmt
        );
    }


    /*
     * Save changes
     */

    mysqli_commit($conn);

    header("Location: index.php");
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "Unable to delete lecturer: "
        . htmlspecialchars($e->getMessage())
    );
}