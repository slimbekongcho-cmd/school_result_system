<?php
session_start();
require "../config/database.php";

/* Make sure a user is logged in */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* Make sure this is a student */
if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Get the logged-in student's information */
$sql = "SELECT
            s.student_id,
            s.matricule,
            s.first_name,
            s.last_name,
            s.email,
            s.phone,
            s.gender,
            s.level,
            f.faculty_name,
            d.department_name,
            p.programme_name
        FROM students s
        LEFT JOIN faculties f
            ON s.faculty_id = f.faculty_id
        LEFT JOIN departments d
            ON s.department_id = d.department_id
        LEFT JOIN programmes p
            ON s.programme_id = p.programme_id
        WHERE s.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$student = mysqli_fetch_assoc($result);

/* If no student record is found */
if (!$student) {
    die("Student profile not found.");
}

/* Initials for the avatar */
$initials = strtoupper(
    substr($student['first_name'], 0, 1) .
    substr($student['last_name'], 0, 1)
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard - UniResult</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

    <style>

        :root {
            --navy: #003366;
            --navy-deep: #002347;
            --blue: #0066cc;
            --light-blue: #4da3ff;
            --bg: #f5f7fa;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e5e7eb;
            --green: #0f9d68;
            --amber: #b8770a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3 {
            font-family: "Sora", "Segoe UI", Arial, sans-serif;
        }

        a { color: inherit; }

        :focus-visible {
            outline: 3px solid var(--light-blue);
            outline-offset: 2px;
        }

        /* ===== TOP NAVBAR ===== */

        .navbar {
            background: var(--navy);
            color: white;
            padding: 16px clamp(18px, 4vw, 30px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 20;
            box-shadow: 0 2px 14px rgba(0, 20, 50, .18);
        }

        .navbar .brand {
            font-family: "Sora", sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 700;
        }

        .brand .logo-mark {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--light-blue), var(--blue));
            color: var(--navy-deep);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .logout {
            background: white;
            border: 1px solid rgba(255,255,255,.16);
            color: black;
            text-decoration: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            transition: background .2s ease;
            white-space: nowrap;
        }

        .logout:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* ===== CONTAINER ===== */

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px clamp(18px, 4vw, 20px) 60px;
        }

        /* ===== CONTENT HEADER ===== */

        .content-header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            background: #fff;
            color: var(--navy);
            border: 1.5px solid var(--border);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s ease;
            white-space: nowrap;
        }

        .back-button:hover {
            border-color: var(--navy);
            background: var(--bg);
        }

        /* ===== WELCOME / PROFILE SUMMARY ===== */

        .welcome {
            background: linear-gradient(135deg, var(--navy-deep), var(--navy) 55%, #01438c);
            color: white;
            border-radius: 18px;
            padding: 32px clamp(22px, 4vw, 34px);
            margin-bottom: 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .welcome::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(77,163,255,.18), transparent 70%);
            top: -110px;
            right: -80px;
            pointer-events: none;
        }

        .welcome-left {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .welcome .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            border: 1.5px solid rgba(255,255,255,.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Sora", sans-serif;
            font-size: 22px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .welcome h1 {
            font-size: 23px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -.3px;
        }

        .welcome .matric-line {
            font-family: "IBM Plex Mono", monospace;
            font-size: 13px;
            color: rgba(255,255,255,.75);
        }

        .welcome-badge {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.16);
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 12.5px;
            color: rgba(255,255,255,.75);
            font-weight: 600;
        }

        /* ===== QUICK ACTION CARDS ===== */

        .section-header {
            margin-bottom: 16px;
        }

        .section-header h2 {
            color: var(--navy);
            font-size: 18px;
            font-weight: 700;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 36px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 24px;
            border: 1px solid var(--border);
            border-top: 3px solid var(--blue);
            transition: .2s ease;
            display: flex;
            flex-direction: column;
        }

        .card:nth-child(2) { border-top-color: var(--green); }
        .card:nth-child(3) { border-top-color: var(--amber); }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(0, 30, 70, .08);
        }

        .card .card-icon {
            font-size: 24px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .card h3 {
            color: var(--text);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .card p {
            color: var(--muted);
            font-size: 13.5px;
            line-height: 1.6;
            margin-bottom: 18px;
            flex: 1;
        }

        .card a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: var(--navy);
            font-weight: 700;
            font-size: 14px;
        }

        .card a:hover {
            color: var(--blue);
        }

        /* ===== PROFILE INFO ===== */

        .profile {
            background: white;
            padding: 30px clamp(20px, 4vw, 28px);
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        .profile-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .profile-head h2 {
            color: var(--navy);
            font-size: 18px;
            font-weight: 700;
        }

        .profile-head span {
            font-family: "IBM Plex Mono", monospace;
            font-size: 11.5px;
            color: var(--muted);
            background: var(--bg);
            padding: 5px 11px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px 24px;
        }

        .profile-item {
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f4f8;
        }

        .profile-item .profile-label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .profile-item .profile-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
        }

        /* ===== MOBILE ===== */

        @media (max-width: 700px) {

            .cards {
                grid-template-columns: 1fr;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .welcome {
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome-badge {
                align-self: flex-start;
            }

        }

        @media (max-width: 480px) {

            .navbar .brand {
                font-size: 16px;
            }

            .welcome h1 {
                font-size: 20px;
            }

            .welcome-left {
                gap: 14px;
            }

            .welcome .avatar {
                width: 54px;
                height: 54px;
                font-size: 19px;
            }

            .profile {
                padding: 22px 18px;
            }

        }

    </style>

</head>

<body>

<?php include '../navbar.html'; ?>


<div class="container">

    <div class="content-header">

        <a href="../index.php" class="back-button">
            ← Back to Home
        </a>

    </div>


    <div class="welcome">

        <div class="welcome-left">

            <div class="avatar"><?= htmlspecialchars($initials) ?></div>

            <div>
                <h1>Welcome, <?= htmlspecialchars($student['first_name']) ?> 👋</h1>
                <div class="matric-line">
                    Matricule: <?= htmlspecialchars($student['matricule']) ?>
                    &nbsp;·&nbsp;
                    Level <?= htmlspecialchars($student['level']) ?>
                </div>
            </div>

        </div>

        <div class="welcome-badge">
            🎓 <?= htmlspecialchars($student['programme_name']) ?>
        </div>

    </div>


    <div class="section-header">
        <h2>Quick Actions</h2>
    </div>

    <div class="cards">

        <div class="card">

            <div class="card-icon">📚</div>

            <h3>My Courses</h3>
            <p>View and register your courses for this semester.</p>

            <a href="register_courses.php">
                Register Courses →
            </a>

        </div>


        <div class="card">

            <div class="card-icon">📝</div>

            <h3>My Results</h3>
            <p>View your published examination results and GPA.</p>

            <a href="result.php">
                View Results →
            </a>

        </div>


        <div class="card">

            <div class="card-icon">📊</div>

            <h3>Academic History</h3>
            <p>Track your academic performance over time.</p>

            <a href="academic_history.php">
                View History →
            </a>

        </div>

    </div>


    <div class="profile">

        <div class="profile-head">
            <h2>My Profile</h2>
            <span><?= htmlspecialchars($student['matricule']) ?></span>
        </div>

        <div class="profile-grid">

            <div class="profile-item">
                <div class="profile-label">Full Name</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Matricule</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['matricule']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Email</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['email']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Phone</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['phone']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Gender</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['gender']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Level</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['level']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Faculty</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['faculty_name']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Department</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['department_name']) ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Programme</div>
                <div class="profile-value">
                    <?= htmlspecialchars($student['programme_name']) ?>
                </div>
            </div>

        </div>

    </div>

</div>

</body>

</html>