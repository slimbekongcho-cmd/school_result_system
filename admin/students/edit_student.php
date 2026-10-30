<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: students.php");
    exit;
}

$student_id = (int) $_GET['id'];
$error = "";

/* Get student */
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Student not found.");
}


/* Update student */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $matricule = trim($_POST['matricule']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $level = $_POST['level'];

    $update_sql = "UPDATE students SET
                    matricule = ?,
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    gender = ?,
                    date_of_birth = ?,
                    level = ?
                   WHERE student_id = ?";

    $update_stmt = mysqli_prepare($conn, $update_sql);

    mysqli_stmt_bind_param(
        $update_stmt,
        "sssssssii",
        $matricule,
        $first_name,
        $last_name,
        $email,
        $phone,
        $gender,
        $date_of_birth,
        $level,
        $student_id
    );

    if (mysqli_stmt_execute($update_stmt)) {

        header("Location: students.php");
        exit;

    } else {

        $error = mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Student - UniResult</title>

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

/* ===== CONTAINER ===== */

.container {
    max-width: 800px;
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
    background: #fee2e2;
    color: #b91c1c;
    border-left: 4px solid #dc2626;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
}

/* ===== FORM CARD ===== */

.form-card {
    background: white;
    padding: 36px;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0, 51, 102, 0.08);
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
    font-weight: 600;
    font-size: 13.5px;
    color: #334155;
    margin-bottom: 7px;
}

input,
select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
    background: #fff;
    color: #1e293b;
    transition: 0.2s ease;
}

select {
    cursor: pointer;
}

input:focus,
select:focus {
    border-color: #003366;
    box-shadow: 0 0 0 3px rgba(0, 51, 102, .10);
}

/* ===== BUTTONS ===== */

.buttons {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

button,
.cancel-btn {
    flex: 1;
    padding: 14px 18px;
    border: none;
    border-radius: 8px;
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

button {
    background: #003366;
    color: white;
}

button:hover {
    background: #00509e;
    transform: translateY(-2px);
}

.cancel-btn {
    background: #fff;
    color: #003366;
    border: 1px solid #cbd5e1;
}

.cancel-btn:hover {
    background: #eef2f7;
    transform: translateY(-2px);
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

    .form-card {
        padding: 24px 20px;
        border-radius: 14px;
    }

    .field-grid {
        grid-template-columns: 1fr;
    }

    .buttons {
        flex-direction: column;
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
            <h1>Edit Student</h1>
            <p>Update the student information below.</p>
        </div>

        <a href="students.php" class="back-button">
            ← Back to Students
        </a>

    </div>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="form-card">

        <form method="POST">

            <div class="section-label">Personal Information</div>

            <div class="field-grid">

                <div class="field">
                    <label>Matricule</label>
                    <input
                        type="text"
                        name="matricule"
                        value="<?= htmlspecialchars($student['matricule']) ?>"
                        required>
                </div>

                <div class="field">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($student['email']) ?>">
                </div>

                <div class="field">
                    <label>First Name</label>
                    <input
                        type="text"
                        name="first_name"
                        value="<?= htmlspecialchars($student['first_name']) ?>"
                        required>
                </div>

                <div class="field">
                    <label>Last Name</label>
                    <input
                        type="text"
                        name="last_name"
                        value="<?= htmlspecialchars($student['last_name']) ?>"
                        required>
                </div>

                <div class="field">
                    <label>Phone</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= htmlspecialchars($student['phone']) ?>">
                </div>

                <div class="field">
                    <label>Gender</label>

                    <select name="gender">

                        <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>
                            Male
                        </option>

                        <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>
                            Female
                        </option>

                    </select>

                </div>

                <div class="field">
                    <label>Date of Birth</label>

                    <input
                        type="date"
                        name="date_of_birth"
                        value="<?= htmlspecialchars($student['date_of_birth']) ?>">

                </div>

                <div class="field">
                    <label>Level</label>

                    <select name="level" required>

                        <?php for ($i = 100; $i <= 500; $i += 100): ?>

                            <option
                                value="<?= $i ?>"
                                <?= $student['level'] == $i ? 'selected' : '' ?>>

                                Level <?= $i ?>

                            </option>

                        <?php endfor; ?>

                    </select>

                </div>

            </div>

            <div class="buttons">

                <button type="submit">
                    💾 Save Changes
                </button>

                <a href="students.php" class="cancel-btn">
                    ← Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>