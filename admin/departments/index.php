<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT 
            d.department_id,
            d.department_name,
            f.faculty_name,
            d.created_at
        FROM departments d
        INNER JOIN faculties f 
        ON d.faculty_id = f.faculty_id
        ORDER BY d.department_id DESC";

$result = mysqli_query($conn, $sql);
$total = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Departments - UniResult</title>

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

        .navbar .badge-count {
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .page-wrap {
            padding: 30px 20px 50px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ===== CONTENT HEADER ===== */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 22px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header h1 {
            font-size: 25px;
            color: #003366;
            font-weight: 700;
        }

        .header p {
            margin-top: 6px;
            color: #64748b;
            font-size: 14px;
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
            font-weight: 700;
            transition: 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-dashboard {
            background: white;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .btn-dashboard:hover {
            background: #eef2f7;
            transform: translateY(-2px);
        }

        .btn-add {
            background: #003366;
            color: white;
        }

        .btn-add:hover {
            background: #00509e;
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 8px 13px;
            font-size: 13px;
        }

        .btn-edit:hover {
            background: #d97706;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
            padding: 8px 13px;
            font-size: 13px;
        }

        .btn-delete:hover {
            background: #b91c1c;
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

        /* ===== CARD ===== */

        .card {
            background: white;
            border-radius: 14px;
            padding: 26px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.07);
        }

        .card-title {
            font-size: 18px;
            color: #003366;
            margin-bottom: 18px;
            font-weight: 700;
        }

        /* ===== TABLE ===== */

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        th {
            background: #003366;
            color: white;
            padding: 13px 14px;
            text-align: left;
            font-size: 12.5px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #eef1f5;
            font-size: 14.5px;
        }

        tbody tr:hover td {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
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

        .actions {
            display: flex;
            gap: 8px;
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

        /* ===== MOBILE CARD LIST ===== */

        .mobile-list {
            display: none;
        }

        .dept-card {
            border: 1px solid #eaeef3;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #003366;
        }

        .dept-card:last-child {
            margin-bottom: 0;
        }

        .dept-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }

        .dept-name {
            font-size: 15.5px;
            font-weight: 700;
            color: #1e293b;
        }

        .dept-meta {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .dept-meta strong {
            color: #334155;
            font-weight: 600;
        }

        .dept-card .actions {
            margin-top: 14px;
        }

        .dept-card .actions .btn {
            flex: 1;
        }

        /* ===== TABLET ===== */

        @media (max-width: 760px) {

            .header {
                flex-direction: column;
            }

            .top-actions .btn {
                flex: 1;
            }

        }

        /* ===== MOBILE ===== */

        @media (max-width: 640px) {

            .navbar {
                padding: 14px 18px;
            }

            .navbar .brand {
                font-size: 15px;
            }

            .page-wrap {
                padding: 18px 12px 40px;
            }

            .header h1 {
                font-size: 21px;
            }

            .search-bar input {
                max-width: 100%;
            }

            .card {
                padding: 16px;
                border-radius: 12px;
            }

            .card-title {
                font-size: 16px;
            }

            .table-container {
                display: none;
            }

            .mobile-list {
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
        <?= $total ?> total
    </span>

</div>

<div class="page-wrap">

    <div class="container">

        <div class="header">

            <div>
                <h1>Department Management</h1>
                <p>Manage university departments</p>
            </div>

            <div class="top-actions">

                <a href="../dashboard.php" class="btn btn-dashboard">
                    ← Back to Dashboard
                </a>

                <a href="add.php" class="btn btn-add">
                    + Add Department
                </a>

            </div>

        </div>

        <div class="search-bar">
            <input
                type="text"
                id="deptSearch"
                placeholder="Search by department or faculty..."
                onkeyup="filterDepartments()"
            >
        </div>


        <div class="card">

            <h2 class="card-title">
                Department List
            </h2>

            <!-- Desktop / tablet table -->

            <div class="table-container">

                <table id="deptTable">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Faculty</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($total > 0): ?>

                        <?php while ($department = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <span class="id-pill">#<?= $department['department_id'] ?></span>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($department['department_name']) ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($department['faculty_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($department['created_at']) ?>
                            </td>

                            <td>

                                <div class="actions">

                                    <a href="edit.php?id=<?= $department['department_id'] ?>" class="btn btn-edit">
                                        ✏ Edit
                                    </a>

                                    <a
                                        href="delete.php?id=<?= $department['department_id'] ?>"
                                        class="btn btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this department?');"
                                    >
                                        🗑 Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty">
                                <div class="empty-icon">📭</div>
                                No departments found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- Mobile stacked cards -->

            <div class="mobile-list" id="deptCards">

                <?php if ($total > 0): ?>

                    <?php mysqli_data_seek($result, 0); ?>

                    <?php while ($department = mysqli_fetch_assoc($result)): ?>

                        <div class="dept-card"
                             data-search="<?= strtolower(htmlspecialchars(
                                $department['department_name'] . ' ' . $department['faculty_name']
                             )) ?>">

                            <div class="dept-card-top">

                                <div class="dept-name">
                                    <?= htmlspecialchars($department['department_name']) ?>
                                </div>

                                <span class="id-pill">
                                    #<?= $department['department_id'] ?>
                                </span>

                            </div>

                            <div class="dept-meta">
                                <strong>Faculty:</strong>
                                <?= htmlspecialchars($department['faculty_name']) ?>
                            </div>

                            <div class="dept-meta">
                                <strong>Created:</strong>
                                <?= htmlspecialchars($department['created_at']) ?>
                            </div>

                            <div class="actions">

                                <a href="edit.php?id=<?= $department['department_id'] ?>" class="btn btn-edit">
                                    ✏ Edit
                                </a>

                                <a
                                    href="delete.php?id=<?= $department['department_id'] ?>"
                                    class="btn btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this department?');"
                                >
                                    🗑 Delete
                                </a>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty">
                        <div class="empty-icon">📭</div>
                        No departments found.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<script>

function filterDepartments() {

    const query = document.getElementById("deptSearch").value.toLowerCase();

    const rows = document.querySelectorAll("#deptTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".dept-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>
</html>