<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Get student */
$sql = "SELECT student_id, first_name, last_name, matricule
        FROM students
        WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Student profile not found.");
}

$student_id = $student['student_id'];

/* Get student's results */
$sql = "SELECT
            c.course_code,
            c.course_title,
            c.credit_unit,
            c.level,
            sem.semester_name,
            a.session_name,
            r.ca_score,
            r.exam_score,
            r.total_score,
            r.letter_grade,
            r.grade_point,
            r.remark,
            r.status
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

ORDER BY a.session_id DESC, sem.semester_id ASC, c.course_code ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$results = mysqli_stmt_get_result($stmt);
$total_results = mysqli_num_rows($results);

$results_list = [];
while ($row = mysqli_fetch_assoc($results)) {
    $results_list[] = $row;
}


/* Calculate overall CGPA */

$cgpa_sql = "SELECT
                COALESCE(SUM(r.grade_point * c.credit_unit), 0) AS total_points,
                COALESCE(SUM(c.credit_unit), 0) AS total_credits
             FROM results r
             INNER JOIN course_registrations cr
                 ON r.registration_id = cr.registration_id
             INNER JOIN courses c
                 ON cr.course_id = c.course_id
             WHERE cr.student_id = ?
             AND r.status = 'Published'";

$cgpa_stmt = mysqli_prepare($conn, $cgpa_sql);

