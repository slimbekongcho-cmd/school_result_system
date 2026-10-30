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

$error = "";
$success = "";


/* Get faculty */

$sql = "SELECT * FROM faculties WHERE faculty_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $faculty_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$faculty = mysqli_fetch_assoc($result);

if (!$faculty) {
    die("Faculty not found.");
}


/* Update faculty */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $faculty_name = trim($_POST['faculty_name']);

    if (empty($faculty_name)) {

        $error = "Faculty name is required.";

    } else {

        $sql = "UPDATE faculties
                SET faculty_name = ?
                WHERE faculty_id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $faculty_name,
            $faculty_id
        );

        if (mysqli_stmt_execute($stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $error = mysqli_stmt_error($stmt);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Faculty - UniResult</title>

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
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 14px;
            font-weight: 500;
        }

        /* ===== FORM CARD ===== */

        .form-card {
            background: white;
            padding: 36px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13.5px;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            margin-bottom: 24px;
            transition: 0.2s ease;
        }

        input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        .buttons {
            display: flex;
            gap: 12px;
        }

        button,
        .back-btn {
            flex: 1;
            padding: 14px 18px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            text-align: center;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        button {
            background: #003366;
            color: white;
        }

        button:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        .back-btn {
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .back-btn:hover {
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

            .form-card {
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
            <h1>Edit Faculty</h1>
            <p>Update the faculty name below.</p>
        </div>

        <a href="index.php" class="back-button">
            ← Back to Faculties
        </a>

    </div>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="form-card">

        <form method="POST">

            <label>Faculty Name</label>

            <input
                type="text"
                name="faculty_name"
                value="<?= htmlspecialchars($faculty['faculty_name']) ?>"
                required
            >

            <div class="buttons">

                <button type="submit">
                    💾 Save Changes
                </button>

                <a href="index.php" class="back-btn">
                    ← Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>