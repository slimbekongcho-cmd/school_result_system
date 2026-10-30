<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT 
            p.programme_id,
            p.programme_name,
            d.department_name,
            f.faculty_name,
            p.created_at
        FROM programmes p
        INNER JOIN departments d
            ON p.department_id = d.department_id
        INNER JOIN faculties f
            ON d.faculty_id = f.faculty_id
        ORDER BY p.programme_id DESC";

$result = mysqli_query($conn, $sql);
$total_programmes = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Programmes - UniResult</title>

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

        .navbar .badge-count {
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ===== CONTAINER ===== */

        .container {
            width: 95%;
            max-width: 1250px;
            margin: 0 auto;
            padding: 30px 0 60px;
        }

        /* ===== CONTENT HEADER ===== */

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .top h2 {
            color: #003366;
            font-size: 24px;
            font-weight: 700;
        }

        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ===== BUTTONS ===== */

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px 17px;
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

        .edit-btn,
        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.25s ease;
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

        /* ===== TABLE CARD ===== */

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 51, 102, 0.08);
            overflow-x: auto;
        }

        .table-title {
            font-size: 18px;
            color: #003366;
            font-weight: 700;
            margin-bottom: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            background: #003366;
            color: white;
            padding: 13px 12px;
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
            padding: 13px 12px;
            border-bottom: 1px solid #eef1f5;
            font-size: 14px;
            color: #475569;
        }

        tbody tr {
            transition: 0.15s ease;
        }

        tbody tr:hover {
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

        .programme-name {
            font-weight: 600;
            color: #1e293b;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
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

        .programme-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .programme-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .programme-card .card-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .programme-card .card-details {
            font-size: 13px;
            color: #64748b;
            line-height: 1.9;
            margin-bottom: 14px;
        }

        .programme-card .card-details b {
            color: #334155;
        }

        .programme-card .card-actions {
            display: flex;
            gap: 8px;
        }

        .programme-card .card-actions a {
            flex: 1;
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
                width: 92%;
                padding: 20px 0 40px;
            }

            .top {
                flex-direction: column;
                align-items: stretch;
            }

            .top h2 {
                font-size: 21px;
            }

            .top-actions .button {
                flex: 1;
                justify-content: center;
            }

            .search-bar input {
                max-width: 100%;
            }

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
        <?= $total_programmes ?> total
    </span>

</div>


<div class="container">

    <div class="top">

        <h2>Programme Management</h2>

        <div class="top-actions">

            <a href="../dashboard.php" class="button back-btn">
                ← Back to Dashboard
            </a>

            <a href="add.php" class="button add-btn">
                ➕ Add Programme
            </a>

        </div>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="programmeSearch"
            placeholder="Search by programme, department, or faculty..."
            onkeyup="filterProgrammes()"
        >
    </div>


    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <h3 class="table-title">
            Programme List
        </h3>

        <table id="programmesTable">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Programme</th>
                    <th>Department</th>
                    <th>Faculty</th>
                    <th>Created At</th>
                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

            <?php if ($total_programmes > 0): ?>

                <?php mysqli_data_seek($result, 0); ?>
                <?php while ($programme = mysqli_fetch_assoc($result)): ?>

                    <tr>

                        <td>
                            <span class="id-pill">
                                #<?= $programme['programme_id'] ?>
                            </span>
                        </td>

                        <td class="programme-name">
                            <?= htmlspecialchars($programme['programme_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($programme['department_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($programme['faculty_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($programme['created_at']) ?>
                        </td>

                        <td>

                            <div class="actions">

                                <a
                                    href="edit.php?id=<?= $programme['programme_id'] ?>"
                                    class="edit-btn"
                                >
                                    ✏ Edit
                                </a>

                                <a
                                    href="delete.php?id=<?= $programme['programme_id'] ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this programme?');"
                                >
                                    🗑 Delete
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="empty">
                        <div class="empty-icon">📭</div>
                        No programmes have been created yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards" id="programmesCards">

        <?php if ($total_programmes > 0): ?>

            <?php mysqli_data_seek($result, 0); ?>
            <?php while ($programme = mysqli_fetch_assoc($result)): ?>

                <div class="programme-card"
                     data-search="<?= strtolower(htmlspecialchars(
                        $programme['programme_name'] . ' ' .
                        $programme['department_name'] . ' ' .
                        $programme['faculty_name']
                     )) ?>">

                    <div class="card-top">
                        <span class="card-name"><?= htmlspecialchars($programme['programme_name']) ?></span>
                        <span class="id-pill">#<?= $programme['programme_id'] ?></span>
                    </div>

                    <div class="card-details">
                        <div><b>Department:</b> <?= htmlspecialchars($programme['department_name']) ?></div>
                        <div><b>Faculty:</b> <?= htmlspecialchars($programme['faculty_name']) ?></div>
                        <div><b>Created:</b> <?= htmlspecialchars($programme['created_at']) ?></div>
                    </div>

                    <div class="card-actions">

                        <a
                            href="edit.php?id=<?= $programme['programme_id'] ?>"
                            class="edit-btn"
                        >
                            ✏ Edit
                        </a>

                        <a
                            href="delete.php?id=<?= $programme['programme_id'] ?>"
                            class="delete-btn"
                            onclick="return confirm('Are you sure you want to delete this programme?');"
                        >
                            🗑 Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No programmes have been created yet.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function filterProgrammes() {

    const query = document.getElementById("programmeSearch").value.toLowerCase();

    const rows = document.querySelectorAll("#programmesTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".programme-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>

</html>