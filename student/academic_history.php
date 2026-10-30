<?php
session_start();
require_once "../config/database.php";

/* Only students can access this page */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


/* =========================
   GET STUDENT INFORMATION
========================= */

$sql = "SELECT
            student_id,
            first_name,
            last_name,
            matricule
        FROM students
        WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Student profile not found.");
}

$student_id = $student['student_id'];


/* =========================
   GET PUBLISHED RESULTS
========================= */

$sql = "SELECT
            r.result_id,
            r.ca_score,
            r.exam_score,
            r.total_score,
            r.letter_grade,
            r.grade_point,
            r.remark,
            r.status,

            c.course_code,
            c.course_title,
            c.credit_unit,

            sem.semester_id,
            sem.semester_name,

            a.session_id,
            a.session_name

        FROM results r

        INNER JOIN course_registrations cr
            ON r.registration_id = cr.registration_id

        INNER JOIN courses c
            ON cr.course_id = c.course_id

        LEFT JOIN semesters sem
            ON cr.semester_id = sem.semester_id

        LEFT JOIN academic_sessions a
            ON cr.session_id = a.session_id

        WHERE cr.student_id = ?
        AND r.status = 'Published'

        ORDER BY
            a.session_id DESC,
            sem.semester_id ASC,
            c.course_code ASC";


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$results = mysqli_stmt_get_result($stmt);


/* =========================
   ORGANIZE RESULTS
   BY SESSION AND SEMESTER
========================= */

$history = [];

while ($row = mysqli_fetch_assoc($results)) {

    $session_name = $row['session_name'] ?? 'Academic Session';
    $session_id   = $row['session_id'] ?? 0;

    $semester_name = $row['semester_name'] ?? 'Semester';
    $semester_id   = $row['semester_id'] ?? 0;


    if (!isset($history[$session_id])) {

        $history[$session_id] = [
            'session_name' => $session_name,
            'semesters' => []
        ];
    }


    if (!isset(
        $history[$session_id]['semesters'][$semester_id]
    )) {

        $history[$session_id]['semesters'][$semester_id] = [
            'semester_name' => $semester_name,
            'courses' => [],
            'points' => 0,
            'credits' => 0
        ];
    }


    $history[$session_id]['semesters'][$semester_id]['courses'][] = $row;


    /* Calculate semester points */

    $credit = (float) $row['credit_unit'];
    $grade_point = (float) $row['grade_point'];

    $history[$session_id]['semesters'][$semester_id]['points']
        += $grade_point * $credit;

    $history[$session_id]['semesters'][$semester_id]['credits']
        += $credit;
}


/* =========================
   OVERALL CGPA
========================= */

$total_points = 0;
$total_credits = 0;

foreach ($history as $session) {

    foreach ($session['semesters'] as $semester) {

        $total_points += $semester['points'];
        $total_credits += $semester['credits'];
    }
}

$cgpa = 0;

if ($total_credits > 0) {
    $cgpa = $total_points / $total_credits;
}


/* =========================
   COUNT COURSES
========================= */

$total_courses = 0;

