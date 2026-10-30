<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$message = "";

/* Get departments */
$department_sql = "SELECT 
                       d.department_id,
                       d.department_name,
                       f.faculty_name
                   FROM departments d
                   INNER JOIN faculties f
                       ON d.faculty_id = f.faculty_id
                   ORDER BY d.department_name ASC";

$department_result = mysqli_query($conn, $department_sql);


/* Add programme */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $department_id = $_POST['department_id'];
    $programme_name = trim($_POST['programme_name']);

    if (empty($department_id) || empty($programme_name)) {

        $message = "Please select a department and enter a programme name.";

    } else {

        $sql = "INSERT INTO programmes
                (department_id, programme_name)
                VALUES (?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $department_id,
            $programme_name
        );

        if (mysqli_stmt_execute($stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Error: " . mysqli_error($conn);
        }
    }

    // Re-fetch since the cursor was consumed on first render
    $department_result = mysqli_query($conn, $department_sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Programme - UniResult</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            min-height: 100vh;
            background: #f4f7fb;
            color: #1e293b;
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
            font-size: 28px;
            color: #003366;
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

        .error-message {
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

        .form-card {
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
            background: #ffffff;
            color: #334155;
            transition: 0.2s ease;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        /* ===== SUBMIT ===== */

        .submit-row {
            margin-top: 10px;
        }

        .btn-primary {
            width: 100%;
            border: none;
            padding: 14px 22px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            background: #003366;
            color: #ffffff;
            transition: 0.2s ease;
        }

        .btn-primary:hover {
            background: #00509e;
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

            .form-card {
                padding: 25px 20px;
                border-radius: 14px;
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
            <h1>Add Programme</h1>
            <p>Create a new programme and assign it to a department.</p>
        </div>

        <a href="index.php" class="back-button">
            ← Back to Programmes
        </a>

    </div>


    <?php if ($message): ?>

        <div class="error-message">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <div class="form-card">

        <form method="POST">

            <!-- Department -->

            <div class="form-group">

                <label for="department_id">
                    Department
                </label>

                <select
                    name="department_id"
                    id="department_id"
                    required
                >

                    <option value="">
                        -- Select Department --
                    </option>

                    <?php while ($department = mysqli_fetch_assoc($department_result)): ?>

                        <option value="<?= $department['department_id'] ?>">

                            <?= htmlspecialchars($department['department_name']) ?>
                            —
                            <?= htmlspecialchars($department['faculty_name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- Programme name -->

            <div class="form-group">

                <label for="programme_name">
                    Programme Name
                </label>

                <input
                    type="text"
                    name="programme_name"
                    id="programme_name"
                    placeholder="e.g. Software Engineering"
                    value="<?= htmlspecialchars($_POST['programme_name'] ?? '') ?>"
                    required
                >

            </div>


            <!-- Submit -->

            <div class="submit-row">

                <button type="submit" class="btn-primary">
                    + Add Programme
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>