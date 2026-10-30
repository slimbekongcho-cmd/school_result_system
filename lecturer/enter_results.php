<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['registration_id'])) {
    die("Registration not specified.");
}

$registration_id = intval($_GET['registration_id']);

/* Get lecturer */
$sql = "SELECT lecturer_id, first_name, last_name
        FROM lecturers
        WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$lecturer = mysqli_fetch_assoc($result);

if (!$lecturer) {
    die("Lecturer profile not found.");
}

$lecturer_id = $lecturer['lecturer_id'];


/* Get registered student and course */
$sql = "SELECT
            cr.registration_id,
            s.student_id,
            s.first_name,
            s.last_name,
            s.matricule,
            c.course_id,
            c.course_code,
            c.course_title
        FROM course_registrations cr

        INNER JOIN students s
            ON cr.student_id = s.student_id

        INNER JOIN courses c
            ON cr.course_id = c.course_id

        WHERE cr.registration_id = ?
        AND c.lecturer_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $registration_id,
    $lecturer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$registration = mysqli_fetch_assoc($result);

if (!$registration) {
    die("Registration not found or this course is not assigned to you.");
}


$message = "";
$error = "";


/* Save result */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ca_score = floatval($_POST['ca_score']);
    $exam_score = floatval($_POST['exam_score']);

    /* Validate scores */
    if ($ca_score < 0 || $ca_score > 40) {

        $error = "CA score must be between 0 and 40.";

    } elseif ($exam_score < 0 || $exam_score > 60) {

        $error = "Exam score must be between 0 and 60.";

    } else {

        $total_score = $ca_score + $exam_score;

        /* Determine grade */
        if ($total_score >= 80) {

            $letter_grade = "A";
            $grade_point = 4.0;
            $remark = "Excellent";

        } elseif ($total_score >= 70) {

            $letter_grade = "B";
            $grade_point = 3.0;
            $remark = "Very Good";

        } elseif ($total_score >= 60) {

            $letter_grade = "C";
            $grade_point = 2.0;
            $remark = "Good";

        } elseif ($total_score >= 50) {

            $letter_grade = "D";
            $grade_point = 1.0;
            $remark = "Pass";

        } elseif ($total_score >= 40) {

            $letter_grade = "E";
            $grade_point = 0.0;
            $remark = "Weak";

        } else {

            $letter_grade = "F";
            $grade_point = 0.0;
            $remark = "Fail";
        }


        /* Check if result already exists */
        $check_sql = "SELECT result_id
                      FROM results
                      WHERE registration_id = ?";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $check_stmt,
            "i",
            $registration_id
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);


        if (mysqli_num_rows($check_result) > 0) {

            /* Update existing result */

            $update_sql = "UPDATE results
                           SET ca_score = ?,
                               exam_score = ?,
                               total_score = ?,
                               letter_grade = ?,
                               grade_point = ?,
                               remark = ?,
                               status = 'Submitted'
                           WHERE registration_id = ?";

            $update_stmt = mysqli_prepare(
                $conn,
                $update_sql
            );

            mysqli_stmt_bind_param(
                $update_stmt,
                "dddsssi",
                $ca_score,
                $exam_score,
                $total_score,
                $letter_grade,
                $grade_point,
                $remark,
                $registration_id
            );

            if (mysqli_stmt_execute($update_stmt)) {

                $message = "Result updated successfully.";

            } else {

                $error = "Unable to update result.";
            }

        } else {

            /* Insert new result */

            $insert_sql = "INSERT INTO results
                           (
                               registration_id,
                               ca_score,
                               exam_score,
                               total_score,
                               letter_grade,
                               grade_point,
                               remark,
                               status
                           )
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')";

            $insert_stmt = mysqli_prepare(
                $conn,
                $insert_sql
            );

            mysqli_stmt_bind_param(
                $insert_stmt,
                "idddsds",
                $registration_id,
                $ca_score,
                $exam_score,
                $total_score,
                $letter_grade,
                $grade_point,
                $remark
            );

            if (mysqli_stmt_execute($insert_stmt)) {

                $message = "Result submitted successfully.";

            } else {

                $error = "Unable to submit result.";
            }
        }
    }
}


/* Get existing result if available */
$existing_sql = "SELECT
                     ca_score,
                     exam_score,
                     total_score,
                     letter_grade,
                     grade_point,
                     remark,
                     status
                 FROM results
                 WHERE registration_id = ?";

$existing_stmt = mysqli_prepare(
    $conn,
    $existing_sql
);

mysqli_stmt_bind_param(
    $existing_stmt,
    "i",
    $registration_id
);

mysqli_stmt_execute($existing_stmt);

$existing_result =
    mysqli_stmt_get_result($existing_stmt);

