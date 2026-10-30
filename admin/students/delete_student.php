<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

/* Check student ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid student ID.");
}

$student_id = (int) $_GET['id'];


/* Get the student's user_id */

$sql = "SELECT user_id FROM students WHERE student_id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $student_id);

if (!mysqli_stmt_execute($stmt)) {
    die("Execute failed: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_bind_result($stmt, $user_id);

if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);
    die("Student not found.");
}

mysqli_stmt_close($stmt);


/* Start transaction */

mysqli_begin_transaction($conn);

try {

    /* Delete student */

    $sql = "DELETE FROM students WHERE student_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $student_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);


    /* Delete user account */

    $sql = "DELETE FROM users WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);


    /* Commit */

    mysqli_commit($conn);


    /* Go back to students */

    header("Location: students.php");
    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "<h2>Unable to delete student</h2>" .
        "<p style='color:red;'>" .
        htmlspecialchars($e->getMessage()) .
        "</p>" .
        "<br><a href='students.php'>← Back to Students</a>"
    );
}

?>