<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Get lecturer information */
$sql = "SELECT lecturer_id, first_name, last_name, staff_id
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


/* Get courses assigned to this lecturer */
$course_sql = "SELECT
                   c.course_id,
                   c.course_code,
                   c.course_title,
                   c.credit_unit,
                   c.level,
                   s.semester_name
               FROM courses c

               LEFT JOIN semesters s
                   ON c.semester_id = s.semester_id

               WHERE c.lecturer_id = ?

               ORDER BY c.course_code ASC";

$course_stmt = mysqli_prepare($conn, $course_sql);

mysqli_stmt_bind_param(
    $course_stmt,
    "i",
    $lecturer_id
);

mysqli_stmt_execute($course_stmt);

$courses_result = mysqli_stmt_get_result($course_stmt);
$total_courses = mysqli_num_rows($courses_result);

/* Compute total credit units and distinct semesters/levels while we're at it */
$total_credits = 0;
$semesters_seen = [];
$levels_seen = [];

$courses_list = [];

while ($row = mysqli_fetch_assoc($courses_result)) {
    $courses_list[] = $row;
    $total_credits += (int) $row['credit_unit'];
    if (!empty($row['semester_name'])) {
        $semesters_seen[$row['semester_name']] = true;
    }
    if (!empty($row['level'])) {
        $levels_seen[$row['level']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Lecturer Dashboard - UniResult</title>

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
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        /* ===== CONTENT HEADER ===== */

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .content-header h1 {
            color: #003366;
            font-size: 22px;
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

        /* ===== WELCOME BANNER ===== */

        .welcome {
            background: linear-gradient(135deg, #003366, #00509e);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .welcome h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .welcome p {
            font-size: 14px;
            opacity: 0.9;
        }

        .welcome .staff-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ===== STATS ROW ===== */

        .stats-row {
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
            font-size: 28px;
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

        /* ===== SECTION HEADER ===== */

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .section-header h2 {
            color: #003366;
            font-size: 20px;
            font-weight: 700;
        }

        .search-box input {
            padding: 10px 14px 10px 36px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            width: 240px;
            max-width: 100%;
            outline: none;
            background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="%2394a3b8" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>') no-repeat 12px center;
            background-size: 13px;
            transition: 0.2s ease;
        }

        .search-box input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        /* ===== COURSE GRID ===== */

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
        }

        .course-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transition: 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.1);
        }

        .course-card .code-pill {
            display: inline-block;
            background: #fdf3e3;
            color: #b5760f;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 12px;
            align-self: flex-start;
        }

        .course-card h3 {
            color: #1e293b;
            font-size: 16.5px;
            font-weight: 700;
            margin-bottom: 14px;
            line-height: 1.4;
        }

        .course-card .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .course-card .meta span {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
        }

        .course-card .button {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #003366;
            color: white;
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .course-card .button:hover {
            background: #00509e;
            transform: translateY(-1px);
        }

        /* ===== EMPTY STATE ===== */

        .empty {
            background: white;
            border-radius: 14px;
            padding: 50px 20px;
            text-align: center;
            color: #64748b;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        /* ===== MOBILE ===== */

        @media (max-width: 700px) {

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

            .welcome {
                padding: 22px;
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome h2 {
                font-size: 21px;
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box input {
                width: 100%;
            }

            .course-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include '../navbar.html'; ?>


<div class="container">

    <div class="content-header">
        <h1>Dashboard</h1>

        <a href="../index.php" class="back-button">
            ← Back to Home
        </a>
    </div>

    <div class="welcome">

        <div>
            <h2>Welcome, <?= htmlspecialchars($lecturer['first_name']) ?> 👋</h2>
            <p>Here's an overview of your teaching load this semester.</p>
        </div>

        <div class="staff-badge">
            Staff ID: <?= htmlspecialchars($lecturer['staff_id']) ?>
        </div>

    </div>


    <div class="stats-row">

        <div class="stat-box">
            <div class="stat-value"><?= $total_courses ?></div>
            <div class="stat-label">Courses Assigned</div>
        </div>

        <div class="stat-box">
            <div class="stat-value"><?= $total_credits ?></div>
            <div class="stat-label">Total Credit Units</div>
        </div>

        <div class="stat-box">
            <div class="stat-value"><?= count($levels_seen) ?></div>
            <div class="stat-label">Levels Taught</div>
        </div>

        <div class="stat-box">
            <div class="stat-value"><?= count($semesters_seen) ?></div>
            <div class="stat-label">Active Semesters</div>
        </div>

    </div>


    <div class="section-header">

        <h2>My Courses</h2>

        <div class="search-box">
            <input
                type="text"
                id="courseSearch"
                placeholder="Search my courses..."
                onkeyup="filterCourses()"
            >
        </div>

    </div>


    <?php if ($total_courses > 0): ?>

        <div class="course-grid" id="courseGrid">

            <?php foreach ($courses_list as $course): ?>

                <div class="course-card"
                     data-search="<?= strtolower(htmlspecialchars(
                        $course['course_code'] . ' ' . $course['course_title']
                     )) ?>">

                    <span class="code-pill">
                        <?= htmlspecialchars($course['course_code']) ?>
                    </span>

                    <h3>
                        <?= htmlspecialchars($course['course_title']) ?>
                    </h3>

                    <div class="meta">
                        <span>🎯 <?= htmlspecialchars($course['credit_unit']) ?> Units</span>
                        <span>📶 Level <?= htmlspecialchars($course['level']) ?></span>
                        <span>📅 <?= htmlspecialchars($course['semester_name']) ?></span>
                    </div>

                    <a
                        class="button"
                        href="students.php?course_id=<?= $course['course_id'] ?>"
                    >
                        👥 View Students
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <div class="empty-icon">📭</div>
            No courses have been assigned to you yet.
        </div>

    <?php endif; ?>

</div>

<script>

function filterCourses() {

    const query = document.getElementById("courseSearch").value.toLowerCase();
    const cards = document.querySelectorAll(".course-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>

</html>