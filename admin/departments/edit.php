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
$message = "";


/* Get department */

$sql = "SELECT * FROM departments WHERE department_id = ?";

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


/* Get faculties */

$faculty_sql = "
    SELECT faculty_id, faculty_name
    FROM faculties
    ORDER BY faculty_name ASC
";

$faculty_result = mysqli_query($conn, $faculty_sql);


/* Update department */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $department_name = trim($_POST['department_name']);
    $faculty_id = (int) $_POST['faculty_id'];

    if (empty($department_name)) {

        $message = "Department name is required.";

    } elseif ($faculty_id <= 0) {

        $message = "Please select a faculty.";

    } else {

        $update_sql = "
            UPDATE departments
            SET department_name = ?,
                faculty_id = ?
            WHERE department_id = ?
        ";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        mysqli_stmt_bind_param(
            $update_stmt,
            "sii",
            $department_name,
            $faculty_id,
            $department_id
        );

        if (mysqli_stmt_execute($update_stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Error updating department: "
                     . mysqli_error($conn);
        }
    }

    // Re-fetch since the cursor was consumed on first render
    $faculty_result = mysqli_query($conn, $faculty_sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Department - UniResult</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ===== TOP NAVBAR ===== */

        .navbar {
            background: #003366;
            color: white;
            padding: 16px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 700;
        }

        .navbar .brand .divider {
            opacity: 0.5;
            font-weight: 400;
        }

        /* ===== CONTAINER ===== */

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        /* ===== CONTENT HEADER ===== */

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .page-heading h1 {
            color: #003366;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 14px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .back-button:hover {
            background: #eef2f7;
            transform: translateY(-2px);
        }

        /* ===== ALERT ===== */

        .error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        /* ===== FORM CARD ===== */

        .card {
            background: white;
            padding: 36px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13.5px;
            color: #334155;
        }

        input,
        select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            color: #1e293b;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        select {
            cursor: pointer;
        }

        input:focus,
        select:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        /* ===== BUTTONS ===== */

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .btn {
            flex: 1;
            padding: 14px 18px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-save {
            background: #003366;
            color: white;
        }

        .btn-save:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .btn-cancel:hover {
            background: #eef2f7;
            transform: translateY(-2px);
        }

        /* ===== MOBILE ===== */

        @media (max-width: 600px) {

            .navbar {
                padding: 14px 18px;
            }

            .navbar .brand {
                font-size: 15px;
            }

            .container {
                padding: 25px 16px 40px;
            }

            .content-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .back-button {
                justify-content: center;
            }

            .card {
                padding: 25px 20px;
                border-radius: 14px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="navbar">

     <div class="brand">
    <?php include __DIR__ . '/../../brand.html'; ?>
</div>

</div>


<div class="container">

    <div class="content-header">

        <div class="page-heading">
            <h1>Edit Department</h1>
            <p>Update the department information below.</p>
        </div>

        <a href="index.php" class="back-button">
            ← Back to Departments
        </a>

    </div>

    <?php if ($message): ?>

        <div class="error">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <div class="card">

        <form method="POST">

            <div class="form-group">

                <label for="department_name">
                    Department Name
                </label>

                <input
                    type="text"
                    id="department_name"
                    name="department_name"
                    value="<?= htmlspecialchars($department['department_name']) ?>"
                    placeholder="e.g. Computer Engineering"
                    required
                >

            </div>


            <div class="form-group">

                <label for="faculty_id">
                    Faculty
                </label>

                <select
                    id="faculty_id"
                    name="faculty_id"
                    required
                >

                    <option value="">
                        -- Select Faculty --
                    </option>

                    <?php while ($faculty = mysqli_fetch_assoc($faculty_result)): ?>

                        <option
                            value="<?= $faculty['faculty_id'] ?>"
                            <?= ($faculty['faculty_id'] == $department['faculty_id']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($faculty['faculty_name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="buttons">

                <button type="submit" class="btn btn-save">
                    ✓ Save Changes
                </button>

                <a href="index.php" class="btn btn-cancel">
                    ← Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>