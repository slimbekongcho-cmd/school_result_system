<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT
            c.course_id,
            c.course_code,
            c.course_title,
            c.credit_unit,
            c.level,
            f.faculty_name,
            d.department_name,
            p.programme_name,
            s.semester_name,
            l.first_name,
            l.last_name
        FROM courses c

        LEFT JOIN faculties f
            ON c.faculty_id = f.faculty_id

        LEFT JOIN departments d
            ON c.department_id = d.department_id

        LEFT JOIN programmes p
            ON c.programme_id = p.programme_id

        LEFT JOIN semesters s
            ON c.semester_id = s.semester_id

        LEFT JOIN lecturers l
            ON c.lecturer_id = l.lecturer_id

        ORDER BY c.course_id DESC";

$result = mysqli_query($conn, $sql);
$total_courses = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Courses - UniResult</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            color: #1e293b;
        }

        /* ===== HEADER ===== */

        .header {
            background: #003366;
            color: white;
            padding: 22px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .badge-count {
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ===== CONTAINER ===== */

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 30px auto;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .top h2 {
            margin: 0;
            color: #003366;
            font-size: 22px;
            font-weight: 700;
        }

        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ===== BUTTONS ===== */

        .button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .add-button {
            background: #003366;
        }

        .add-button:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        .back-button {
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .back-button:hover {
            background: #eef2f7;
            transform: translateY(-2px);
        }

        .edit-button {
            background: #f59e0b;
        }

        .edit-button:hover {
            background: #d97706;
        }

        .delete-button {
            background: #dc2626;
        }

        .delete-button:hover {
            background: #b91c1c;
        }

        /* ===== SEARCH ===== */

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 100%;
            max-width: 380px;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%2394a3b8" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>') no-repeat 14px center;
            background-size: 15px;
            padding-left: 40px;
            outline: none;
            transition: 0.2s ease;
        }

        .search-bar input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        /* ===== TABLE (desktop) ===== */

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 12px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #003366;
            color: white;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            position: sticky;
            top: 0;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        tbody tr {
            transition: 0.15s ease;
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

        .level-pill {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
        }

        /* Action buttons */

        .actions {
            display: flex;
            gap: 7px;
        }

        .actions .button {
            padding: 8px 12px;
            font-size: 13px;
        }

        /* Empty */

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 40px;
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
            margin-bottom: 10px;
            gap: 10px;
        }

        .course-card .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }

        .course-card .card-details {
            font-size: 13px;
            color: #64748b;
            line-height: 1.9;
        }

        .course-card .card-details b {
            color: #334155;
        }

        .course-card .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 14px;
        }

        .course-card .card-actions .button {
            flex: 1;
            justify-content: center;
        }

        /* ===== MOBILE ===== */

        @media (max-width: 768px) {

            .header {
                padding: 18px 20px;
            }

            .header h1 {
                font-size: 19px;
            }

            .container {
                width: 92%;
                margin: 20px auto;
            }

            .top {
                flex-direction: column;
                align-items: stretch;
            }

            .top h2 {
                font-size: 20px;
            }

            .top-actions {
                width: 100%;
            }

            .top-actions .button {
                flex: 1;
                justify-content: center;
            }

            .search-bar input {
                max-width: 100%;
            }

            /* Hide table, show cards instead */

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

<div class="header">

     <div class="brand">
    <?php include __DIR__ . '/../../brand.html'; ?>
</div>
    <span class="badge-count">
        <?= $total_courses ?> total
    </span>

</div>


<div class="container">

    <div class="top">

        <h2>Course Management</h2>

        <div class="top-actions">

            <a href="../dashboard.php" class="button back-button">
                ← Back to Dashboard
            </a>

            <a href="add.php" class="button add-button">
                + Add Course
            </a>

        </div>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="courseSearch"
            placeholder="Search by course code, title, or lecturer..."
            onkeyup="filterCourses()"
        >
    </div>


    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <table id="coursesTable">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Code</th>
                    <th>Course Title</th>
                    <th>Credit</th>
                    <th>Level</th>
                    <th>Faculty</th>
                    <th>Department</th>
                    <th>Programme</th>
                    <th>Semester</th>
                    <th>Lecturer</th>
                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php if ($total_courses > 0): ?>

                <?php mysqli_data_seek($result, 0); ?>
                <?php while ($course = mysqli_fetch_assoc($result)): ?>

                    <tr>

                        <td>
                            <?= $course['course_id'] ?>
                        </td>

                        <td>
                            <span class="code-pill">
                                <?= htmlspecialchars($course['course_code']) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($course['course_title']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($course['credit_unit']) ?>
                        </td>

                        <td>
                            <span class="level-pill">
                                <?= htmlspecialchars($course['level']) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($course['faculty_name'] ?? '—') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($course['department_name'] ?? '—') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($course['programme_name'] ?? '—') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($course['semester_name'] ?? '—') ?>
                        </td>

                        <td>

                            <?= htmlspecialchars(
                                trim(
                                    ($course['first_name'] ?? '') . " " .
                                    ($course['last_name'] ?? '')
                                ) ?: '—'
                            ) ?>

                        </td>

                        <td>

                            <div class="actions">

                                <a
                                    href="edit.php?id=<?= $course['course_id'] ?>"
                                    class="button edit-button"
                                >
                                    ✏ Edit
                                </a>

                                <a
                                    href="delete.php?id=<?= $course['course_id'] ?>"
                                    class="button delete-button"
                                    onclick="return confirm('Are you sure you want to delete this course?');"
                                >
                                    🗑 Delete
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="11" class="empty">
                        <div class="empty-icon">📭</div>
                        No courses have been created yet.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards" id="coursesCards">

        <?php if ($total_courses > 0): ?>

            <?php mysqli_data_seek($result, 0); ?>
            <?php while ($course = mysqli_fetch_assoc($result)): ?>

                <div class="course-card"
                     data-search="<?= strtolower(htmlspecialchars(
                        $course['course_code'] . ' ' .
                        $course['course_title'] . ' ' .
                        ($course['first_name'] ?? '') . ' ' .
                        ($course['last_name'] ?? '')
                     )) ?>">

                    <div class="card-top">

                        <div>
                            <span class="code-pill">
                                <?= htmlspecialchars($course['course_code']) ?>
                            </span>
                        </div>

                        <span class="level-pill">
                            Level <?= htmlspecialchars($course['level']) ?>
                        </span>

                    </div>

                    <div class="card-title">
                        <?= htmlspecialchars($course['course_title']) ?>
                    </div>

                    <div class="card-details">
                        <div><b>Credit:</b> <?= htmlspecialchars($course['credit_unit']) ?></div>
                        <div><b>Faculty:</b> <?= htmlspecialchars($course['faculty_name'] ?? '—') ?></div>
                        <div><b>Department:</b> <?= htmlspecialchars($course['department_name'] ?? '—') ?></div>
                        <div><b>Programme:</b> <?= htmlspecialchars($course['programme_name'] ?? '—') ?></div>
                        <div><b>Semester:</b> <?= htmlspecialchars($course['semester_name'] ?? '—') ?></div>
                        <div><b>Lecturer:</b>
                            <?= htmlspecialchars(
                                trim(
                                    ($course['first_name'] ?? '') . " " .
                                    ($course['last_name'] ?? '')
                                ) ?: '—'
                            ) ?>
                        </div>
                    </div>

                    <div class="card-actions">

                        <a
                            href="edit.php?id=<?= $course['course_id'] ?>"
                            class="button edit-button"
                        >
                            ✏ Edit
                        </a>

                        <a
                            href="delete.php?id=<?= $course['course_id'] ?>"
                            class="button delete-button"
                            onclick="return confirm('Are you sure you want to delete this course?');"
                        >
                            🗑 Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No courses have been created yet.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function filterCourses() {

    const query = document.getElementById("courseSearch").value.toLowerCase();

    // Filter desktop table rows
    const rows = document.querySelectorAll("#coursesTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    // Filter mobile cards
    const cards = document.querySelectorAll(".course-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>

</html>