foreach ($history as $session) {

    foreach ($session['semesters'] as $semester) {

        $total_courses += count($semester['courses']);
    }
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

    <title>Academic History - UniResult</title>


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
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 0 60px;
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

        .content-header .titles h1 {
            color: #003366;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .content-header .titles p {
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

        /* ===== CGPA HERO ===== */

        .cgpa-hero {
            background: radial-gradient(circle at top right, #00509e, #003366);
            color: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }

        .cgpa-hero .cgpa-value {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
        }

        .cgpa-hero .cgpa-label {
            font-size: 13px;
            opacity: 0.85;
            font-weight: 600;
            margin-top: 6px;
        }

        .cgpa-hero .student-id-block {
            text-align: right;
        }

        .cgpa-hero .student-id-block .name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .cgpa-hero .student-id-block .matric {
            font-size: 13px;
            opacity: 0.85;
        }

        /* ===== QUICK STATS ===== */

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .stat-box {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            border-top: 4px solid #d99a2b;
        }

        .stat-box .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #003366;
            margin-bottom: 4px;
        }

        .stat-box .stat-label {
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ===== PRINT BUTTON ===== */

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #003366;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 26px;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .print-btn:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        /* ===== SESSION CARD ===== */

        .session-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 26px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .session-header {
            background: #003366;
            color: white;
            padding: 16px 24px;
        }

        .session-header h2 {
            font-size: 17px;
            font-weight: 700;
        }

        /* ===== SEMESTER ===== */

        .semester {
            padding: 24px;
            border-bottom: 1px solid #eef1f5;
        }

        .semester:last-child {
            border-bottom: none;
        }

        .semester-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .semester-title h3 {
            color: #1e293b;
            font-size: 16px;
            font-weight: 700;
        }

        .semester-gpa {
            background: #eaf1fb;
            color: #003366;
            padding: 7px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
        }

        /* ===== TABLE (desktop) ===== */

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f4f7fb;
            color: #003366;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        tr:hover {
            background: #f8fafc;
        }

        .grade {
            font-weight: 800;
            color: #003366;
        }

        .published {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 5px 11px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }

        /* ===== MOBILE CARDS (per course, hidden on desktop) ===== */

        .mobile-courses {
            display: none;
        }

        .course-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid #003366;
        }

        .course-card:last-child {
            margin-bottom: 0;
        }

        .course-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .course-card .course-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        .course-card .grade-pill {
            background: #eaf1fb;
            color: #003366;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .course-card .card-details {
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.8;
        }

        .course-card .card-details b {
            color: #334155;
        }

        /* ===== EMPTY ===== */

        .empty {
            background: white;
            padding: 50px 20px;
            text-align: center;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .empty h2 {
            color: #003366;
            font-size: 19px;
            margin-bottom: 8px;
        }

        .empty p {
            color: #64748b;
            font-size: 14px;
        }

        /* ===== MOBILE ===== */

        @media (max-width: 900px) {

            .navbar {
                padding: 14px 18px;
            }

            .navbar .brand {
                font-size: 16px;
            }

            .container {
                width: 92%;
                padding: 20px 0 40px;
            }

            .content-header {
                flex-direction: column;
                align-items: stretch;
            }

            .back-button {
                justify-content: center;
            }

            .cgpa-hero {
                flex-direction: column;
                align-items: flex-start;
                padding: 24px 22px;
            }

            .cgpa-hero .student-id-block {
                text-align: left;
            }

            .cgpa-hero .cgpa-value {
                font-size: 38px;
            }

            .semester-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .table-container {
                display: none;
            }

            .mobile-courses {
                display: block;
            }

        }

    </style>

</head>


<body>

<?php include '../navbar.html'; ?>


<div class="container">

    <div class="content-header">

        <div class="titles">
            <h1>Academic History</h1>
            <p>A session-by-session breakdown of your academic performance.</p>
        </div>

        <a href="dashboard.php" class="back-button">
            ← Back to Dashboard
        </a>

    </div>


    <?php if (!empty($history)): ?>

        <div class="cgpa-hero">

            <div>
                <div class="cgpa-value"><?= number_format($cgpa, 2) ?></div>
                <div class="cgpa-label">Cumulative GPA (CGPA)</div>
            </div>

            <div class="student-id-block">
                <div class="name">
                    <?= htmlspecialchars($student['first_name'] . " " . $student['last_name']) ?>
                </div>
                <div class="matric">
                    <?= htmlspecialchars($student['matricule']) ?>
                </div>
            </div>

        </div>


        <div class="quick-stats">

            <div class="stat-box">
                <div class="stat-value"><?= $total_courses ?></div>
                <div class="stat-label">Total Courses</div>
            </div>

            <div class="stat-box">
                <div class="stat-value"><?= number_format($total_credits, 1) ?></div>
                <div class="stat-label">Total Credits</div>
            </div>

            <div class="stat-box">
                <div class="stat-value"><?= count($history) ?></div>
                <div class="stat-label">Academic Sessions</div>
            </div>

        </div>


        <a href="print_result.php" class="print-btn">
            🖨️ Print Result
        </a>


        <!-- ACADEMIC HISTORY -->

        <?php foreach ($history as $session): ?>

            <div class="session-card">

                <div class="session-header">
                    <h2><?= htmlspecialchars($session['session_name']) ?></h2>
                </div>

                <?php foreach ($session['semesters'] as $semester): ?>

                    <?php
                    $semester_gpa = 0;
                    if ($semester['credits'] > 0) {
                        $semester_gpa = $semester['points'] / $semester['credits'];
                    }
                    ?>

                    <div class="semester">

                        <div class="semester-title">
                            <h3><?= htmlspecialchars($semester['semester_name']) ?></h3>
                            <span class="semester-gpa">GPA: <?= number_format($semester_gpa, 2) ?></span>
                        </div>


                        <!-- DESKTOP TABLE -->

                        <div class="table-container">

                            <table>

                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course</th>
                                        <th>Credit</th>
                                        <th>CA</th>
                                        <th>Exam</th>
                                        <th>Total</th>
                                        <th>Grade</th>
                                        <th>GP</th>
                                        <th>Remark</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>

                                <?php foreach ($semester['courses'] as $course): ?>

                                    <tr>
                                        <td><strong><?= htmlspecialchars($course['course_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($course['course_title']) ?></td>
                                        <td><?= htmlspecialchars($course['credit_unit']) ?></td>
                                        <td><?= htmlspecialchars($course['ca_score']) ?></td>
                                        <td><?= htmlspecialchars($course['exam_score']) ?></td>
                                        <td><strong><?= htmlspecialchars($course['total_score']) ?></strong></td>
                                        <td class="grade"><?= htmlspecialchars($course['letter_grade']) ?></td>
                                        <td><?= htmlspecialchars($course['grade_point']) ?></td>
                                        <td><?= htmlspecialchars($course['remark']) ?></td>
                                        <td><span class="published">✓ Published</span></td>
                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <!-- MOBILE CARDS -->

                        <div class="mobile-courses">

                            <?php foreach ($semester['courses'] as $course): ?>

                                <div class="course-card">

                                    <div class="card-top">
                                        <span class="course-name">
                                            <?= htmlspecialchars($course['course_code']) ?> —
                                            <?= htmlspecialchars($course['course_title']) ?>
                                        </span>
                                        <span class="grade-pill"><?= htmlspecialchars($course['letter_grade']) ?></span>
                                    </div>

                                    <div class="card-details">
                                        <div><b>Credit:</b> <?= htmlspecialchars($course['credit_unit']) ?></div>
                                        <div><b>CA:</b> <?= htmlspecialchars($course['ca_score']) ?> &nbsp; <b>Exam:</b> <?= htmlspecialchars($course['exam_score']) ?> &nbsp; <b>Total:</b> <?= htmlspecialchars($course['total_score']) ?></div>
                                        <div><b>Grade Point:</b> <?= htmlspecialchars($course['grade_point']) ?></div>
                                        <div><b>Remark:</b> <?= htmlspecialchars($course['remark']) ?></div>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            <div class="empty-icon">📭</div>
            <h2>No Academic History</h2>
            <p>You don't have any published results yet.</p>
        </div>

    <?php endif; ?>

</div>

</body>

</html>