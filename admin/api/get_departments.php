<?php

require_once "../../config/database.php";

header("Content-Type: application/json");

if (!isset($_GET['faculty_id'])) {
    echo json_encode([]);
    exit;
}

$faculty_id = intval($_GET['faculty_id']);

$sql = "SELECT department_id, department_name
        FROM departments
        WHERE faculty_id = ?
        ORDER BY department_name ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $faculty_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$departments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
}

echo json_encode($departments);