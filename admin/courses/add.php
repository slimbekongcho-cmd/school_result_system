<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$message = "";

/* Get faculties */
$faculty_sql = "SELECT faculty_id, faculty_name
                FROM faculties
                ORDER BY faculty_name ASC";

$faculty_result = mysqli_query($conn, $faculty_sql);


/* Get departments */
$department_sql = "SELECT department_id, department_name
                   FROM departments
                   ORDER BY department_name ASC";

$department_result = mysqli_query($conn, $department_sql);


/* Get programmes */
$programme_sql = "SELECT programme_id, programme_name
                  FROM programmes
                  ORDER BY programme_name ASC";

$programme_result = mysqli_query($conn, $programme_sql);


/* Get semesters */
$semester_sql = "SELECT semester_id, semester_name
                 FROM semesters
                 WHERE is_active = 1
                 ORDER BY semester_id ASC";

$semester_result = mysqli_query($conn, $semester_sql);


/* Get lecturers */
$lecturer_sql = "SELECT lecturer_id, first_name, last_name, staff_id
                 FROM lecturers
                 ORDER BY first_name ASC";

$lecturer_result = mysqli_query($conn, $lecturer_sql);


/* Add course */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $faculty_id = $_POST['faculty_id'];
    $department_id = $_POST['department_id'];
    $programme_id = $_POST['programme_id'];

    $course_code = trim($_POST['course_code']);
    $course_title = trim($_POST['course_title']);

    $credit_unit = $_POST['credit_unit'];
    $level = $_POST['level'];

    $semester_id = $_POST['semester_id'];
    $lecturer_id = $_POST['lecturer_id'];


    if (
        empty($faculty_id) ||
        empty($department_id) ||
        empty($programme_id) ||
        empty($course_code) ||
        empty($course_title) ||
        empty($credit_unit) ||
        empty($level) ||
        empty($semester_id) ||
        empty($lecturer_id)
    ) {

        $message = "Please fill in all fields.";

    } else {

        $sql = "INSERT INTO courses
                (
                    faculty_id,
                    department_id,
                    programme_id,
                    course_code,
                    course_title,
                    credit_unit,
                    level,
                    semester_id,
                    lecturer_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iiissiiii",
            $faculty_id,
            $department_id,
            $programme_id,
            $course_code,
            $course_title,
            $credit_unit,
            $level,
            $semester_id,
            $lecturer_id
        );

        if (mysqli_stmt_execute($stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Error: " . mysqli_error($conn);
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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Course - UniResult</title>

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
        padding: 35px 20px 60px;
    }

    /* ===== TOP BAR (content header) ===== */

    .top-bar {
        max-width: 820px;
        margin: 0 auto 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .top-bar h1 {
        color: #003366;
        font-size: 28px;
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

    /* ===== ALERT MESSAGE ===== */

    .alert {
        max-width: 820px;
        margin: 0 auto 20px;
        padding: 14px 18px;
        background: #fee2e2;
        color: #b91c1c;
        border-left: 4px solid #dc2626;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
    }

    /* ===== FORM CARD ===== */

    form {
        max-width: 820px;
        margin: 0 auto;
        background: #ffffff;
        padding: 36px;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
    }

    .section-label {
        font-size: 13px;
        font-weight: 700;
        color: #003366;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid #eaf1fb;
    }

    .section-label:not(:first-child) {
        margin-top: 32px;
    }

    /* Field grid: 2 columns on desktop */

    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 18px;
    }

    .field-grid .full-width {
        grid-column: 1 / -1;
    }

    .field {
        display: flex;
        flex-direction: column;
    }

    label {
        display: block;
        margin-bottom: 7px;
        color: #334155;
        font-size: 13.5px;
        font-weight: 600;
    }

    /* Inputs and selects */

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

    input::placeholder {
        color: #94a3b8;
    }

    input:focus,
    select:focus {
        border-color: #003366;
        box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
    }

    select {
        cursor: pointer;
    }

    /* Submit button */

    .submit-row {
        margin-top: 32px;
        display: flex;
        gap: 12px;
    }

    button {
        flex: 1;
        padding: 14px 20px;
        border: none;
        border-radius: 8px;
        background: #003366;
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s ease;
    }

    button:hover {
        background: #00509e;
        transform: translateY(-2px);
    }

    button:active {
        transform: translateY(0);
    }

    /* ===== MOBILE ===== */

    @media (max-width: 700px) {

        .navbar {
            padding: 14px 18px;
        }

        .navbar .brand {
            font-size: 15px;
        }

        .page-wrap {
            padding: 22px 14px 40px;
        }

        .top-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .top-bar h1 {
            font-size: 24px;
        }

        .back-button {
            justify-content: center;
        }

        form {
            padding: 24px 20px;
            border-radius: 14px;
        }

        .field-grid {
            grid-template-columns: 1fr;
        }

        input,
        select {
            padding: 12px;
        }

        button {
            padding: 13px;
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

    <div class="top-bar">

        <h1>Add Course</h1>

        <a href="../courses/index.php" class="back-button">
            ← Back to Courses
        </a>

    </div>


    <?php if ($message): ?>

        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="section-label">Academic Placement</div>

        <div class="field-grid">

            <div class="field">
                <label>Faculty</label>

                <select name="faculty_id" required>

                    <option value="">-- Select Faculty --</option>

                    <?php while ($faculty = mysqli_fetch_assoc($faculty_result)): ?>

                        <option value="<?= $faculty['faculty_id'] ?>">
                            <?= htmlspecialchars($faculty['faculty_name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

            <div class="field">
                <label>Department</label>

                <select name="department_id" required>

                    <option value="">-- Select Department --</option>

                    <?php while ($department = mysqli_fetch_assoc($department_result)): ?>

                        <option value="<?= $department['department_id'] ?>">
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

            <div class="field full-width">
                <label>Programme</label>

                <select name="programme_id" required>

                    <option value="">-- Select Programme --</option>

                    <?php while ($programme = mysqli_fetch_assoc($programme_result)): ?>

                        <option value="<?= $programme['programme_id'] ?>">
                            <?= htmlspecialchars($programme['programme_name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

        </div>


        <div class="section-label">Course Details</div>

        <div class="field-grid">

            <div class="field">
                <label>Course Code</label>

                <input
                    type="text"
                    name="course_code"
                    placeholder="e.g. SWE101"
                    value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field">
                <label>Credit Unit</label>

                <input
                    type="number"
                    name="credit_unit"
                    min="1"
                    max="10"
                    placeholder="e.g. 3"
                    value="<?= htmlspecialchars($_POST['credit_unit'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field full-width">
                <label>Course Title</label>

                <input
                    type="text"
                    name="course_title"
                    placeholder="e.g. Introduction to Programming"
                    value="<?= htmlspecialchars($_POST['course_title'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field">
                <label>Level</label>

                <select name="level" required>

                    <option value="">-- Select Level --</option>

                    <option value="100">Level 100</option>
                    <option value="200">Level 200</option>
                    <option value="300">Level 300</option>
                    <option value="400">Level 400</option>
                    <option value="500">Level 500</option>

                </select>
            </div>

            <div class="field">
                <label>Semester</label>

                <select name="semester_id" required>

                    <option value="">-- Select Semester --</option>

                    <?php while ($semester = mysqli_fetch_assoc($semester_result)): ?>

                        <option value="<?= $semester['semester_id'] ?>">
                            <?= htmlspecialchars($semester['semester_name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

        </div>


        <div class="section-label">Assigned Lecturer</div>

        <div class="field-grid">

            <div class="field full-width">
                <label>Lecturer</label>

                <select name="lecturer_id" required>

                    <option value="">-- Select Lecturer --</option>

                    <?php while ($lecturer = mysqli_fetch_assoc($lecturer_result)): ?>

                        <option value="<?= $lecturer['lecturer_id'] ?>">

                            <?= htmlspecialchars(
                                $lecturer['first_name'] . " " .
                                $lecturer['last_name'] .
                                " (" . $lecturer['staff_id'] . ")"
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

        </div>


        <div class="submit-row">

            <button type="submit">
                + Add Course
            </button>

        </div>

    </form>

</div>

</body>

</html>