$existing = mysqli_fetch_assoc($existing_result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Enter Result - UniResult</title>

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
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
        }

        .logout {
            background: rgba(255, 255, 255, 0.14);
            color: white;
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .logout:hover {
            background: rgba(255, 255, 255, 0.24);
        }

        /* ===== CONTAINER ===== */

        .container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        /* ===== CONTENT HEADER ===== */

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .content-header h1 {
            color: #003366;
            font-size: 24px;
            font-weight: 700;
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

        /* ===== RETURNED NOTICE ===== */

        .returned-message {
            background: #fff8e6;
            border-left: 4px solid #f59e0b;
            color: #7c5700;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .returned-message strong {
            display: block;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .returned-message p {
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
        }

        /* ===== ALERTS ===== */

        .success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #16a34a;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

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

        /* ===== CARD ===== */

        .card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #003366;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        /* ===== STUDENT INFO ===== */

        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 20px;
        }

        .student-info .info-item .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .student-info .info-item .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }

        .student-info .info-item.full-width {
            grid-column: 1 / -1;
        }

        /* ===== FORM ===== */

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
        }

        label small {
            color: #94a3b8;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        button {
            width: 100%;
            padding: 14px;
            background: #003366;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: 0.2s ease;
            margin-top: 6px;
        }

        button:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        /* ===== RESULT SUMMARY BOX ===== */

        .result-box {
            background: #f4f7fb;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .result-box .result-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 20px;
            margin-bottom: 14px;
        }

        .result-box .result-item .result-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .result-box .result-item .result-value {
            font-size: 16px;
            font-weight: 700;
            color: #003366;
        }

        /* ===== STATUS BADGES ===== */

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 700;
        }

        .status.returned {
            background: #fef3c7;
            color: #92400e;
        }

        .status.submitted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status.review {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status.approved {
            background: #d1fae5;
            color: #047857;
        }

        .status.published {
            background: #dcfce7;
            color: #166534;
        }

        .status.draft {
            background: #e5e7eb;
            color: #374151;
        }

        /* ===== MOBILE ===== */

        @media (max-width: 600px) {

            .navbar {
                padding: 14px 18px;
            }

            .navbar .brand {
                font-size: 16px;
            }

            .container {
                padding: 20px 14px 40px;
            }

            .content-header {
                flex-direction: column;
                align-items: stretch;
            }

            .content-header h1 {
                font-size: 21px;
            }

            .back-button {
                justify-content: center;
            }

            .card {
                padding: 20px;
                border-radius: 14px;
            }

            .student-info,
            .result-box .result-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include '../navbar.html'; ?>

<div class="container">

    <div class="content-header">

        <h1>Enter Student Result</h1>

        <a href="students.php?course_id=<?= $registration['course_id'] ?>" class="back-button">
            ← Back to Students
        </a>

    </div>


    <?php if ($existing && $existing['status'] === 'Returned'): ?>

        <div class="returned-message">
            <strong>⚠ Result Returned by Admin</strong>
            <p>
                Your result has been returned for correction.
                Please review the CA and Exam scores, make the necessary
                corrections, and submit the result again.
            </p>
        </div>

    <?php endif; ?>


    <div class="card">

        <h2>Student Details</h2>

        <div class="student-info">

            <div class="info-item">
                <div class="info-label">Student</div>
                <div class="info-value">
                    <?= htmlspecialchars(
                        $registration['first_name'] . " " .
                        $registration['last_name']
                    ) ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Student Number</div>
                <div class="info-value">
                    <?= htmlspecialchars($registration['matricule']) ?>
                </div>
            </div>

            <div class="info-item full-width">
                <div class="info-label">Course</div>
                <div class="info-value">
                    <?= htmlspecialchars(
                        $registration['course_code'] . " — " .
                        $registration['course_title']
                    ) ?>
                </div>
            </div>

        </div>

    </div>


    <?php if ($message): ?>

        <div class="success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <div class="card">

        <h2>Scores</h2>

        <?php if ($existing): ?>

            <div class="result-box">

                <div class="result-grid">

                    <div class="result-item">
                        <div class="result-label">Total</div>
                        <div class="result-value"><?= htmlspecialchars($existing['total_score']) ?></div>
                    </div>

                    <div class="result-item">
                        <div class="result-label">Grade</div>
                        <div class="result-value"><?= htmlspecialchars($existing['letter_grade']) ?></div>
                    </div>

                    <div class="result-item">
                        <div class="result-label">Grade Point</div>
                        <div class="result-value"><?= htmlspecialchars($existing['grade_point']) ?></div>
                    </div>

                    <div class="result-item">
                        <div class="result-label">Remark</div>
                        <div class="result-value"><?= htmlspecialchars($existing['remark']) ?></div>
                    </div>

                </div>

                <div>

                    <?php if ($existing['status'] === 'Returned'): ?>

                        <span class="status returned">⚠ Returned — Needs Correction</span>

                    <?php elseif ($existing['status'] === 'Submitted'): ?>

                        <span class="status submitted">✓ Submitted</span>

                    <?php elseif ($existing['status'] === 'Under Review'): ?>

                        <span class="status review">🔍 Under Review</span>

                    <?php elseif ($existing['status'] === 'Approved'): ?>

                        <span class="status approved">✓ Approved</span>

                    <?php elseif ($existing['status'] === 'Published'): ?>

                        <span class="status published">✓ Published</span>

                    <?php else: ?>

                        <span class="status draft">Draft</span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="field">

                <label>
                    CA Score <small>(Maximum 40)</small>
                </label>

                <input
                    type="number"
                    name="ca_score"
                    min="0"
                    max="40"
                    step="0.01"
                    value="<?= $existing ? htmlspecialchars($existing['ca_score']) : '' ?>"
                    required
                >

            </div>

            <div class="field">

                <label>
                    Exam Score <small>(Maximum 60)</small>
                </label>

                <input
                    type="number"
                    name="exam_score"
                    min="0"
                    max="60"
                    step="0.01"
                    value="<?= $existing ? htmlspecialchars($existing['exam_score']) : '' ?>"
                    required
                >

            </div>

            <button type="submit">
                <?= $existing ? 'Update Result' : 'Submit Result' ?>
            </button>

        </form>

    </div>

</div>

</body>

</html>