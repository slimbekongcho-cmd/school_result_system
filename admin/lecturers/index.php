<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT * FROM lecturers ORDER BY lecturer_id DESC";
$result = mysqli_query($conn, $sql);
$total_lecturers = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Lecturers - UniResult</title>

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
            max-width: 1300px;
            margin: 0 auto;
            padding: 30px 25px 60px;
        }

        /* ===== HEADER ===== */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header h1 {
            color: #003366;
            font-size: 26px;
            font-weight: 700;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ===== BUTTONS ===== */

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
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

        h2.section-title {
            color: #003366;
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            background: #003366;
            color: white;
            padding: 13px 15px;
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
            padding: 14px 15px;
            border-bottom: 1px solid #eef1f5;
            font-size: 14px;
        }

        tbody tr {
            transition: 0.15s ease;
        }

        tbody tr:hover {
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

        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 10px;
        }

        /* Action buttons */

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions .btn {
            padding: 8px 13px;
            font-size: 13px;
        }

        /* ===== MOBILE CARDS (hidden on desktop) ===== */

        .mobile-cards {
            display: none;
        }

        .lecturer-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .lecturer-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 10px;
        }

        .lecturer-card .card-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .lecturer-card .card-details {
            font-size: 13px;
            color: #64748b;
            line-height: 1.9;
            margin-bottom: 14px;
        }

        .lecturer-card .card-details b {
            color: #334155;
        }

        .lecturer-card .card-actions {
            display: flex;
            gap: 8px;
        }

        .lecturer-card .card-actions .btn {
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
                flex-direction: column;
                align-items: stretch;
            }

            .header h1 {
                font-size: 22px;
            }

            .top-actions {
                justify-content: space-between;
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
        <?= $total_lecturers ?> total
    </span>

</div>


<div class="container">

    <div class="header">

        <h1>Lecturers</h1>

        <div class="top-actions">

            <a href="../dashboard.php" class="btn back-btn">
                ← Back to Dashboard
            </a>

            <a href="add.php" class="btn add-btn">
                ➕ Add Lecturer
            </a>

        </div>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="lecturerSearch"
            placeholder="Search by name, email, or phone..."
            onkeyup="filterLecturers()"
        >
    </div>

    <h2 class="section-title">Lecturer List</h2>


    <!-- DESKTOP TABLE -->

    <div class="table-container">

        <table id="lecturersTable">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

            <?php if ($total_lecturers > 0): ?>

                <?php mysqli_data_seek($result, 0); ?>
                <?php while ($lecturer = mysqli_fetch_assoc($result)): ?>

                    <tr>

                        <td>
                            <span class="id-pill">
                                #<?= $lecturer['lecturer_id'] ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($lecturer['first_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($lecturer['last_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($lecturer['email']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($lecturer['phone']) ?>
                        </td>

                        <td>

                            <div class="actions">

                                <a
                                    href="edit.php?id=<?= $lecturer['lecturer_id'] ?>"
                                    class="btn edit-btn"
                                >
                                    ✏ Edit
                                </a>

                                <a
                                    href="delete.php?id=<?= $lecturer['lecturer_id'] ?>"
                                    class="btn delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this lecturer?');"
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
                        No lecturers have been added yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards" id="lecturersCards">

        <?php if ($total_lecturers > 0): ?>

            <?php mysqli_data_seek($result, 0); ?>
            <?php while ($lecturer = mysqli_fetch_assoc($result)): ?>

                <div class="lecturer-card"
                     data-search="<?= strtolower(htmlspecialchars(
                        $lecturer['first_name'] . ' ' .
                        $lecturer['last_name'] . ' ' .
                        $lecturer['email'] . ' ' .
                        $lecturer['phone']
                     )) ?>">

                    <div class="card-top">
                        <span class="card-name">
                            <?= htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']) ?>
                        </span>
                        <span class="id-pill">#<?= $lecturer['lecturer_id'] ?></span>
                    </div>

                    <div class="card-details">
                        <div><b>Email:</b> <?= htmlspecialchars($lecturer['email']) ?></div>
                        <div><b>Phone:</b> <?= htmlspecialchars($lecturer['phone']) ?></div>
                    </div>

                    <div class="card-actions">

                        <a
                            href="edit.php?id=<?= $lecturer['lecturer_id'] ?>"
                            class="btn edit-btn"
                        >
                            ✏ Edit
                        </a>

                        <a
                            href="delete.php?id=<?= $lecturer['lecturer_id'] ?>"
                            class="btn delete-btn"
                            onclick="return confirm('Are you sure you want to delete this lecturer?');"
                        >
                            🗑 Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No lecturers have been added yet.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function filterLecturers() {

    const query = document.getElementById("lecturerSearch").value.toLowerCase();

    const rows = document.querySelectorAll("#lecturersTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".lecturer-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>

</html>