<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$sql = "SELECT * FROM students ORDER BY student_id DESC";
$result = mysqli_query($conn, $sql);
$total_students = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - UniResult</title>
    <style>

/* ================================
   STUDENTS PAGE - UNIRESULT
================================ */

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

.navbar .badge-count {
    background: rgba(255, 255, 255, 0.15);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

/* ===== CONTAINER ===== */

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 25px 60px;
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

.content-header .head h1 {
    color: #003366;
    font-size: 26px;
    font-weight: 700;
}

.content-header .head p {
    color: #64748b;
    font-size: 14px;
    margin-top: 4px;
}

.top-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* ===== BUTTONS ===== */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 11px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s ease;
    border: none;
    cursor: pointer;
}

.back-btn {
    background: #fff;
    color: #003366;
    border: 1px solid #cbd5e1;
}

.back-btn:hover {
    background: #eef2f7;
    transform: translateY(-2px);
}

.add-btn {
    background: #003366;
    color: white;
}

.add-btn:hover {
    background: #00509e;
    transform: translateY(-2px);
}

.edit-btn {
    background: #f59e0b;
    color: white;
}

.edit-btn:hover {
    background: #d97706;
    transform: translateY(-1px);
}

.delete-btn {
    background: #dc2626;
    color: white;
}

.delete-btn:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

/* ===== SEARCH ===== */

.search-bar {
    margin-bottom: 20px;
}

.search-bar input {
    width: 100%;
    max-width: 380px;
    padding: 12px 16px 12px 40px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%2394a3b8" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>') no-repeat 14px center;
    background-size: 15px;
    outline: none;
    transition: 0.2s ease;
}

.search-bar input:focus {
    border-color: #003366;
    box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
}

/* ===== TABLE ===== */

.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    overflow-x: auto;
    box-shadow: 0 5px 25px rgba(0, 51, 102, 0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th {
    background: #003366;
    color: white;
    padding: 13px 12px;
    text-align: left;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

th:first-child {
    border-top-left-radius: 8px;
}

th:last-child {
    border-top-right-radius: 8px;
}

td {
    padding: 13px 12px;
    border-bottom: 1px solid #eef1f5;
    font-size: 14px;
    color: #475569;
}

tbody tr:hover {
    background: #eef5ff;
}

.id-pill {
    display: inline-block;
    background: #f1f5f9;
    color: #334155;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12.5px;
}

.level-pill {
    display: inline-block;
    background: #eaf1fb;
    color: #003366;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12.5px;
}

.actions {
    display: flex;
    gap: 7px;
    white-space: nowrap;
}

.actions .btn {
    padding: 7px 12px;
    font-size: 13px;
}

.empty {
    text-align: center;
    padding: 45px 20px;
    color: #64748b;
}

.empty-icon {
    font-size: 38px;
    margin-bottom: 10px;
}

/* ===== MOBILE CARDS (hidden on desktop) ===== */

.mobile-cards {
    display: none;
}

.student-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 14px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
    border-left: 4px solid #003366;
}

.student-card .card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
    gap: 10px;
}

.student-card .card-name {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}

.student-card .card-details {
    font-size: 13px;
    color: #64748b;
    line-height: 1.9;
    margin-bottom: 14px;
}

.student-card .card-details b {
    color: #334155;
}

.student-card .card-actions {
    display: flex;
    gap: 8px;
}

.student-card .card-actions .btn {
    flex: 1;
}

/* ===== MOBILE ===== */

@media (max-width: 900px) {

    .navbar {
        padding: 14px 18px;
    }

    .navbar .brand {
        font-size: 15px;
    }

    .container {
        padding: 20px 14px 40px;
    }

    .content-header {
        flex-direction: column;
        align-items: stretch;
    }

    .content-header .head h1 {
        font-size: 22px;
    }

    .top-actions .btn {
        flex: 1;
        justify-content: center;
    }

    .search-bar input {
        max-width: 100%;
    }

    /* Hide table, show cards */

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

<div class="navbar">

    <div class="brand">
    <?php include __DIR__ . '/../../brand.html'; ?>
</div>

    <span class="badge-count">
        <?= $total_students ?> total
    </span>

</div>


<div class="container">

    <div class="content-header">

        <div class="head">
            <h1>Students</h1>
            <p>Manage university students</p>
        </div>

        <div class="top-actions">

            <a href="../dashboard.php" class="btn back-btn">
                ← Back to Dashboard
            </a>

            <a href="add_student.php" class="btn add-btn">
                ➕ Add Student
            </a>

        </div>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="studentSearch"
            placeholder="Search by name, matricule, or email..."
            onkeyup="filterStudents()"
        >
    </div>


    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <table id="studentsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Matricule</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Level</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($total_students > 0): ?>

                <?php mysqli_data_seek($result, 0); ?>
                <?php while ($student = mysqli_fetch_assoc($result)): ?>

                    <tr>
                        <td>
                            <span class="id-pill">#<?= $student['student_id'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($student['matricule']) ?></td>
                        <td><?= htmlspecialchars($student['first_name']) ?></td>
                        <td><?= htmlspecialchars($student['last_name']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td><?= htmlspecialchars($student['phone']) ?></td>
                        <td><?= htmlspecialchars($student['gender']) ?></td>
                        <td>
                            <span class="level-pill">
                                <?= htmlspecialchars($student['level']) ?>
                            </span>
                        </td>

                        <td>

                            <div class="actions">

                                <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="btn edit-btn">
                                    ✏ Edit
                                </a>

                                <a
                                    href="delete_student.php?id=<?= $student['student_id'] ?>"
                                    class="btn delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this student?');"
                                >
                                    🗑 Delete
                                </a>

                            </div>

                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" class="empty">
                        <div class="empty-icon">📭</div>
                        No students have been added yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards" id="studentsCards">

        <?php if ($total_students > 0): ?>

            <?php mysqli_data_seek($result, 0); ?>
            <?php while ($student = mysqli_fetch_assoc($result)): ?>

                <div class="student-card"
                     data-search="<?= strtolower(htmlspecialchars(
                        $student['first_name'] . ' ' .
                        $student['last_name'] . ' ' .
                        $student['matricule'] . ' ' .
                        $student['email']
                     )) ?>">

                    <div class="card-top">
                        <span class="card-name">
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                        </span>
                        <span class="id-pill">#<?= $student['student_id'] ?></span>
                    </div>

                    <div class="card-details">
                        <div><b>Matricule:</b> <?= htmlspecialchars($student['matricule']) ?></div>
                        <div><b>Email:</b> <?= htmlspecialchars($student['email']) ?></div>
                        <div><b>Phone:</b> <?= htmlspecialchars($student['phone']) ?></div>
                        <div><b>Gender:</b> <?= htmlspecialchars($student['gender']) ?></div>
                        <div><b>Level:</b> <?= htmlspecialchars($student['level']) ?></div>
                    </div>

                    <div class="card-actions">

                        <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="btn edit-btn">
                            ✏ Edit
                        </a>

                        <a
                            href="delete_student.php?id=<?= $student['student_id'] ?>"
                            class="btn delete-btn"
                            onclick="return confirm('Are you sure you want to delete this student?');"
                        >
                            🗑 Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No students have been added yet.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function filterStudents() {

    const query = document.getElementById("studentSearch").value.toLowerCase();

    const rows = document.querySelectorAll("#studentsTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".student-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>
</html>