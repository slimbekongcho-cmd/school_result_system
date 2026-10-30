<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

/*
 * Get the logged-in student's details
 */
$sql = "SELECT student_id, programme_id, level
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
$programme_id = $student['programme_id'];
$level = $student['level'];


/*
 * Get active academic session
 */
$session_sql = "SELECT session_id, session_name
                FROM academic_sessions
                WHERE is_active = 1
                LIMIT 1";

$session_result = mysqli_query($conn, $session_sql);
$session = mysqli_fetch_assoc($session_result);

if (!$session) {
    die("No active academic session has been created.");
}

$session_id = $session['session_id'];


/*
 * Get active semester
 */
$semester_sql = "SELECT semester_id, semester_name
                 FROM semesters
                 WHERE session_id = ?
                 AND is_active = 1
                 LIMIT 1";

$semester_stmt = mysqli_prepare($conn, $semester_sql);

mysqli_stmt_bind_param(
    $semester_stmt,
    "i",
    $session_id
);

mysqli_stmt_execute($semester_stmt);

$semester_result = mysqli_stmt_get_result($semester_stmt);

$semester = mysqli_fetch_assoc($semester_result);

if (!$semester) {
    die("No active semester has been created for this academic session.");
}

$semester_id = $semester['semester_id'];


/*
 * Register course
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $course_id = intval($_POST['course_id']);

    /*
     * Check if already registered
     */
    $check_sql = "SELECT registration_id
                  FROM course_registrations
                  WHERE student_id = ?
                  AND course_id = ?
                  AND semester_id = ?
                  AND session_id = ?";

    $check_stmt = mysqli_prepare($conn, $check_sql);

    mysqli_stmt_bind_param(
        $check_stmt,
        "iiii",
        $student_id,
        $course_id,
        $semester_id,
        $session_id
    );

    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {

        $error = "You have already registered this course.";

    } else {

        /*
         * Make sure the course belongs to the student's
         * programme and level.
         */
        $course_check_sql = "SELECT course_id
                             FROM courses
                             WHERE course_id = ?
                             AND programme_id = ?
                             AND level = ?
                             AND semester_id = ?";

        $course_check_stmt = mysqli_prepare(
            $conn,
            $course_check_sql
        );

        mysqli_stmt_bind_param(
            $course_check_stmt,
            "iiii",
            $course_id,
            $programme_id,
            $level,
            $semester_id
        );

        mysqli_stmt_execute($course_check_stmt);

        $course_check_result =
            mysqli_stmt_get_result($course_check_stmt);

        if (mysqli_num_rows($course_check_result) == 0) {

            $error = "This course is not available for your programme or level.";

        } else {

            /*
             * Register course
             */
            $insert_sql = "INSERT INTO course_registrations
                           (
                               student_id,
                               course_id,
                               semester_id,
                               session_id
                           )
                           VALUES (?, ?, ?, ?)";

            $insert_stmt = mysqli_prepare(
                $conn,
                $insert_sql
            );

            mysqli_stmt_bind_param(
                $insert_stmt,
                "iiii",
                $student_id,
                $course_id,
                $semester_id,
                $session_id
            );

            if (mysqli_stmt_execute($insert_stmt)) {

                $message = "Course registered successfully.";

            } else {

                $error = "Unable to register course.";
            }
        }
    }
}


/*
 * Get courses available for this student
 */
$courses_sql = "SELECT
                    c.course_id,
                    c.course_code,
                    c.course_title,
                    c.credit_unit,
                    l.first_name,
                    l.last_name
                FROM courses c

                LEFT JOIN lecturers l
                    ON c.lecturer_id = l.lecturer_id

                WHERE c.programme_id = ?
                AND c.level = ?
                AND c.semester_id = ?

                ORDER BY c.course_code ASC";

$courses_stmt = mysqli_prepare(
    $conn,
    $courses_sql
);

mysqli_stmt_bind_param(
    $courses_stmt,
    "iii",
    $programme_id,
    $level,
    $semester_id
);

mysqli_stmt_execute($courses_stmt);

$courses_result =
    mysqli_stmt_get_result($courses_stmt);

$total_courses = mysqli_num_rows($courses_result);

$courses_list = [];
while ($row = mysqli_fetch_assoc($courses_result)) {
    $courses_list[] = $row;
}


/*
 * Get student's registered courses
 */
$registered_sql = "SELECT
                       c.course_id,
                       c.course_code,
                       c.course_title,
                       c.credit_unit
                   FROM course_registrations cr

                   INNER JOIN courses c
                       ON cr.course_id = c.course_id

                   WHERE cr.student_id = ?
                   AND cr.semester_id = ?
                   AND cr.session_id = ?

                   ORDER BY c.course_code ASC";

$registered_stmt = mysqli_prepare(
    $conn,
    $registered_sql
);

mysqli_stmt_bind_param(
    $registered_stmt,
    "iii",
    $student_id,
    $semester_id,
    $session_id
);

mysqli_stmt_execute($registered_stmt);

$registered_result =
    mysqli_stmt_get_result($registered_stmt);

$total_registered = mysqli_num_rows($registered_result);

$registered_list = [];
$registered_credits = 0;
$registered_course_ids = [];

