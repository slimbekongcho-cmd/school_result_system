<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['course_id'])) {
    die("Course not specified.");
}

$course_id = intval($_GET['course_id']);


/* =========================
   GET LECTURER
========================= */

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


/* =========================
   CHECK COURSE
========================= */

$course_sql = "SELECT course_id, course_code, course_title
               FROM courses
               WHERE course_id = ?
               AND lecturer_id = ?";

$course_stmt = mysqli_prepare($conn, $course_sql);

mysqli_stmt_bind_param(
    $course_stmt,
    "ii",
    $course_id,
    $lecturer_id
);

mysqli_stmt_execute($course_stmt);

$course_result = mysqli_stmt_get_result($course_stmt);
$course = mysqli_fetch_assoc($course_result);

if (!$course) {
    die("You are not assigned to this course.");
}


/* =========================
   GET REGISTERED STUDENTS
   + THEIR RESULTS
========================= */

$student_sql = "SELECT
                    cr.registration_id,
                    s.student_id,
                    s.first_name,
                    s.last_name,
                    s.matricule,
                    s.level,

                    r.result_id,
                    r.ca_score,
                    r.exam_score,
                    r.total_score,
                    r.letter_grade,
                    r.status

                FROM course_registrations cr

                INNER JOIN students s
                    ON cr.student_id = s.student_id

                LEFT JOIN results r
                    ON cr.registration_id = r.registration_id

                WHERE cr.course_id = ?

                ORDER BY s.first_name ASC";

$student_stmt = mysqli_prepare($conn, $student_sql);

mysqli_stmt_bind_param(
    $student_stmt,
    "i",
    $course_id
);

mysqli_stmt_execute($student_stmt);

$students_result = mysqli_stmt_get_result($student_stmt);
$total_students = mysqli_num_rows($students_result);

$students_list = [];
while ($row = mysqli_fetch_assoc($students_result)) {
    $students_list[] = $row;
}

/* Helper for status badge markup, reused for table + cards */
function render_status_badge($status) {
    if (!$status) {
        return '<span class="status none">Not Entered</span>';
    }
    switch ($status) {
        case 'Draft':
            return '<span class="status draft">Draft</span>';
        case 'Submitted':
            return '<span class="status submitted">Submitted</span>';
        case 'Under Review':
            return '<span class="status review">Under Review</span>';
        case 'Returned':
            return '<span class="status returned">⚠ Returned</span><div class="returned-notice">Needs Correction</div>';
        case 'Approved':
            return '<span class="status approved">✓ Approved</span>';
        case 'Published':
            return '<span class="status published">✓ Published</span>';
        default:
            return '<span class="status none">' . htmlspecialchars($status) . '</span>';
    }
}

