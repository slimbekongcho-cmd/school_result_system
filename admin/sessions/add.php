<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $session_name = trim($_POST['session_name']);

    if (empty($session_name)) {

        $error = "Academic session is required.";

    } else {

        $sql = "INSERT INTO academic_sessions
                (session_name)
                VALUES (?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $session_name
        );

        if (mysqli_stmt_execute($stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $error = "Error: " . mysqli_error($conn);

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

    <title>Add Academic Session - UniResult</title>

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
            max-width: 550px;
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

        .box {
            background: white;
            padding: 36px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        input::placeholder {
            color: #94a3b8;
        }

        input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #003366;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        button:hover {
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

            .box {
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
            <h1>Add Academic Session</h1>
            <p>Create a new academic session for the university.</p>
        </div>

        <a href="index.php" class="back-button">
            ← Back to Sessions
        </a>

    </div>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="box">

        <form method="POST">

            <div class="form-group">

                <label>Academic Session</label>

                <input
                    type="text"
                    name="session_name"
                    placeholder="e.g. 2025/2026"
                    value="<?= htmlspecialchars($_POST['session_name'] ?? '') ?>"
                    required
                >

            </div>

            <button type="submit">
                + Add Session
            </button>

        </form>

    </div>

</div>

</body>

</html>