<?php

session_start();
require_once "../config/database.php";

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}

/* Helper to safely get a COUNT(*) result */
function get_count($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_row($result)) {
        return (int) $row[0];
    }
    return 0;
}

$total_students  = get_count($conn, "SELECT COUNT(*) FROM students");
$total_lecturers = get_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'lecturer'");
$total_courses   = get_count($conn, "SELECT COUNT(*) FROM courses");
$total_results   = get_count($conn, "SELECT COUNT(*) FROM results");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - UniResult</title>

    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

   <?php
    $active = 'dashboard';
    include __DIR__ . '/sidebar.php';
    ?>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <header class="topbar">

            <div class="topbar-left">

                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <h2>Admin Dashboard</h2>

            </div>

            <div class="admin-info">
                👤 <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>

        </header>


        <!-- PAGE CONTENT -->
        <section class="content">

            <h1>Welcome, Admin 👋</h1>

            <p class="subtitle">
                Here's what's happening in your university system.
            </p>


            <!-- DASHBOARD CARDS -->
            <div class="cards">

                <div class="card">
                    <div class="card-icon">👨‍🎓</div>
                    <div class="card-info">
                        <h3>Students</h3>
                        <p><?php echo $total_students; ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">👨‍🏫</div>
                    <div class="card-info">
                        <h3>Lecturers</h3>
                        <p><?php echo $total_lecturers; ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">📚</div>
                    <div class="card-info">
                        <h3>Courses</h3>
                        <p><?php echo $total_courses; ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">📝</div>
                    <div class="card-info">
                        <h3>Results</h3>
                        <p><?php echo $total_results; ?></p>
                    </div>
                </div>

            </div>


            <!-- QUICK ACTIONS -->
            <div class="quick-section">

                <h2>Quick Actions</h2>

                <div class="quick-actions">

                    <a href="students/students.php">
                        ➕ Add Student
                    </a>

                    <a href="lecturers/">
                        ➕ Add Lecturer
                    </a>

                    <a href="courses/">
                        ➕ Add Course
                    </a>

                    <a href="results/">
                        📝 Manage Results
                    </a>

                </div>

            </div>

            <a href="../index.php" class="back-home">
                ← Back to Home
            </a>

        </section>

    </main>

<script>

const menuToggle = document.getElementById("menuToggle");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("sidebarOverlay");

function openSidebar() {
    sidebar.classList.add("open");
    overlay.classList.add("visible");
    menuToggle.classList.add("active");
}

function closeSidebar() {
    sidebar.classList.remove("open");
    overlay.classList.remove("visible");
    menuToggle.classList.remove("active");
}

menuToggle.addEventListener("click", function () {
    if (sidebar.classList.contains("open")) {
        closeSidebar();
    } else {
        openSidebar();
    }
});

overlay.addEventListener("click", closeSidebar);

// Close sidebar when a nav link is tapped (mobile)
document.querySelectorAll(".sidebar nav a").forEach(link => {
    link.addEventListener("click", closeSidebar);
});

// Close sidebar if window is resized back to desktop
window.addEventListener("resize", function () {
    if (window.innerWidth > 650) {
        closeSidebar();
    }
});

</script>

</body>

</html>