mysqli_stmt_bind_param(
    $cgpa_stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($cgpa_stmt);

$cgpa_result = mysqli_stmt_get_result($cgpa_stmt);

$cgpa_data = mysqli_fetch_assoc($cgpa_result);

$total_points = (float) $cgpa_data['total_points'];
$total_credits = (float) $cgpa_data['total_credits'];

$cgpa = $total_credits > 0
    ? $total_points / $total_credits
    : 0;


/* Calculate GPA for each semester */

$semester_sql = "SELECT
                    sem.semester_id,
                    sem.semester_name,
                    a.session_id,
                    a.session_name,

                    COALESCE(
                        SUM(r.grade_point * c.credit_unit),
                        0
                    ) AS semester_points,

                    COALESCE(
                        SUM(c.credit_unit),
                        0
                    ) AS semester_credits

                 FROM results r

                 INNER JOIN course_registrations cr
                     ON r.registration_id = cr.registration_id

                 INNER JOIN courses c
                     ON cr.course_id = c.course_id

                 INNER JOIN semesters sem
                     ON cr.semester_id = sem.semester_id

                 INNER JOIN academic_sessions a
                     ON cr.session_id = a.session_id

                 WHERE cr.student_id = ?
                 AND r.status = 'Published'

                 GROUP BY
                     sem.semester_id,
                     sem.semester_name,
                     a.session_id,
                     a.session_name

                 ORDER BY
                     a.session_id DESC,
                     sem.semester_id ASC";                    

$semester_stmt = mysqli_prepare(
    $conn,
    $semester_sql
);

mysqli_stmt_bind_param(
    $semester_stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($semester_stmt);

$semester_results =
    mysqli_stmt_get_result($semester_stmt);

$total_semesters = mysqli_num_rows($semester_results);

$semesters_list = [];
$current_gpa = 0;

while ($row = mysqli_fetch_assoc($semester_results)) {

    $row['gpa'] = $row['semester_credits'] > 0
        ? $row['semester_points'] / $row['semester_credits']
        : 0;

    $semesters_list[] = $row;
}

/* Use the most recent semester (first row, since ordered DESC by session)
   as the "Current GPA" figure, distinct from CGPA */
if (!empty($semesters_list)) {
    $current_gpa = $semesters_list[0]['gpa'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Results - UniResult</title>

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
            max-width: 1200px;
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
            padding: 36px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }

        .cgpa-hero .cgpa-main {
            display: flex;
            align-items: baseline;
            gap: 14px;
        }

        .cgpa-hero .cgpa-value {
            font-size: 56px;
            font-weight: 800;
            line-height: 1;
        }

        .cgpa-hero .cgpa-label {
            font-size: 14px;
            opacity: 0.85;
            font-weight: 600;
        }

        .cgpa-hero .cgpa-sub {
            font-size: 13.5px;
            opacity: 0.8;
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
            margin-bottom: 30px;
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

        .print-result-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 20px;
            background: #003366;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 26px;
            transition: 0.2s ease;
        }

        .print-result-btn:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        /* ===== SECTION HEADER ===== */

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .section-header h2 {
            color: #003366;
            font-size: 19px;
            font-weight: 700;
        }

        /* ===== SEMESTER BREAKDOWN ===== */

        .semester-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .semester-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }

        .semester-card h3 {
            color: #1e293b;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .semester-card .session-label {
            color: #64748b;
            font-size: 12.5px;
            margin-bottom: 16px;
        }

        .semester-card .semester-gpa {
            font-size: 32px;
            font-weight: 800;
            color: #003366;
        }

        .semester-card .gpa-caption {
            font-size: 11.5px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .semester-card .credit-line {
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #eef1f5;
            padding-top: 10px;
        }

        .semester-card .credit-line b {
            color: #334155;
        }

        /* ===== RESULTS TABLE (desktop) ===== */

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #003366;
            color: white;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .grade {
            font-weight: 700;
            color: #003366;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status.published {
            background: #dcfce7;
            color: #166534;
        }

        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 10px;
        }

        /* ===== MOBILE CARDS (results, hidden on desktop) ===== */

        .mobile-results {
            display: none;
        }

        .result-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .result-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .result-card .course-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .result-card .grade-pill {
            background: #eaf1fb;
            color: #003366;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 15px;
        }

        .result-card .card-details {
            font-size: 13px;
            color: #64748b;
            line-height: 1.9;
        }

        .result-card .card-details b {
            color: #334155;
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
                padding: 20px 14px 40px;
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
                padding: 26px 22px;
            }

            .cgpa-hero .student-id-block {
                text-align: left;
            }

            .cgpa-hero .cgpa-value {
                font-size: 44px;
            }

            .table-container {
                display: none;
            }

            .mobile-results {
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
            <h1>My Results</h1>
            <p>A full breakdown of your academic performance.</p>
        </div>

        <a href="dashboard.php" class="back-button">
            ← Back to Dashboard
        </a>

    </div>


    <div class="cgpa-hero">

        <div>
            <div class="cgpa-main">
                <div class="cgpa-value"><?= number_format($cgpa, 2) ?></div>
                <div class="cgpa-label">CGPA</div>
            </div>
            <div class="cgpa-sub">
                Current Semester GPA: <?= number_format($current_gpa, 2) ?>
            </div>
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
            <div class="stat-value"><?= number_format($total_credits, 1) ?></div>
            <div class="stat-label">Total Credit Units</div>
        </div>

        <div class="stat-box">
            <div class="stat-value"><?= $total_semesters ?></div>
            <div class="stat-label">Semesters Recorded</div>
        </div>

        <div class="stat-box">
            <div class="stat-value"><?= $total_results ?></div>
            <div class="stat-label">Published Courses</div>
        </div>

    </div>


    <a href="print_result.php" class="print-result-btn">
        🖨️ Print Result
    </a>


    <?php if ($total_semesters > 0): ?>

        <div class="section-header">
            <h2>Semester Breakdown</h2>
        </div>

        <div class="semester-grid">

            <?php foreach ($semesters_list as $semester): ?>

                <div class="semester-card">

                    <h3><?= htmlspecialchars($semester['semester_name']) ?></h3>
                    <div class="session-label"><?= htmlspecialchars($semester['session_name']) ?></div>

                    <div class="gpa-caption">GPA</div>
                    <div class="semester-gpa"><?= number_format($semester['gpa'], 2) ?></div>

                    <div class="credit-line">
                        <b><?= number_format($semester['semester_credits'], 1) ?></b> credit units
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <div class="section-header">
        <h2>Academic Results</h2>
    </div>

    <?php if ($total_results > 0): ?>

        <!-- DESKTOP TABLE -->

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Course Code</th>
                        <th>Course Title</th>
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

                <?php foreach ($results_list as $row): ?>

                    <tr>

                        <td><?= htmlspecialchars($row['session_name']) ?></td>
                        <td><?= htmlspecialchars($row['semester_name']) ?></td>
                        <td><strong><?= htmlspecialchars($row['course_code']) ?></strong></td>
                        <td><?= htmlspecialchars($row['course_title']) ?></td>
                        <td><?= htmlspecialchars($row['credit_unit']) ?></td>
                        <td><?= htmlspecialchars($row['ca_score']) ?></td>
                        <td><?= htmlspecialchars($row['exam_score']) ?></td>
                        <td><strong><?= htmlspecialchars($row['total_score']) ?></strong></td>
                        <td class="grade"><?= htmlspecialchars($row['letter_grade']) ?></td>
                        <td><?= htmlspecialchars($row['grade_point']) ?></td>
                        <td><?= htmlspecialchars($row['remark']) ?></td>

                        <td>
                            <?php if ($row['status'] === 'Published'): ?>
                                <span class="status published">✓ Published</span>
                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <!-- MOBILE CARDS -->

        <div class="mobile-results">

            <?php foreach ($results_list as $row): ?>

                <div class="result-card">

                    <div class="card-top">
                        <div class="course-name">
                            <?= htmlspecialchars($row['course_code']) ?> — <?= htmlspecialchars($row['course_title']) ?>
                        </div>
                        <div class="grade-pill">
                            <?= htmlspecialchars($row['letter_grade']) ?>
                        </div>
                    </div>

                    <div class="card-details">
                        <div><b>Session:</b> <?= htmlspecialchars($row['session_name']) ?></div>
                        <div><b>Semester:</b> <?= htmlspecialchars($row['semester_name']) ?></div>
                        <div><b>Credit Unit:</b> <?= htmlspecialchars($row['credit_unit']) ?></div>
                        <div><b>CA:</b> <?= htmlspecialchars($row['ca_score']) ?> &nbsp; <b>Exam:</b> <?= htmlspecialchars($row['exam_score']) ?> &nbsp; <b>Total:</b> <?= htmlspecialchars($row['total_score']) ?></div>
                        <div><b>Grade Point:</b> <?= htmlspecialchars($row['grade_point']) ?></div>
                        <div><b>Remark:</b> <?= htmlspecialchars($row['remark']) ?></div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="table-container">
            <div class="empty">
                <div class="empty-icon">📭</div>
                No results are available yet.
            </div>
        </div>

    <?php endif; ?>

</div>

</body>

</html>