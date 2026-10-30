<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$course_id = (int) $_GET['id'];
$message = "";


/* Get course */

$sql = "SELECT * FROM courses WHERE course_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $course_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$course = mysqli_fetch_assoc($result);

if (!$course) {
    die("Course not found.");
}


/* Get faculties */

$faculty_sql = "
    SELECT faculty_id, faculty_name
    FROM faculties
    ORDER BY faculty_name ASC
";

$faculty_result = mysqli_query($conn, $faculty_sql);


/* Get departments */

$department_sql = "
    SELECT department_id, department_name
    FROM departments
    ORDER BY department_name ASC
";

$department_result = mysqli_query($conn, $department_sql);


/* Get programmes */

$programme_sql = "
    SELECT programme_id, programme_name
    FROM programmes
    ORDER BY programme_name ASC
";

$programme_result = mysqli_query($conn, $programme_sql);


/* Get semesters */

$semester_sql = "
    SELECT semester_id, semester_name
    FROM semesters
    WHERE is_active = 1
    ORDER BY semester_id ASC
";

$semester_result = mysqli_query($conn, $semester_sql);


/* Get lecturers */

$lecturer_sql = "
    SELECT lecturer_id, first_name, last_name, staff_id
    FROM lecturers
    ORDER BY first_name ASC
";

$lecturer_result = mysqli_query($conn, $lecturer_sql);