while ($row = mysqli_fetch_assoc($registered_result)) {
    $registered_list[] = $row;
    $registered_credits += (int) $row['credit_unit'];
    $registered_course_ids[$row['course_code']] = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Course Registration - UniResult</title>

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
            background: white;
            color: black;
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
            max-width: 1100px;
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

        /* ===== SESSION INFO BANNER ===== */

        .session-banner {
            background: linear-gradient(135deg, #003366, #00509e);
            color: white;
            padding: 24px 28px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .session-banner .info-block .info-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .session-banner .info-block .info-value {
            font-size: 17px;
            font-weight: 700;
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

        .badge-count {
            background: #eaf1fb;
            color: #003366;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ===== TABLE CARD (desktop) ===== */

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
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

        .code-pill {
            display: inline-block;
            background: #eaf1fb;
            color: #003366;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
        }

        button {
            background: #003366;
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        button:hover {
            background: #00509e;
            transform: translateY(-1px);
        }

        .registered-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #dcfce7;
            color: #166534;
            padding: 8px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 34px;
            margin-bottom: 10px;
        }

        /* ===== MOBILE CARDS (hidden on desktop) ===== */

        .mobile-cards {
            display: none;
        }

        .course-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .course-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .course-card .course-name {
            font-size: 14.5px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }

        .course-card .card-details {
            font-size: 13px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 14px;
        }

        .course-card .card-details b {
            color: #334155;
        }

        .course-card form,
        .course-card .registered-pill {
            width: 100%;
        }

        .course-card button {
            width: 100%;
        }

        /* ===== MOBILE ===== */

        @media (max-width: 800px) {

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

            .session-banner {
                padding: 20px;
                flex-direction: column;
                gap: 14px;
            }

            .table-container {
                display: none;
            }

            .mobile-cards {
                display: block;
                margin-bottom: 30px;
            }

        }
        .logo {
        font-family: "Sora", sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: .3px;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .logo-mark {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--light-blue), var(--blue));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 800;
        color: var(--navy-deep);
        flex-shrink: 0;
    }

    .logo span { color: #7fbaff; }


    </style>

</head>

<body>

<?php include '../navbar.html'; ?>


<div class="container">

    <div class="content-header">

        <h1>Course Registration</h1>

        <a href="dashboard.php" class="back-button">
            ← Back to Dashboard
        </a>

    </div>


    <div class="session-banner">

        <div class="info-block">
            <div class="info-label">Academic Session</div>
            <div class="info-value"><?= htmlspecialchars($session['session_name']) ?></div>
        </div>

        <div class="info-block">
            <div class="info-label">Semester</div>
            <div class="info-value"><?= htmlspecialchars($semester['semester_name']) ?></div>
        </div>

        <div class="info-block">
            <div class="info-label">Registered Courses</div>
            <div class="info-value"><?= $total_registered ?> (<?= $registered_credits ?> units)</div>
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


    <div class="section-header">
        <h2>Available Courses</h2>
        <span class="badge-count"><?= $total_courses ?> total</span>
    </div>

    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course</th>
                    <th>Credit</th>
                    <th>Lecturer</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($total_courses > 0): ?>

                <?php foreach ($courses_list as $course): ?>

                    <?php $already = isset($registered_course_ids[$course['course_code']]); ?>

                    <tr>

                        <td><span class="code-pill"><?= htmlspecialchars($course['course_code']) ?></span></td>
                        <td><?= htmlspecialchars($course['course_title']) ?></td>
                        <td><?= htmlspecialchars($course['credit_unit']) ?></td>
                        <td><?= htmlspecialchars($course['first_name'] . " " . $course['last_name']) ?></td>

                        <td>

                            <?php if ($already): ?>

                                <span class="registered-pill">✓ Registered</span>

                            <?php else: ?>

                                <form method="POST">
                                    <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                                    <button type="submit">Register</button>
                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="empty">
                        <div class="empty-icon">📭</div>
                        No courses are available for your programme, level, and current semester.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards">

        <?php if ($total_courses > 0): ?>

            <?php foreach ($courses_list as $course): ?>

                <?php $already = isset($registered_course_ids[$course['course_code']]); ?>

                <div class="course-card">

                    <div class="card-top">
                        <span class="code-pill"><?= htmlspecialchars($course['course_code']) ?></span>
                    </div>

                    <div class="course-name"><?= htmlspecialchars($course['course_title']) ?></div>

                    <div class="card-details">
                        <div><b>Credit:</b> <?= htmlspecialchars($course['credit_unit']) ?></div>
                        <div><b>Lecturer:</b> <?= htmlspecialchars($course['first_name'] . " " . $course['last_name']) ?></div>
                    </div>

                    <?php if ($already): ?>

                        <span class="registered-pill">✓ Registered</span>

                    <?php else: ?>

                        <form method="POST">
                            <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                            <button type="submit">Register</button>
                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No courses are available for your programme, level, and current semester.
            </div>

        <?php endif; ?>

    </div>


    <div class="section-header">
        <h2>My Registered Courses</h2>
        <span class="badge-count"><?= $total_registered ?> total</span>
    </div>

    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course</th>
                    <th>Credit</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($total_registered > 0): ?>

                <?php foreach ($registered_list as $registered): ?>

                    <tr>
                        <td><span class="code-pill"><?= htmlspecialchars($registered['course_code']) ?></span></td>
                        <td><?= htmlspecialchars($registered['course_title']) ?></td>
                        <td><?= htmlspecialchars($registered['credit_unit']) ?></td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="3" class="empty">
                        <div class="empty-icon">📭</div>
                        You have not registered any courses yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards">

        <?php if ($total_registered > 0): ?>

            <?php foreach ($registered_list as $registered): ?>

                <div class="course-card">

                    <div class="card-top">
                        <span class="code-pill"><?= htmlspecialchars($registered['course_code']) ?></span>
                    </div>

                    <div class="course-name"><?= htmlspecialchars($registered['course_title']) ?></div>

                    <div class="card-details">
                        <div><b>Credit:</b> <?= htmlspecialchars($registered['credit_unit']) ?></div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                You have not registered any courses yet.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>