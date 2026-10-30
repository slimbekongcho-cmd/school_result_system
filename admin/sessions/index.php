<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT * FROM academic_sessions
        ORDER BY session_id DESC";

$result = mysqli_query($conn, $sql);
$total_sessions = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Academic Sessions - UniResult</title>

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
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        /* ===== CONTENT HEADER ===== */

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .top h1 {
            color: #003366;
            font-size: 26px;
            font-weight: 700;
        }

        .subtitle {
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

        .edit {
            background: #f59e0b;
            color: white;
        }

        .edit:hover {
            background: #d97706;
        }

        .delete {
            background: #dc2626;
            color: white;
        }

        .delete:hover {
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

        /* ===== TABLE ===== */

        .table-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
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

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 700;
        }

        .active {
            background: #dcfce7;
            color: #166534;
        }

        .inactive {
            background: #fee2e2;
            color: #991b1b;
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

        .session-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #003366;
        }

        .session-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 10px;
        }

        .session-card .card-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .session-card .card-date {
            font-size: 12.5px;
            color: #64748b;
            margin-bottom: 14px;
        }

        .session-card .card-actions {
            display: flex;
            gap: 8px;
        }

        .session-card .card-actions .btn {
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
                padding: 20px 14px 40px;
            }

            .top {
                flex-direction: column;
                align-items: stretch;
            }

            .top h1 {
                font-size: 22px;
            }

            .top-actions .btn {
                flex: 1;
                justify-content: center;
            }

            .search-bar input {
                max-width: 100%;
            }

            .table-box {
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
        <?= $total_sessions ?> total
    </span>

</div>


<div class="container">

    <div class="top">

        <div>
            <h1>Academic Sessions</h1>
            <p class="subtitle">Manage university academic sessions.</p>
        </div>

        <div class="top-actions">

            <a class="btn back-btn" href="../dashboard.php">
                ← Back to Dashboard
            </a>

            <a class="btn add-btn" href="add.php">
                + Add Session
            </a>

        </div>

    </div>

    <div class="search-bar">
        <input
            type="text"
            id="sessionSearch"
            placeholder="Search sessions..."
            onkeyup="filterSessions()"
        >
    </div>


    <!-- DESKTOP TABLE -->

    <div class="table-box">

        <table id="sessionsTable">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Academic Session</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if ($total_sessions > 0): ?>

                <?php mysqli_data_seek($result, 0); ?>
                <?php while ($session = mysqli_fetch_assoc($result)): ?>

                    <tr>

                        <td>
                            <span class="id-pill">#<?= $session['session_id'] ?></span>
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($session['session_name']) ?></strong>
                        </td>

                        <td>

                            <?php if ($session['is_active'] == 1): ?>

                                <span class="status active">
                                    ● Active
                                </span>

                            <?php else: ?>

                                <span class="status inactive">
                                    ● Inactive
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($session['created_at']) ?>
                        </td>

                        <td class="actions">

                            <a
                                class="btn edit"
                                href="edit.php?id=<?= $session['session_id'] ?>"
                            >
                                ✏ Edit
                            </a>

                            <a
                                class="btn delete"
                                href="delete.php?id=<?= $session['session_id'] ?>"
                                onclick="return confirm('Are you sure you want to delete this academic session?');"
                            >
                                🗑 Delete
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="empty">
                        <div class="empty-icon">📭</div>
                        No academic sessions found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- MOBILE CARDS -->

    <div class="mobile-cards" id="sessionsCards">

        <?php if ($total_sessions > 0): ?>

            <?php mysqli_data_seek($result, 0); ?>
            <?php while ($session = mysqli_fetch_assoc($result)): ?>

                <div class="session-card"
                     data-search="<?= strtolower(htmlspecialchars($session['session_name'])) ?>">

                    <div class="card-top">
                        <span class="card-name"><?= htmlspecialchars($session['session_name']) ?></span>

                        <?php if ($session['is_active'] == 1): ?>
                            <span class="status active">● Active</span>
                        <?php else: ?>
                            <span class="status inactive">● Inactive</span>
                        <?php endif; ?>

                    </div>

                    <div class="card-date">
                        #<?= $session['session_id'] ?> · Created: <?= htmlspecialchars($session['created_at']) ?>
                    </div>

                    <div class="card-actions">

                        <a class="btn edit" href="edit.php?id=<?= $session['session_id'] ?>">
                            ✏ Edit
                        </a>

                        <a
                            class="btn delete"
                            href="delete.php?id=<?= $session['session_id'] ?>"
                            onclick="return confirm('Are you sure you want to delete this academic session?');"
                        >
                            🗑 Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📭</div>
                No academic sessions found.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function filterSessions() {

    const query = document.getElementById("sessionSearch").value.toLowerCase();

    const rows = document.querySelectorAll("#sessionsTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });

    const cards = document.querySelectorAll(".session-card");

    cards.forEach(card => {
        const text = card.getAttribute("data-search") || "";
        card.style.display = text.includes(query) ? "" : "none";
    });

}

</script>

</body>
</html>