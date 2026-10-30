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
$message = "";


/* Get existing programme */

$sql = "SELECT *
        FROM programmes
        WHERE programme_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $programme_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$programme = mysqli_fetch_assoc($result);

if (!$programme) {
    die("Programme not found.");
}


/* Get departments */

$department_sql = "SELECT
                        department_id,
                        department_name
                   FROM departments
                   ORDER BY department_name ASC";

$department_result = mysqli_query(
    $conn,
    $department_sql
);


/* Update programme */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $programme_name = trim($_POST['programme_name']);
    $department_id = (int) $_POST['department_id'];


    if (empty($programme_name) || !$department_id) {

        $message = "Please fill in all fields.";

    } else {

        $update_sql = "UPDATE programmes
                       SET programme_name = ?,
                           department_id = ?
                       WHERE programme_id = ?";

        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );

        mysqli_stmt_bind_param(
            $update_stmt,
            "sii",
            $programme_name,
            $department_id,
            $programme_id
        );


        if (mysqli_stmt_execute($update_stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Error updating programme: "
                     . mysqli_error($conn);
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Programme - UniResult</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            width: 100%;
            max-width: 650px;
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

        .message {
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
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            color: #334155;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input,
        select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
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

        .button {
            flex: 1;
            padding: 14px 18px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .save {
            background: #003366;
            color: white;
        }

        .save:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        .cancel {
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .cancel:hover {
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
            <h1>Edit Programme</h1>
            <p>Update the programme information below.</p>
        </div>

        <a href="index.php" class="back-button">
            ← Back to Programmes
        </a>

    </div>


    <?php if ($message): ?>

        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <div class="card">

        <form method="POST">


            <!-- Programme Name -->

            <div class="form-group">

                <label>
                    Programme Name
                </label>

                <input
                    type="text"
                    name="programme_name"
                    value="<?= htmlspecialchars($programme['programme_name']) ?>"
                    placeholder="e.g. Software Engineering"
                    required
                >

            </div>


            <!-- Department -->

            <div class="form-group">

                <label>
                    Department
                </label>

                <select name="department_id" required>

                    <option value="">
                        -- Select Department --
                    </option>

                    <?php while ($department = mysqli_fetch_assoc($department_result)): ?>

                        <option
                            value="<?= $department['department_id'] ?>"
                            <?= ($department['department_id'] == $programme['department_id']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- Buttons -->

            <div class="buttons">

                <button type="submit" class="button save">
                    ✓ Save Changes
                </button>

                <a href="index.php" class="button cancel">
                    ← Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>