/* Update course */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $faculty_id = (int) $_POST['faculty_id'];
    $department_id = (int) $_POST['department_id'];
    $programme_id = (int) $_POST['programme_id'];

    $course_code = trim($_POST['course_code']);
    $course_title = trim($_POST['course_title']);

    $credit_unit = (int) $_POST['credit_unit'];
    $level = (int) $_POST['level'];

    $semester_id = (int) $_POST['semester_id'];
    $lecturer_id = (int) $_POST['lecturer_id'];


    if (
        !$faculty_id ||
        !$department_id ||
        !$programme_id ||
        empty($course_code) ||
        empty($course_title) ||
        !$credit_unit ||
        !$level ||
        !$semester_id ||
        !$lecturer_id
    ) {

        $message = "Please fill in all fields.";

    } else {

        $update_sql = "
            UPDATE courses
            SET
                faculty_id = ?,
                department_id = ?,
                programme_id = ?,
                course_code = ?,
                course_title = ?,
                credit_unit = ?,
                level = ?,
                semester_id = ?,
                lecturer_id = ?
            WHERE course_id = ?
        ";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        mysqli_stmt_bind_param(
            $update_stmt,
            "iiissiiiii",
            $faculty_id,
            $department_id,
            $programme_id,
            $course_code,
            $course_title,
            $credit_unit,
            $level,
            $semester_id,
            $lecturer_id,
            $course_id
        );

        if (mysqli_stmt_execute($update_stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Error updating course: " . mysqli_error($conn);
        }
    }

    // Re-fetch since cursors were consumed on first render
    $faculty_result = mysqli_query($conn, $faculty_sql);
    $department_result = mysqli_query($conn, $department_sql);
    $programme_result = mysqli_query($conn, $programme_sql);
    $semester_result = mysqli_query($conn, $semester_sql);
    $lecturer_result = mysqli_query($conn, $lecturer_sql);
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

    <title>Edit Course - UniResult</title>

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
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 700;
        }

        .navbar .brand .divider {
            opacity: 0.5;
            font-weight: 400;
        }

        /* ===== PAGE WRAP ===== */

        .page-wrap {
            padding: 30px 20px 50px;
        }

        .container {
            max-width: 780px;
            margin: 0 auto;
        }

        /* ===== CONTENT HEADER ===== */

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .page-heading h1 {
            color: #003366;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-heading p {
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

        /* ===== CARD ===== */

        .card {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
        }

        fieldset {
            border: none;
            margin-bottom: 22px;
        }

        .section-label {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #003366;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaf1fb;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13.5px;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        select {
            cursor: pointer;
        }

        input:focus,
        select:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        /* ===== BUTTONS ===== */

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .btn {
            flex: 1;
            padding: 14px 18px;
            border-radius: 8px;
            border: none;
            text-align: center;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .save {
            background: #003366;
            color: white;
        }

        .save:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        .cancel {
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .cancel:hover {
            background: #eef2f7;
            transform: translateY(-2px);
        }

        /* ===== TABLET ===== */

        @media (max-width: 700px) {

            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .card {
                padding: 26px 22px;
            }

        }

        /* ===== MOBILE ===== */

        @media (max-width: 480px) {

            .navbar {
                padding: 14px 18px;
            }

            .navbar .brand {
                font-size: 15px;
            }

            .page-wrap {
                padding: 18px 12px 40px;
            }

            .content-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-heading h1 {
                font-size: 22px;
            }

            .back-button {
                justify-content: center;
            }

            .card {
                padding: 20px 16px;
                border-radius: 12px;
            }

            input,
            select {
                padding: 11px 12px;
                font-size: 16px; /* prevents iOS zoom on focus */
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                padding: 14px;
            }

        }

    </style>

</head>

<body>

<div class="navbar">

    <div class="brand">
    <?php include __DIR__ . '/../../brand.html'; ?>
</div>

</div>

<div class="page-wrap">

    <div class="container">

        <div class="content-header">

            <div class="page-heading">
                <h1>Edit Course</h1>
                <p>Update the course information below.</p>
            </div>

            <a href="index.php" class="back-button">
                ← Back to Courses
            </a>

        </div>

        <div class="card">

            <?php if ($message): ?>

                <div class="error">
                    <?= htmlspecialchars($message) ?>
                </div>

            <?php endif; ?>


            <form method="POST">


                <fieldset>

                    <div class="section-label">Academic Placement</div>

                    <div class="row">

                        <!-- Faculty -->

                        <div class="form-group">

                            <label>Faculty</label>

                            <select name="faculty_id" required>

                                <option value="">
                                    -- Select Faculty --
                                </option>

                                <?php while ($faculty = mysqli_fetch_assoc($faculty_result)): ?>

                                    <option
                                        value="<?= $faculty['faculty_id'] ?>"
                                        <?= ($faculty['faculty_id'] == $course['faculty_id']) ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars($faculty['faculty_name']) ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>


                        <!-- Department -->

                        <div class="form-group">

                            <label>Department</label>

                            <select name="department_id" required>

                                <option value="">
                                    -- Select Department --
                                </option>

                                <?php while ($department = mysqli_fetch_assoc($department_result)): ?>

                                    <option
                                        value="<?= $department['department_id'] ?>"
                                        <?= ($department['department_id'] == $course['department_id']) ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars($department['department_name']) ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>

                    </div>


                    <!-- Programme -->

                    <div class="form-group">

                        <label>Programme</label>

                        <select name="programme_id" required>

                            <option value="">
                                -- Select Programme --
                            </option>

                            <?php while ($programme = mysqli_fetch_assoc($programme_result)): ?>

                                <option
                                    value="<?= $programme['programme_id'] ?>"
                                    <?= ($programme['programme_id'] == $course['programme_id']) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($programme['programme_name']) ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                </fieldset>


                <fieldset>

                    <div class="section-label">Course Details</div>

                    <div class="row">

                        <!-- Course Code -->

                        <div class="form-group">

                            <label>Course Code</label>

                            <input
                                type="text"
                                name="course_code"
                                value="<?= htmlspecialchars($course['course_code']) ?>"
                                required
                            >

                        </div>


                        <!-- Credit Unit -->

                        <div class="form-group">

                            <label>Credit Unit</label>

                            <input
                                type="number"
                                name="credit_unit"
                                min="1"
                                max="10"
                                value="<?= htmlspecialchars($course['credit_unit']) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- Course Title -->

                    <div class="form-group">

                        <label>Course Title</label>

                        <input
                            type="text"
                            name="course_title"
                            value="<?= htmlspecialchars($course['course_title']) ?>"
                            required
                        >

                    </div>


                    <div class="row">

                        <!-- Level -->

                        <div class="form-group">

                            <label>Level</label>

                            <select name="level" required>

                                <option value="">
                                    -- Select Level --
                                </option>

                                <?php
                                $levels = [100, 200, 300, 400, 500];

                                foreach ($levels as $level):
                                ?>

                                    <option
                                        value="<?= $level ?>"
                                        <?= ($level == $course['level']) ? 'selected' : '' ?>
                                    >

                                        Level <?= $level ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- Semester -->

                        <div class="form-group">

                            <label>Semester</label>

                            <select name="semester_id" required>

                                <option value="">
                                    -- Select Semester --
                                </option>

                                <?php while ($semester = mysqli_fetch_assoc($semester_result)): ?>

                                    <option
                                        value="<?= $semester['semester_id'] ?>"
                                        <?= ($semester['semester_id'] == $course['semester_id']) ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars($semester['semester_name']) ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>

                    </div>

                </fieldset>


                <fieldset>

                    <div class="section-label">Assigned Lecturer</div>

                    <!-- Lecturer -->

                    <div class="form-group">

                        <label>Lecturer</label>

                        <select name="lecturer_id" required>

                            <option value="">
                                -- Select Lecturer --
                            </option>

                            <?php while ($lecturer = mysqli_fetch_assoc($lecturer_result)): ?>

                                <option
                                    value="<?= $lecturer['lecturer_id'] ?>"
                                    <?= ($lecturer['lecturer_id'] == $course['lecturer_id']) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $lecturer['first_name'] . " " .
                                        $lecturer['last_name'] .
                                        " (" . $lecturer['staff_id'] . ")"
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                </fieldset>


                <div class="buttons">

                    <button type="submit" class="btn save">
                        ✓ Save Changes
                    </button>

                    <a href="index.php" class="btn cancel">
                        ← Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>