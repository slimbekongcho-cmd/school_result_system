<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error = "";

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


/* Process form */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $matricule     = trim($_POST['matricule'] ?? '');
    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $gender        = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $faculty_id    = $_POST['faculty_id'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $programme_id  = $_POST['programme_id'] ?? '';
    $level         = $_POST['level'] ?? '';

    /* Validate required fields before touching the database */
    if (
        $matricule === '' ||
        $first_name === '' ||
        $last_name === '' ||
        $faculty_id === '' ||
        $department_id === '' ||
        $programme_id === '' ||
        $level === ''
    ) {
        $error = "Please fill in all required fields, including Faculty, Department, and Programme.";
    } else {

        $username = $matricule;
        $default_password = "student123";

        $password_hash = password_hash(
            $default_password,
            PASSWORD_DEFAULT
        );

        mysqli_begin_transaction($conn);

        try {

            /* Create user account */
            $user_sql = "INSERT INTO users
                        (username, password, role, status)
                        VALUES (?, ?, 'student', 'active')";

            $user_stmt = mysqli_prepare($conn, $user_sql);

            mysqli_stmt_bind_param(
                $user_stmt,
                "ss",
                $username,
                $password_hash
            );

            mysqli_stmt_execute($user_stmt);

            $user_id = mysqli_insert_id($conn);


            /* Create student */
            $student_sql = "INSERT INTO students
                (user_id, matricule, first_name, last_name, email,
                 phone, gender, date_of_birth, faculty_id,
                 department_id, programme_id, level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $student_stmt = mysqli_prepare($conn, $student_sql);

            mysqli_stmt_bind_param(
                $student_stmt,
                "issssssiiiii",
                $user_id,
                $matricule,
                $first_name,
                $last_name,
                $email,
                $phone,
                $gender,
                $date_of_birth,
                $faculty_id,
                $department_id,
                $programme_id,
                $level
            );

            mysqli_stmt_execute($student_stmt);

            mysqli_commit($conn);

            header("Location: students.php");
            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = "Error adding student: " . $e->getMessage();
        }
    }

    // Re-fetch dropdowns since cursors were consumed on first render
    $faculty_result = mysqli_query($conn, $faculty_sql);
    $department_result = mysqli_query($conn, $department_sql);
    $programme_result = mysqli_query($conn, $programme_sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - UniResult</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        /* ===== CONTAINER ===== */

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px 60px;
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
            font-size: 28px;
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

        /* ===== ALERT ===== */

        .error {
            margin-bottom: 20px;
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
            background: #ffffff;
            padding: 36px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
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

        input,
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #1e293b;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        input:focus,
        select:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.10);
        }

        input::placeholder {
            color: #94a3b8;
        }

        select {
            cursor: pointer;
        }

        select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .hint {
            font-size: 12.5px;
            color: #94a3b8;
            margin-top: 5px;
        }

        /* Submit button */

        .submit-row {
            margin-top: 32px;
        }

        button {
            width: 100%;
            border: none;
            background: #003366;
            color: white;
            padding: 14px 20px;
            border-radius: 8px;
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

            .container {
                padding: 25px 16px 40px;
            }

            .content-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-heading h1 {
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

        }

    </style>
</head>

<body>

<div class="navbar">
 <div class="brand">
    <?php include __DIR__ . '/../../brand.html'; ?>
</div>

</div>


<div class="container">

    <div class="content-header">

        <div class="page-heading">
            <h1>Add Student</h1>
            <p>Create a new student account and assign them to a programme.</p>
        </div>

        <a href="students.php" class="back-button">
            ← Back to Students
        </a>

    </div>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="section-label">Personal Information</div>

        <div class="field-grid">

            <div class="field">
                <label>Matricule</label>
                <input type="text" name="matricule" value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="field">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Gender</label>
                <select name="gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="field full-width">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
            </div>

        </div>


        <div class="section-label">Academic Placement</div>

        <div class="field-grid">

            <div class="field">
                <label>Faculty</label>

                <select name="faculty_id" id="faculty" required>
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

                <select name="department_id" id="department" required disabled>
                    <option value="">-- Select Faculty First --</option>
                </select>
            </div>

            <div class="field">
                <label>Programme</label>

                <select name="programme_id" id="programme" required disabled>
                    <option value="">-- Select Department First --</option>
                </select>
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

        </div>


        <div class="submit-row">

            <button type="submit" id="submitBtn">
                + Add Student
            </button>

        </div>

    </form>

</div>

<script>

const facultySelect = document.getElementById("faculty");
const departmentSelect = document.getElementById("department");
const programmeSelect = document.getElementById("programme");
const form = document.querySelector("form");

facultySelect.addEventListener("change", function () {
    const facultyId = this.value;

    departmentSelect.innerHTML = '<option value="">Loading departments...</option>';
    programmeSelect.innerHTML = '<option value="">-- Select Department First --</option>';
    programmeSelect.disabled = true;

    if (!facultyId) {
        departmentSelect.innerHTML = '<option value="">-- Select Faculty First --</option>';
        departmentSelect.disabled = true;
        return;
    }

    fetch("../api/get_departments.php?faculty_id=" + facultyId)
        .then(response => response.json())
        .then(data => {
            departmentSelect.innerHTML = '<option value="">-- Select Department --</option>';

            data.forEach(department => {
                const option = document.createElement("option");
                option.value = department.department_id;
                option.textContent = department.department_name;
                departmentSelect.appendChild(option);
            });

            departmentSelect.disabled = false;
        })
        .catch(error => {
            console.error(error);
            departmentSelect.innerHTML = '<option value="">Error loading departments</option>';
        });
});

departmentSelect.addEventListener("change", function () {
    const departmentId = this.value;

    programmeSelect.innerHTML = '<option value="">Loading programmes...</option>';

    if (!departmentId) {
        programmeSelect.innerHTML = '<option value="">-- Select Department First --</option>';
        programmeSelect.disabled = true;
        return;
    }

    fetch("../api/get_programmes.php?department_id=" + departmentId)
        .then(response => response.json())
        .then(data => {
            programmeSelect.innerHTML = '<option value="">-- Select Programme --</option>';

            data.forEach(programme => {
                const option = document.createElement("option");
                option.value = programme.programme_id;
                option.textContent = programme.programme_name;
                programmeSelect.appendChild(option);
            });

            programmeSelect.disabled = false;
        })
        .catch(error => {
            console.error(error);
            programmeSelect.innerHTML = '<option value="">Error loading programmes</option>';
        });
});

/* Stop submission client-side if department/programme still disabled or empty */
form.addEventListener("submit", function (e) {
    if (departmentSelect.disabled || !departmentSelect.value) {
        e.preventDefault();
        alert("Please select a Department.");
        return;
    }
    if (programmeSelect.disabled || !programmeSelect.value) {
        e.preventDefault();
        alert("Please select a Programme.");
        return;
    }
});

</script>

</body>
</html>