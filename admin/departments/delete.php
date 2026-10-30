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

$department_id = (int) $_GET['id'];


/* Get department */

$sql = "
    SELECT department_name
    FROM departments
    WHERE department_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $department_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$department = mysqli_fetch_assoc($result);

if (!$department) {
    die("Department not found.");
}


/*
 * Check if the department has programmes
 */

$check_sql = "
    SELECT COUNT(*) AS total
    FROM programmes
    WHERE department_id = ?
";

$check_stmt = mysqli_prepare($conn, $check_sql);

mysqli_stmt_bind_param(
    $check_stmt,
    "i",
    $department_id
);

mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);

$row = mysqli_fetch_assoc($check_result);

$programme_count = $row['total'];


/*
 * If programmes exist, don't delete
 */

if ($programme_count > 0) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cannot Delete Department - UniResult</title>

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
            margin-bottom: 12px;
        }

        .description {
            color: #475569;
            line-height: 1.6;
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
            Cannot Delete Department
        </h1>

        <p class="description">

            The department
            <strong>
                <?= htmlspecialchars($department['department_name']) ?>
            </strong>
            cannot be deleted because it still has
            programmes attached to it.

        </p>


        <div class="warning">

            <strong>
                <?= $programme_count ?> programme(s) found.
            </strong>

            You must remove or reassign the programmes
            before deleting this department.

        </div>


        <a
            href="index.php"
            class="btn"
        >
            ← Back to Departments
        </a>

    </div>

</body>

</html>

<?php

    exit;
}


/*
 * Delete department
 */

$delete_sql = "
    DELETE FROM departments
    WHERE department_id = ?
";

$delete_stmt = mysqli_prepare(
    $conn,
    $delete_sql
);

mysqli_stmt_bind_param(
    $delete_stmt,
    "i",
    $department_id
);

if (mysqli_stmt_execute($delete_stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Unable to delete department: "
        . mysqli_error($conn)
    );
}

?>