function render_action_button($status, $registration_id) {
    if (!$status) {
        $label = "Enter Result";
        $class = "enter-button";
    } elseif ($status === 'Returned') {
        $label = "✏ Correct Result";
        $class = "correct-button";
    } else {
        $label = "View Result";
        $class = "enter-button";
    }

    return '<a class="action-button ' . $class . '" href="enter_results.php?registration_id=' . $registration_id . '">' . $label . '</a>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Registered Students - UniResult</title>

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
            max-width: 1300px;
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

        .content-header .titles h2 {
            color: #003366;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .content-header .titles .course-info {
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

        /* ===== SEARCH ===== */

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 100%;
            max-width: 380px;
            padding: 12px 16px 12px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%2394a3b8" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>') no-repeat 14px center;
            background-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        .search-bar input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        /* ===== TABLE CARD (desktop) ===== */

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
            min-width: 900px;
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

        .returned-row {
            background: #fffaf0;
        }

        .returned-row:hover {
            background: #fff5e6;
        }

        /* ===== ACTION BUTTON ===== */

        .action-button {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .enter-button {
            background: #003366;
            color: white;
        }

        .enter-button:hover {
            background: #00509e;
            transform: translateY(-1px);
        }

        .correct-button {
            background: #f59e0b;
            color: white;
        }

        .correct-button:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        /* ===== STATUS BADGES ===== */

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status.none {
            background: #e5e7eb;
            color: #374151;
        }

        .status.draft {
            background: #e5e7eb;
            color: #374151;
        }

        .status.submitted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status.review {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status.returned {
            background: #fef3c7;
            color: #92400e;
        }

        .status.approved {
            background: #d1fae5;
            color: #047857;
        }

        .status.published {
            background: #dcfce7;
            color: #166534;
        }

        .returned-notice {
            color: #b45309;
            font-size: 11px;
            margin-top: 4px;
            font-weight: 700;
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

        /* ===== MOBILE CARDS (hidden on desktop) ===== */

        .mobile-cards {
            display: none;
        }

        .student-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .student-card.returned {
            border-left-color: #f59e0b;
            background: #fffaf0;
        }

        .student-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .student-card .card-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .student-card .card-details {
            font-size: 13px;
            color: #64748b;
            line-height: 1.9;
            margin-bottom: 14px;
        }

        .student-card .card-details b {
            color: #334155;
        }

        .student-card .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
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

            .search-bar input {
                max-width: 100%;
            }

            .table-container {
                display: none;
            }

            .mobile-cards {
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
            <h2>
                <?= htmlspecialchars($course['course_code']) ?> —
                <?= htmlspecialchars($course['course_title']) ?>
            </h2>
            <p class="course-info">
                Students registered for this course
            </p>
        </div>

        <a href="dashboard.php" class="back-button">
            ← Back to Dashboard
        </a>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="studentSearch"
            placeholder="Search by name or matricule..."
            onkeyup="filterStudents()"
        >
    </div>


    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <table id="studentsTable">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Level</th>
                    <th>CA</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($total_students > 0): ?>

                <?php $number = 1; ?>

                <?php foreach ($students_list as $student): ?>

                    <?php $status = $student['status']; ?>

                    <tr <?= $status === 'Returned' ? 'class="returned-row"' : '' ?>>

                        <td><?= $number++ ?></td>

                        <td><?= htmlspecialchars($student['matricule']) ?></td>

                        <td>
                            <?= htmlspecialchars($student['first_name'] . " " . $student['last_name']) ?>
                        </td>

                        <td><?= htmlspecialchars($student['level']) ?></td>

                        <td><?= $student['ca_score'] !== null ? htmlspecialchars($student['ca_score']) : '-' ?></td>

                        <td><?= $student['exam_score'] !== null ? htmlspecialchars($student['exam_score']) : '-' ?></td>

                        <td><?= $student['total_score'] !== null ? htmlspecialchars($student['total_score']) : '-' ?></td>

                        <td><?= render_status_badge($status) ?></td>

                        <td><?= render_action_button($status, $student['registration_id']) ?></td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" class="empty">
                        <div class="empty-icon">📭</div>
                        No students have registered for this course yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards" id="studentsCards">

        <?php if ($total_students > 0): ?>

            <?php foreach ($students_list as $student): ?>

                <?php $status = $student['status']; ?>

                <div class="student-card <?= $status === 'Returned' ? 'returned' : '' ?>"
                     data-search="<?= strtolower(htmlspecialchars(
                        $student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['matricule']
                     )) ?>">

                    <div class="card-top">
                        <span class="card-name">
                            <?= htmlspecialchars($student['first_name'] . " " . $student['last_name']) ?>
                        </span>
                        <?= render_status_badge($status) ?>
                    </div>

                    <div class="card-details">
                        <div><b>Matricule:</b> <?= htmlspecialchars($student['matricule']) ?></div>
                        <div><b>Level:</b> <?= htmlspecialchars($student['level']) ?></div>
                        <div><b>CA:</b> <?= $student['ca_score'] !== null ? htmlspecialchars($student['ca_score']) : '-' ?></div>
                        <div><b>Exam:</b> <?= $student['exam_score'] !== null ? htmlspecialchars($student['exam_score']) : '-' ?></div>
                        <div><b>Total:</b> <?= $student['total_score'] !== null ? htmlspecialchars($student['total_score']) : '-' ?></div>
                    </div>

                    <div class="card-footer">
                        <?= render_action_button($status, $student['registration_id']) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No students have registered for this course yet.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function filterStudents() {

    const query = document.getElementById("studentSearch").value.toLowerCase();

    const rows = document.querySelectorAll("#studentsTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".student-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>

</html>