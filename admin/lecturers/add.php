<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$error = "";

/* Get faculties for dropdown */
$faculty_sql = "SELECT faculty_id, faculty_name FROM faculties ORDER BY faculty_name ASC";
$faculty_result = mysqli_query($conn, $faculty_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $staff_id = trim($_POST['staff_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $faculty_id = $_POST['faculty_id'] ?? '';
    $department_id = $_POST['department_id'] ?? '';

    if (
        empty($staff_id) ||
        empty($first_name) ||
        empty($last_name) ||
        empty($faculty_id) ||
        empty($department_id)
    ) {

        $error = "Please fill in all required fields, including Faculty and Department.";

    } else {

        // Use staff ID as username
        $username = $staff_id;

        // Temporary default password
        $default_password = "lecturer123";

        // Hash password
        $password_hash = password_hash(
            $default_password,
            PASSWORD_DEFAULT
        );

        // Start database transaction
        mysqli_begin_transaction($conn);

        try {

            // Create lecturer's user account
            $user_sql = "INSERT INTO users
                (username, password, role, status)
                VALUES (?, ?, 'lecturer', 'active')";

            $user_stmt = mysqli_prepare($conn, $user_sql);

            mysqli_stmt_bind_param(
                $user_stmt,
                "ss",
                $username,
                $password_hash
            );

            mysqli_stmt_execute($user_stmt);

            // Get the new user's ID
            $user_id = mysqli_insert_id($conn);


            // Create lecturer record
            $lecturer_sql = "INSERT INTO lecturers
                (user_id, staff_id, first_name, last_name, email,
                 phone, faculty_id, department_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $lecturer_stmt = mysqli_prepare($conn, $lecturer_sql);

            mysqli_stmt_bind_param(
                $lecturer_stmt,
                "isssssii",
                $user_id,
                $staff_id,
                $first_name,
                $last_name,
                $email,
                $phone,
                $faculty_id,
                $department_id
            );

            mysqli_stmt_execute($lecturer_stmt);

            // Save everything
            mysqli_commit($conn);

            header("Location: index.php");
            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = "Error adding lecturer: " . $e->getMessage();
        }
    }

    // Re-fetch faculties since result cursor was consumed
    $faculty_result = mysqli_query($conn, $faculty_sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Lecturer - UniResult</title>

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
            max-width: 700px;
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

        .alert {
            margin-bottom: 20px;
            padding: 14px 18px;
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        /* ===== FORM ===== */

        form {
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
            color: #334155;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 7px;
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
            display: flex;
            gap: 12px;
        }

        button {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            background: #003366;
            color: #ffffff;
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
            <h1>Add Lecturer</h1>
            <p>Create a new lecturer account and assign them to a department.</p>
        </div>

        <a href="index.php" class="back-button">
            ← Back to Lecturers
        </a>

    </div>

    <?php if ($error): ?>

        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="section-label">Personal Information</div>

        <div class="field-grid">

            <div class="field">
                <label>Staff ID</label>
                <input
                    type="text"
                    name="staff_id"
                    placeholder="e.g. STF2026"
                    value="<?= htmlspecialchars($_POST['staff_id'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    placeholder="lecturer@university.cm"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label>First Name</label>
                <input
                    type="text"
                    name="first_name"
                    value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field">
                <label>Last Name</label>
                <input
                    type="text"
                    name="last_name"
                    value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field full-width">
                <label>Phone</label>
                <input
                    type="text"
                    name="phone"
                    placeholder="e.g. 6XXXXXXXX"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                >
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

                <div class="hint">Choose a faculty to load its departments.</div>
            </div>

        </div>


        <div class="submit-row">

            <button type="submit">
                + Add Lecturer
            </button>

        </div>

    </form>

</div>

<script>

const facultySelect = document.getElementById("faculty");
const departmentSelect = document.getElementById("department");

facultySelect.addEventListener("change", function () {

    const facultyId = this.value;

    departmentSelect.innerHTML = '<option value="">Loading departments...</option>';
    departmentSelect.disabled = true;

    if (!facultyId) {
        departmentSelect.innerHTML = '<option value="">-- Select Faculty First --</option>';
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

</script>

</body>

</html>