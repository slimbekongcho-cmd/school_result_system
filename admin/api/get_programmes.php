<?php

require_once "../../config/database.php";

header("Content-Type: application/json");

if (!isset($_GET['department_id'])) {
    echo json_encode([]);
    exit;
}

$department_id = intval($_GET['department_id']);

$sql = "SELECT programme_id, programme_name
        FROM programmes
        WHERE department_id = ?
        ORDER BY programme_name ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $department_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$programmes = [];

while ($row = mysqli_fetch_assoc($result)) {
    $programmes[] = $row;
}

echo json_encode($programmes);