<?php
$active = $active ?? '';
?>

<!-- MOBILE OVERLAY (click to close sidebar) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <?php include __DIR__ . '/../brand.html'; ?>

    <nav>

        <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">
            🏠 Dashboard
        </a>

        <a href="students/students.php" class="<?= $active === 'students' ? 'active' : '' ?>">
            👨‍🎓 Students
        </a>

        <a href="lecturers/" class="<?= $active === 'lecturers' ? 'active' : '' ?>">
            👨‍🏫 Lecturers
        </a>

        <a href="faculties/" class="<?= $active === 'faculties' ? 'active' : '' ?>">
            🏢 Faculties
        </a>

        <a href="departments/" class="<?= $active === 'departments' ? 'active' : '' ?>">
            🏛 Departments
        </a>

        <a href="programmes/" class="<?= $active === 'programmes' ? 'active' : '' ?>">
            🎓 Programmes
        </a>

        <a href="courses/" class="<?= $active === 'courses' ? 'active' : '' ?>">
            📚 Courses
        </a>

        <a href="sessions/" class="<?= $active === 'sessions' ? 'active' : '' ?>">
            📅 Academic Sessions
        </a>

        <a href="results/" class="<?= $active === 'results' ? 'active' : '' ?>">
            📝 Results
        </a>

        <a href="account.php" class="<?= $active === 'account' ? 'active' : '' ?>">
            ⚙️ My Account
        </a>

        <a href="adminlogout.php">
            🚪 Logout
        </a>

    </nav>

</aside>