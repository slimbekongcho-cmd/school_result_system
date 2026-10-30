<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT * FROM faculties ORDER BY faculty_id DESC";
$result = mysqli_query($conn, $sql);
$total_faculties = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Faculties - UniResult</title>

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
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 26px;
            color: #003366;
            font-weight: 700;
        }

        .header p {
            color: #64748b;
            margin-top: 5px;
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 11px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: #fff;
            color: #003366;
            border: 1px solid #cbd5e1;
        }

        .btn-back:hover {
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
        }

        .btn-edit:hover {
            background: #d97706;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Search bar */
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

        /* Card */
        .card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .card-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: #003366;
            font-weight: 700;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th {
            background: #003366;
            color: white;
            padding: 13px 14px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
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
            font-size: 14px;
        }

        tr:hover td {
            background: #f8fafc;
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

        .actions .btn {
            padding: 8px 12px;
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

        .faculty-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .faculty-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .faculty-card .card-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .faculty-card .card-date {
            font-size: 12.5px;
            color: #64748b;
            margin-bottom: 14px;
        }

        .faculty-card .card-actions {
            display: flex;
            gap: 8px;
        }

        .faculty-card .card-actions .btn {
            flex: 1;
            justify-content: center;
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
                padding: 20px 14px 40px;
            }

            .header {
                align-items: stretch;
                flex-direction: column;
            }

            .header h1 {
                font-size: 22px;
            }

            .top-actions .btn {
                flex: 1;
                justify-content: center;
            }

            .search-bar input {
                max-width: 100%;
            }

            .card {
                padding: 16px;
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
        <?= $total_faculties ?> total
    </span>

</div>


<div class="container">

    <div class="header">

        <div>
            <h1>Faculties</h1>
            <p>Manage university faculties</p>
        </div>

        <div class="top-actions">

            <a href="../dashboard.php" class="btn btn-back">
                ← Back to Dashboard
            </a>

            <a href="add.php" class="btn btn-add">
                + Add Faculty
            </a>

        </div>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="facultySearch"
            placeholder="Search faculties..."
            onkeyup="filterFaculties()"
        >
    </div>


    <div class="card">

        <h2 class="card-title">Faculty List</h2>

        <!-- DESKTOP TABLE -->

        <div class="table-container">

            <table id="facultiesTable">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Faculty Name</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($total_faculties > 0): ?>

                    <?php mysqli_data_seek($result, 0); ?>
                    <?php while ($faculty = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <span class="id-pill">
                                    #<?= $faculty['faculty_id'] ?>
                                </span>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($faculty['faculty_name']) ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($faculty['created_at']) ?>
                            </td>

                            <td>

                                <div class="actions">

                                    <a
                                        href="edit.php?id=<?= $faculty['faculty_id'] ?>"
                                        class="btn btn-edit"
                                    >
                                        ✏ Edit
                                    </a>

                                    <a
                                        href="delete.php?id=<?= $faculty['faculty_id'] ?>"
                                        class="btn btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this faculty?');"
                                    >
                                        🗑 Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty">
                            <div class="empty-icon">📭</div>
                            No faculties found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- MOBILE CARDS -->

        <div class="mobile-cards" id="facultiesCards">

            <?php if ($total_faculties > 0): ?>

                <?php mysqli_data_seek($result, 0); ?>
                <?php while ($faculty = mysqli_fetch_assoc($result)): ?>

                    <div class="faculty-card"
                         data-search="<?= strtolower(htmlspecialchars($faculty['faculty_name'])) ?>">

                        <div class="card-top">
                            <span class="card-name"><?= htmlspecialchars($faculty['faculty_name']) ?></span>
                            <span class="id-pill">#<?= $faculty['faculty_id'] ?></span>
                        </div>

                        <div class="card-date">
                            Created: <?= htmlspecialchars($faculty['created_at']) ?>
                        </div>

                        <div class="card-actions">

                            <a
                                href="edit.php?id=<?= $faculty['faculty_id'] ?>"
                                class="btn btn-edit"
                            >
                                ✏ Edit
                            </a>

                            <a
                                href="delete.php?id=<?= $faculty['faculty_id'] ?>"
                                class="btn btn-delete"
                                onclick="return confirm('Are you sure you want to delete this faculty?');"
                            >
                                🗑 Delete
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty">
                    <div class="empty-icon">📭</div>
                    No faculties found.
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>

function filterFaculties() {

    const query = document.getElementById("facultySearch").value.toLowerCase();

    const rows = document.querySelectorAll("#facultiesTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".faculty-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>
</html>