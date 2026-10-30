<?php

session_start();

require_once "../../config/database.php";
require_once "../../includes/send_email.php";


/* =========================================================
   ADMIN SECURITY
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: ../../login.php");
    exit;
}


/* =========================================================
   DATABASE CHECK
========================================================= */

if (!isset($conn) || !$conn) {
    die("Database connection failed.");
}


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function redirectWithMessage($message, $type = "success")
{
    header(
        "Location: index.php?message=" .
        urlencode($message) .
        "&type=" .
        urlencode($type)
    );

    exit;
}


function clean($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   RESULT EMAIL NOTIFICATIONS
========================================================= */

function getResultNotificationData($conn, $result_id)
{
    $sql = "
        SELECT
            r.result_id, r.registration_id, r.status, r.remark,
            st.first_name AS student_first_name,
            st.last_name AS student_last_name,
            st.email AS student_email,
            st.matricule,
            c.course_code, c.course_title,
            l.first_name AS lecturer_first_name,
            l.last_name AS lecturer_last_name,
            l.email AS lecturer_email
        FROM results r
        INNER JOIN course_registrations cr ON r.registration_id = cr.registration_id
        INNER JOIN students st ON cr.student_id = st.student_id
        INNER JOIN courses c ON cr.course_id = c.course_id
        LEFT JOIN lecturers l ON c.lecturer_id = l.lecturer_id
        WHERE r.result_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("UniResult notification query failed: " . mysqli_error($conn));
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $result_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $data ?: null;
}

function notifyResultParty($data, $recipient, $remark = '')
{
    if (!$data) return false;

    if ($recipient === 'lecturer') {
        $email = trim((string)($data['lecturer_email'] ?? ''));
        $name = trim(($data['lecturer_first_name'] ?? '') . ' ' . ($data['lecturer_last_name'] ?? ''));
    } else {
        $email = trim((string)($data['student_email'] ?? ''));
        $name = trim(($data['student_first_name'] ?? '') . ' ' . ($data['student_last_name'] ?? ''));
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("UniResult notification skipped: invalid/missing {$recipient} email for result #{$data['result_id']}");
        return false;
    }

    $recipientName = clean($name);
    $studentName = clean(trim(($data['student_first_name'] ?? '') . ' ' . ($data['student_last_name'] ?? '')));
    $matricule = clean($data['matricule'] ?? '');
    $courseCode = clean($data['course_code'] ?? '');
    $courseTitle = clean($data['course_title'] ?? '');
    $remarkHtml = nl2br(clean($remark));

    switch ($data['status']) {
        case 'Under Review':
            $subject = 'UniResult: Your result is under review';
            $message = "<p>Hello {$recipientName},</p>
                <p>The result you submitted for <strong>{$courseCode} - {$courseTitle}</strong>
                for <strong>{$studentName}</strong> (Matricule: <strong>{$matricule}</strong>)
                is now <strong>Under Review</strong> by the administrator.</p>
                <p>You will be notified when the review is completed.</p>
                <p>Regards,<br><strong>UniResult System</strong></p>";
            break;

        case 'Returned':
            $subject = 'UniResult: Result returned for correction';
            $message = "<p>Hello {$recipientName},</p>
                <p>The result for <strong>{$courseCode} - {$courseTitle}</strong>
                for <strong>{$studentName}</strong> (Matricule: <strong>{$matricule}</strong>)
                has been <strong>Returned</strong> for correction.</p>
                <p><strong>Administrator's remark:</strong></p>
                <p>{$remarkHtml}</p>
                <p>Please correct the result and submit it again.</p>
                <p>Regards,<br><strong>UniResult System</strong></p>";
            break;

        case 'Approved':
            $subject = 'UniResult: Result approved';
            $message = "<p>Hello {$recipientName},</p>
                <p>The result for <strong>{$courseCode} - {$courseTitle}</strong>
                for <strong>{$studentName}</strong> (Matricule: <strong>{$matricule}</strong>)
                has been <strong>Approved</strong> by the administrator.</p>
                <p>The result can now be published.</p>
                <p>Regards,<br><strong>UniResult System</strong></p>";
            break;

        case 'Published':
            $subject = 'UniResult: Your result has been published';
            $message = "<p>Hello {$recipientName},</p>
                <p>Your result for <strong>{$courseCode} - {$courseTitle}</strong>
                has been <strong>Published</strong>.</p>
                <p>You can now log in to your UniResult student account to view your result.</p>
                <p>Regards,<br><strong>UniResult System</strong></p>";
            break;

        case 'Deleted':
            $subject = 'UniResult: Result deleted';
            $message = "<p>Hello {$recipientName},</p>
                <p>The result for <strong>{$courseCode} - {$courseTitle}</strong>
                for <strong>{$studentName}</strong> (Matricule: <strong>{$matricule}</strong>)
                has been <strong>deleted</strong> by the administrator.</p>
                <p>If this was unexpected, please contact the UniResult administrator.</p>
                <p>Regards,<br><strong>UniResult System</strong></p>";
            break;

        default:
            return false;
    }

    return sendEmail($email, $name, $subject, $message);
}

/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

}

$csrf_token = $_SESSION['csrf_token'];


/* =========================================================
   PROCESS ADMIN ACTIONS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =====================================================
       VERIFY CSRF
    ===================================================== */

    $submitted_token =
        $_POST['csrf_token'] ?? '';

    if (
        empty($submitted_token) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $submitted_token
        )
    ) {

        redirectWithMessage(
            "Security verification failed. Please try again.",
            "error"
        );

    }


    /* =====================================================
       GET ACTION
    ===================================================== */

    $action =
        trim($_POST['action'] ?? '');

    $result_id =
        intval($_POST['result_id'] ?? 0);


    /* =====================================================
       VALIDATE RESULT ID
    ===================================================== */

    if ($result_id <= 0) {

        redirectWithMessage(
            "Invalid result selected.",
            "error"
        );

    }


    /* =====================================================
       ALLOWED ACTIONS
    ===================================================== */

    $allowed_actions = [
        "review",
        "return",
        "approve",
        "publish",
        "delete"
    ];

    if (!in_array($action, $allowed_actions, true)) {

        redirectWithMessage(
            "Invalid action.",
            "error"
        );

    }


    /* =====================================================
       GET CURRENT RESULT
    ===================================================== */

    $check_sql = "
        SELECT
            result_id,
            status,
            remark
        FROM results
        WHERE result_id = ?
        LIMIT 1
    ";


    $check_stmt =
        mysqli_prepare(
            $conn,
            $check_sql
        );


    if (!$check_stmt) {

        redirectWithMessage(
            "Database error occurred.",
            "error"
        );

    }


    mysqli_stmt_bind_param(
        $check_stmt,
        "i",
        $result_id
    );


    mysqli_stmt_execute(
        $check_stmt
    );


    $check_result =
        mysqli_stmt_get_result(
            $check_stmt
        );


    $current =
        mysqli_fetch_assoc(
            $check_result
        );


    mysqli_stmt_close(
        $check_stmt
    );


    /* =====================================================
       RESULT NOT FOUND
    ===================================================== */

    if (!$current) {

        redirectWithMessage(
            "Result not found.",
            "error"
        );

    }


    $current_status =
        $current['status'];


    /* =====================================================
       REVIEW
       Submitted → Under Review
    ===================================================== */

    if ($action === "review") {

        if ($current_status !== "Submitted") {

            redirectWithMessage(
                "Only submitted results can be reviewed.",
                "error"
            );

        }


        $new_status =
            "Under Review";


        $sql = "
            UPDATE results
            SET status = ?
            WHERE result_id = ?
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            redirectWithMessage(
                "Unable to review result.",
                "error"
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_status,
            $result_id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            mysqli_stmt_close($stmt);

            redirectWithMessage(
                "Failed to review result.",
                "error"
            );

        }


        mysqli_stmt_close($stmt);

        $notification_data = getResultNotificationData($conn, $result_id);
        if ($notification_data) {
            notifyResultParty($notification_data, 'lecturer');
        }

        redirectWithMessage(
            "Result is now under review."
        );

    }


    /* =====================================================
       RETURN
       Submitted / Under Review → Returned
    ===================================================== */

    if ($action === "return") {

        if (
            $current_status !== "Submitted" &&
            $current_status !== "Under Review"
        ) {

            redirectWithMessage(
                "This result cannot be returned.",
                "error"
            );

        }


        $remark =
            trim(
                $_POST['remark'] ?? ''
            );


        if ($remark === "") {

            redirectWithMessage(
                "Please provide a reason for returning the result.",
                "error"
            );

        }


        if (strlen($remark) > 1000) {

            redirectWithMessage(
                "The return reason is too long. Maximum 1000 characters.",
                "error"
            );

        }


        $new_status =
            "Returned";


        $sql = "
            UPDATE results
            SET
                status = ?,
                remark = ?
            WHERE result_id = ?
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            redirectWithMessage(
                "Unable to return result.",
                "error"
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $new_status,
            $remark,
            $result_id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            mysqli_stmt_close($stmt);

            redirectWithMessage(
                "Failed to return result.",
                "error"
            );

        }


        mysqli_stmt_close($stmt);

        $notification_data = getResultNotificationData($conn, $result_id);
        if ($notification_data) {
            notifyResultParty($notification_data, 'lecturer', $remark);
        }

        redirectWithMessage(
            "Result returned to the lecturer."
        );

    }


    /* =====================================================
       APPROVE
       Under Review → Approved
    ===================================================== */

    if ($action === "approve") {

        if ($current_status !== "Under Review") {

            redirectWithMessage(
                "Only results under review can be approved.",
                "error"
            );

        }


        $new_status =
            "Approved";


        $sql = "
            UPDATE results
            SET
                status = ?
            WHERE result_id = ?
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            redirectWithMessage(
                "Unable to approve result.",
                "error"
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_status,
            $result_id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            mysqli_stmt_close($stmt);

            redirectWithMessage(
                "Failed to approve result.",
                "error"
            );

        }


        mysqli_stmt_close($stmt);

        $notification_data = getResultNotificationData($conn, $result_id);
        if ($notification_data) {
            notifyResultParty($notification_data, 'lecturer');
        }

        redirectWithMessage(
            "Result approved successfully."
        );

    }


    /* =====================================================
       PUBLISH
       Approved → Published
    ===================================================== */

    if ($action === "publish") {

        if ($current_status !== "Approved") {

            redirectWithMessage(
                "Only approved results can be published.",
                "error"
            );

        }


        $new_status =
            "Published";


        $sql = "
            UPDATE results
            SET
                status = ?
            WHERE result_id = ?
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            redirectWithMessage(
                "Unable to publish result.",
                "error"
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_status,
            $result_id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            mysqli_stmt_close($stmt);

            redirectWithMessage(
                "Failed to publish result.",
                "error"
            );

        }


        mysqli_stmt_close($stmt);

        $notification_data = getResultNotificationData($conn, $result_id);
        if ($notification_data) {
            notifyResultParty($notification_data, 'student');
        }

        redirectWithMessage(
            "Result published successfully."
        );

    }


    /* =====================================================
       DELETE RESULT
    ===================================================== */

    if ($action === "delete") {

        /*
         * Published results should not be deleted.
         */

        if ($current_status === "Published") {

            redirectWithMessage(
                "Published results cannot be deleted.",
                "error"
            );

        }


        /* Save notification details before deleting the row. */
        $notification_data = getResultNotificationData($conn, $result_id);

        $sql = "
            DELETE FROM results
            WHERE result_id = ?
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            redirectWithMessage(
                "Unable to delete result.",
                "error"
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $result_id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            mysqli_stmt_close($stmt);

            redirectWithMessage(
                "Failed to delete result.",
                "error"
            );

        }


        mysqli_stmt_close($stmt);

        if ($notification_data) {
            $notification_data['status'] = 'Deleted';
            notifyResultParty($notification_data, 'lecturer');
        }

        redirectWithMessage(
            "Result deleted successfully."
        );

    }

}


/* =========================================================
   GET MESSAGE
========================================================= */

$message =
    $_GET['message'] ?? '';

$message_type =
    $_GET['type'] ?? 'success';


if (
    $message_type !== 'success' &&
    $message_type !== 'error'
) {

    $message_type =
        'success';

}


/* =========================================================
   GET STATISTICS
========================================================= */

$stats_sql = "

    SELECT

        COUNT(*) AS total,

        SUM(status = 'Draft')
        AS draft,

        SUM(status = 'Submitted')
        AS submitted,

        SUM(status = 'Under Review')
        AS under_review,

        SUM(status = 'Returned')
        AS returned,

        SUM(status = 'Approved')
        AS approved,

        SUM(status = 'Published')
        AS published

    FROM results

";


$stats_result =
    mysqli_query(
        $conn,
        $stats_sql
    );


if (!$stats_result) {

    die(
        "Unable to load result statistics: " .
        mysqli_error($conn)
    );

}


$stats =
    mysqli_fetch_assoc(
        $stats_result
    );


/* =========================================================
   SEARCH AND FILTER
========================================================= */

$search =
    trim(
        $_GET['search'] ?? ''
    );


$status_filter =
    trim(
        $_GET['status'] ?? ''
    );


$allowed_statuses = [
    'Draft',
    'Submitted',
    'Under Review',
    'Returned',
    'Approved',
    'Published'
];


if (
    $status_filter !== '' &&
    !in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {

    $status_filter = '';

}


/* =========================================================
   GET RESULTS
========================================================= */

$sql = "

SELECT

    r.result_id,
    r.registration_id,

    r.ca_score,
    r.exam_score,
    r.total_score,

    r.letter_grade,
    r.grade_point,

    r.remark,
    r.status,
    r.created_at,

    st.matricule,
    st.first_name,
    st.last_name,

    c.course_code,
    c.course_title,

    acs.session_name,

    sem.semester_name

FROM results r

INNER JOIN course_registrations cr
    ON r.registration_id = cr.registration_id

INNER JOIN students st
    ON cr.student_id = st.student_id

INNER JOIN courses c
    ON cr.course_id = c.course_id

INNER JOIN academic_sessions acs
    ON cr.session_id = acs.session_id

INNER JOIN semesters sem
    ON cr.semester_id = sem.semester_id

WHERE 1 = 1

";


$params = [];
$types = "";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== '') {

    $sql .= "

        AND (
            st.first_name LIKE ?
            OR st.last_name LIKE ?
            OR st.matricule LIKE ?
            OR c.course_code LIKE ?
            OR c.course_title LIKE ?
        )

    ";

    $search_value =
        "%" . $search . "%";


    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;


    $types .= "sssss";

}


/* =========================================================
   STATUS FILTER
========================================================= */

if ($status_filter !== '') {

    $sql .= "
        AND r.status = ?
    ";


    $params[] =
        $status_filter;


    $types .= "s";

}


$sql .= "

    ORDER BY
        r.result_id DESC

";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    die(
        "Unable to load results: " .
        mysqli_error($conn)
    );

}


/* =========================================================
   BIND SEARCH PARAMETERS
========================================================= */

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
);


mysqli_stmt_close(
    $stmt
);


/* =========================================================
   TOTAL DISPLAYED RESULTS
========================================================= */

$displayed_count =
    mysqli_num_rows($result);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    UniResult - Results Management
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================================
   BODY
========================================================= */

body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        #f4f7fb;

    color:
        #1e293b;

    min-height:
        100vh;

}


/* =========================================================
   HEADER
========================================================= */

.header {

    background:
        linear-gradient(
            135deg,
            #003366,
            #00509e
        );

    color:
        white;

    padding:
        18px 35px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.12);

}


.header-left {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


.header-logo {

    width:
        42px;

    height:
        42px;

    border-radius:
        10px;

    background:
        rgba(255,255,255,.15);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        21px;

}


.header h1 {

    font-size:
        22px;

}


.admin-name {

    font-size:
        14px;

    opacity:
        .95;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width:
        95%;

    max-width:
        1500px;

    margin:
        30px auto;

}


/* =========================================================
   TOP BAR
========================================================= */

.top-bar {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        25px;

}


.top-bar h2 {

    color:
        #003366;

    font-size:
        27px;

}


.subtitle {

    color:
        #64748b;

    font-size:
        13px;

    margin-top:
        5px;

}


.back-link {

    color:
        #003366;

    text-decoration:
        none;

    font-weight:
        600;

    background:
        white;

    padding:
        10px 15px;

    border-radius:
        8px;

    border:
        1px solid #e2e8f0;

}


.back-link:hover {

    background:
        #f8fafc;

}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding:
        15px 18px;

    border-radius:
        9px;

    margin-bottom:
        25px;

    font-weight:
        600;

    position:
        relative;

}


.message.success {

    background:
        #dcfce7;

    color:
        #166534;

    border-left:
        5px solid #22c55e;

}


.message.error {

    background:
        #fee2e2;

    color:
        #991b1b;

    border-left:
        5px solid #ef4444;

}


.message-close {

    position:
        absolute;

    right:
        14px;

    top:
        10px;

    border:
        none;

    background:
        transparent;

    font-size:
        20px;

    cursor:
        pointer;

}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(7, 1fr);

    gap:
        15px;

    margin-bottom:
        30px;

}


.stat-card {

    background:
        white;

    padding:
        19px;

    border-radius:
        12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.06);

    border:
        1px solid #e5e7eb;

    transition:
        .2s;

}


.stat-card:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.09);

}


.stat-title {

    font-size:
        12px;

    color:
        #64748b;

    margin-bottom:
        8px;

}


.stat-number {

    font-size:
        27px;

    font-weight:
        700;

    color:
        #003366;

}


/* =========================================================
   SEARCH CARD
========================================================= */

.search-card {

    background:
        white;

    border:
        1px solid #e5e7eb;

    border-radius:
        12px;

    padding:
        18px;

    margin-bottom:
        20px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.05);

}


.search-form {

    display:
        flex;

    gap:
        10px;

    align-items:
        center;

}


.search-input {

    flex:
        1;

    padding:
        11px 14px;

    border:
        1px solid #cbd5e1;

    border-radius:
        7px;

    outline:
        none;

    font-family:
        inherit;

}


.search-input:focus {

    border-color:
        #00509e;

    box-shadow:
        0 0 0 3px
        rgba(0,80,158,.1);

}


.status-select {

    padding:
        11px 14px;

    border:
        1px solid #cbd5e1;

    border-radius:
        7px;

    background:
        white;

    outline:
        none;

    cursor:
        pointer;

}


.search-btn {

    background:
        #003366;

    color:
        white;

    border:
        none;

    padding:
        11px 18px;

    border-radius:
        7px;

    cursor:
        pointer;

    font-weight:
        600;

}


.search-btn:hover {

    background:
        #00509e;

}


.clear-btn {

    background:
        #e2e8f0;

    color:
        #334155;

    text-decoration:
        none;

    padding:
        11px 16px;

    border-radius:
        7px;

    font-weight:
        600;

}


/* =========================================================
   TABLE CARD
========================================================= */

.table-card {

    background:
        white;

    border-radius:
        12px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

    overflow:
        hidden;

}


/* =========================================================
   TABLE HEADER
========================================================= */

.table-header {

    padding:
        20px 22px;

    border-bottom:
        1px solid #e5e7eb;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

}


.table-header h3 {

    color:
        #003366;

}


.result-count {

    color:
        #64748b;

    font-size:
        13px;

}


/* =========================================================
   TABLE WRAPPER
========================================================= */

.table-wrapper {

    width:
        100%;

    overflow-x:
        auto;

}


/* =========================================================
   TABLE
========================================================= */

table {

    width:
        100%;

    min-width:
        1400px;

    border-collapse:
        collapse;

}


thead {

    background:
        #003366;

    color:
        white;

}


th {

    padding:
        14px 12px;

    text-align:
        left;

    font-size:
        12px;

    white-space:
        nowrap;

}


td {

    padding:
        14px 12px;

    border-bottom:
        1px solid #e5e7eb;

    font-size:
        13px;

    white-space:
        nowrap;

}


tbody tr:hover {

    background:
        #f8fbff;

}


/* =========================================================
   STUDENT
========================================================= */

.student-name {

    font-weight:
        700;

    color:
        #003366;

}


.matricule {

    color:
        #64748b;

    font-size:
        12px;

    margin-top:
        3px;

}


/* =========================================================
   COURSE
========================================================= */

.course-code {

    font-weight:
        700;

    color:
        #003366;

}


.course-title {

    color:
        #64748b;

    font-size:
        12px;

    margin-top:
        3px;

}


/* =========================================================
   SCORE
========================================================= */

.total {

    font-weight:
        700;

    color:
        #003366;

}


.grade {

    font-weight:
        700;

}


/* =========================================================
   STATUS
========================================================= */

.status {

    display:
        inline-block;

    padding:
        6px 10px;

    border-radius:
        20px;

    font-size:
        11px;

    font-weight:
        700;

}


.status-draft {

    background:
        #f1f5f9;

    color:
        #475569;

}


.status-submitted {

    background:
        #dbeafe;

    color:
        #1d4ed8;

}


.status-review {

    background:
        #fef3c7;

    color:
        #92400e;

}


.status-returned {

    background:
        #fee2e2;

    color:
        #b91c1c;

}


.status-approved {

    background:
        #dcfce7;

    color:
        #166534;

}


.status-published {

    background:
        #ede9fe;

    color:
        #6d28d9;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display:
        flex;

    gap:
        7px;

    align-items:
        center;

}


.actions form {

    margin:
        0;

}


.btn {

    border:
        none;

    border-radius:
        6px;

    padding:
        8px 11px;

    font-size:
        12px;

    font-weight:
        600;

    cursor:
        pointer;

    text-decoration:
        none;

    display:
        inline-block;

    transition:
        .2s;

    white-space:
        nowrap;

}


.btn:hover {

    transform:
        translateY(-1px);

}


/* Review */

.btn-review {

    background:
        #f59e0b;

    color:
        white;

}


/* Return */

.btn-return {

    background:
        #dc2626;

    color:
        white;

}


/* Approve */

.btn-approve {

    background:
        #16a34a;

    color:
        white;

}


/* Publish */

.btn-publish {

    background:
        #7c3aed;

    color:
        white;

}


/* Delete */

.btn-delete {

    background:
        #ef4444;

    color:
        white;

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    text-align:
        center;

    padding:
        60px 30px;

    color:
        #64748b;

}


.empty-icon {

    font-size:
        45px;

    margin-bottom:
        10px;

}


.empty h3 {

    color:
        #334155;

    margin-bottom:
        5px;

}


/* =========================================================
   MODAL
========================================================= */

.modal {

    display:
        none;

    position:
        fixed;

    z-index:
        9999;

    left:
        0;

    top:
        0;

    width:
        100%;

    height:
        100%;

    background:
        rgba(0,0,0,.55);

    align-items:
        center;

    justify-content:
        center;

    padding:
        20px;

}


.modal-content {

    background:
        white;

    width:
        100%;

    max-width:
        500px;

    border-radius:
        13px;

    padding:
        25px;

    box-shadow:
        0 15px 40px
        rgba(0,0,0,.2);

    animation:
        modalOpen .2s ease;

}


@keyframes modalOpen {

    from {
        opacity: 0;
        transform: scale(.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }

}


.modal-content h3 {

    color:
        #003366;

    margin-bottom:
        10px;

}


.modal-content p {

    color:
        #64748b;

    font-size:
        14px;

    margin-bottom:
        15px;

}


.modal textarea {

    width:
        100%;

    min-height:
        130px;

    border:
        1px solid #cbd5e1;

    border-radius:
        7px;

    padding:
        12px;

    resize:
        vertical;

    font-family:
        inherit;

    outline:
        none;

}


.modal textarea:focus {

    border-color:
        #00509e;

    box-shadow:
        0 0 0 3px
        rgba(0,80,158,.1);

}


.modal-actions {

    display:
        flex;

    justify-content:
        flex-end;

    gap:
        10px;

    margin-top:
        15px;

}


.cancel-btn {

    background:
        #e2e8f0;

    color:
        #334155;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .stats {

        grid-template-columns:
            repeat(4, 1fr);

    }

}


@media (max-width: 800px) {

    .header {

        padding:
            16px 18px;

    }


    .header h1 {

        font-size:
            17px;

    }


    .admin-name {

        font-size:
            12px;

    }


    .container {

        width:
            94%;

        margin:
            20px auto;

    }


    .top-bar {

        align-items:
            flex-start;

        gap:
            15px;

        flex-direction:
            column;

    }


    .top-bar h2 {

        font-size:
            23px;

    }


    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .search-form {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    .search-input,
    .status-select,
    .search-btn,
    .clear-btn {

        width:
            100%;

    }

}


@media (max-width: 500px) {

    .stats {

        grid-template-columns:
            1fr 1fr;

        gap:
            10px;

    }


    .stat-card {

        padding:
            14px;

    }


    .stat-number {

        font-size:
            21px;

    }


    .table-header {

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            5px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="header-left">

    <?php include __DIR__ . '/../../brand.html'; ?>

</div>


    <div class="admin-name">

        👤 Administrator

    </div>

</header>



<!-- =====================================================
     MAIN CONTAINER
===================================================== -->

<main class="container">


<!-- =====================================================
     PAGE TOP
===================================================== -->

<div class="top-bar">

    <div>

        <h2>
            Results Management
        </h2>

        <div class="subtitle">

            Review, approve and publish student results.

        </div>

    </div>


    <a
        href="../dashboard.php"
        class="back-link"
    >
        ← Back to Dashboard
    </a>

</div>



<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if ($message): ?>

<div
    class="message <?= clean($message_type) ?>"
    id="messageBox"
>

    <?= clean($message) ?>


    <button
        type="button"
        class="message-close"
        onclick="closeMessage()"
    >
        ×
    </button>

</div>

<?php endif; ?>



<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


    <!-- TOTAL -->

    <div class="stat-card">

        <div class="stat-title">
            Total Results
        </div>

        <div class="stat-number">
            <?= intval($stats['total'] ?? 0) ?>
        </div>

    </div>


    <!-- DRAFT -->

    <div class="stat-card">

        <div class="stat-title">
            Draft
        </div>

        <div class="stat-number">
            <?= intval($stats['draft'] ?? 0) ?>
        </div>

    </div>


    <!-- SUBMITTED -->

    <div class="stat-card">

        <div class="stat-title">
            Submitted
        </div>

        <div class="stat-number">
            <?= intval($stats['submitted'] ?? 0) ?>
        </div>

    </div>


    <!-- UNDER REVIEW -->

    <div class="stat-card">

        <div class="stat-title">
            Under Review
        </div>

        <div class="stat-number">
            <?= intval($stats['under_review'] ?? 0) ?>
        </div>

    </div>


    <!-- RETURNED -->

    <div class="stat-card">

        <div class="stat-title">
            Returned
        </div>

        <div class="stat-number">
            <?= intval($stats['returned'] ?? 0) ?>
        </div>

    </div>


    <!-- APPROVED -->

    <div class="stat-card">

        <div class="stat-title">
            Approved
        </div>

        <div class="stat-number">
            <?= intval($stats['approved'] ?? 0) ?>
        </div>

    </div>


    <!-- PUBLISHED -->

    <div class="stat-card">

        <div class="stat-title">
            Published
        </div>

        <div class="stat-number">
            <?= intval($stats['published'] ?? 0) ?>
        </div>

    </div>


</div>



<!-- =====================================================
     SEARCH AND FILTER
===================================================== -->

<div class="search-card">

<form
    method="GET"
    class="search-form"
>


<input
    type="text"
    name="search"
    class="search-input"
    placeholder="Search student, matricule or course..."
    value="<?= clean($search) ?>"
>


<select
    name="status"
    class="status-select"
>

    <option value="">
        All Statuses
    </option>


    <?php foreach ($allowed_statuses as $status): ?>

        <option
            value="<?= clean($status) ?>"
            <?= $status_filter === $status
                ? 'selected'
                : ''
            ?>
        >
            <?= clean($status) ?>
        </option>

    <?php endforeach; ?>

</select>


<button
    type="submit"
    class="search-btn"
>
    🔍 Search
</button>


<?php if (
    $search !== '' ||
    $status_filter !== ''
): ?>

<a
    href="index.php"
    class="clear-btn"
>
    Clear
</a>

<?php endif; ?>


</form>

</div>



<!-- =====================================================
     RESULTS TABLE
===================================================== -->

<div class="table-card">


<div class="table-header">

    <h3>
        All Results
    </h3>


    <div class="result-count">

        Showing
        <strong>
            <?= $displayed_count ?>
        </strong>
        result(s)

    </div>

</div>



<div class="table-wrapper">

<table>


<thead>

<tr>

    <th>
        Student
    </th>

    <th>
        Course
    </th>

    <th>
        Session
    </th>

    <th>
        Semester
    </th>

    <th>
        CA
    </th>

    <th>
        Exam
    </th>

    <th>
        Total
    </th>

    <th>
        Grade
    </th>

    <th>
        Point
    </th>

    <th>
        Remark
    </th>

    <th>
        Status
    </th>

    <th>
        Actions
    </th>

</tr>

</thead>



<tbody>


<?php if ($displayed_count > 0): ?>


<?php while (
    $row = mysqli_fetch_assoc($result)
): ?>


<tr>


<!-- =====================================================
     STUDENT
===================================================== -->

<td>

    <div class="student-name">

        <?= clean(
            $row['first_name'] .
            " " .
            $row['last_name']
        ) ?>

    </div>


    <div class="matricule">

        <?= clean(
            $row['matricule']
        ) ?>

    </div>

</td>



<!-- =====================================================
     COURSE
===================================================== -->

<td>

    <div class="course-code">

        <?= clean(
            $row['course_code']
        ) ?>

    </div>


    <div class="course-title">

        <?= clean(
            $row['course_title']
        ) ?>

    </div>

</td>



<!-- =====================================================
     SESSION
===================================================== -->

<td>

    <?= clean(
        $row['session_name']
    ) ?>

</td>



<!-- =====================================================
     SEMESTER
===================================================== -->

<td>

    <?= clean(
        $row['semester_name']
    ) ?>

</td>



<!-- =====================================================
     CA
===================================================== -->

<td>

    <?= clean(
        $row['ca_score']
    ) ?>

</td>



<!-- =====================================================
     EXAM
===================================================== -->

<td>

    <?= clean(
        $row['exam_score']
    ) ?>

</td>



<!-- =====================================================
     TOTAL
===================================================== -->

<td class="total">

    <?= clean(
        $row['total_score']
    ) ?>

</td>



<!-- =====================================================
     GRADE
===================================================== -->

<td class="grade">

    <?= clean(
        $row['letter_grade'] ?? "-"
    ) ?>

</td>



<!-- =====================================================
     POINT
===================================================== -->

<td>

    <?= clean(
        $row['grade_point'] ?? "-"
    ) ?>

</td>



<!-- =====================================================
     REMARK
===================================================== -->

<td>

<?php if (
    !empty($row['remark'])
): ?>

    <?= clean(
        $row['remark']
    ) ?>

<?php else: ?>

    <span
        style="color:#94a3b8;"
    >
        —
    </span>

<?php endif; ?>

</td>



<!-- =====================================================
     STATUS
===================================================== -->

<td>

<?php

$status_class =
    "status-draft";


switch ($row['status']) {

    case "Submitted":

        $status_class =
            "status-submitted";

        break;


    case "Under Review":

        $status_class =
            "status-review";

        break;


    case "Returned":

        $status_class =
            "status-returned";

        break;


    case "Approved":

        $status_class =
            "status-approved";

        break;


    case "Published":

        $status_class =
            "status-published";

        break;

}

?>


<span
    class="status <?= $status_class ?>"
>

    <?= clean(
        $row['status']
    ) ?>

</span>

</td>



<!-- =====================================================
     ACTIONS
===================================================== -->

<td>

<div class="actions">


<!-- =================================================
     SUBMITTED → REVIEW
================================================= -->

<?php if (
    $row['status'] === "Submitted"
): ?>


<form
    method="POST"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= clean($csrf_token) ?>"
    >


    <input
        type="hidden"
        name="result_id"
        value="<?= intval($row['result_id']) ?>"
    >


    <input
        type="hidden"
        name="action"
        value="review"
    >


    <button
        type="submit"
        class="btn btn-review"
    >
        🔍 Review
    </button>

</form>


<?php endif; ?>



<!-- =================================================
     UNDER REVIEW
================================================= -->

<?php if (
    $row['status'] === "Under Review"
): ?>


<button
    type="button"
    class="btn btn-return"
    onclick="openReturnModal(
        <?= intval($row['result_id']) ?>
    )"
>
    ↩ Return
</button>



<form
    method="POST"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= clean($csrf_token) ?>"
    >


    <input
        type="hidden"
        name="result_id"
        value="<?= intval($row['result_id']) ?>"
    >


    <input
        type="hidden"
        name="action"
        value="approve"
    >


    <button
        type="submit"
        class="btn btn-approve"
        onclick="
            return confirm(
                'Are you sure you want to approve this result?'
            );
        "
    >
        ✓ Approve
    </button>

</form>


<?php endif; ?>



<!-- =================================================
     APPROVED → PUBLISH
================================================= -->

<?php if (
    $row['status'] === "Approved"
): ?>


<form
    method="POST"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= clean($csrf_token) ?>"
    >


    <input
        type="hidden"
        name="result_id"
        value="<?= intval($row['result_id']) ?>"
    >


    <input
        type="hidden"
        name="action"
        value="publish"
    >


    <button
        type="submit"
        class="btn btn-publish"
        onclick="
            return confirm(
                'Publish this result? Students will be able to see it.'
            );
        "
    >
        🚀 Publish
    </button>

</form>


<?php endif; ?>



<!-- =================================================
     DELETE
================================================= -->

<?php if (
    $row['status'] !== "Published"
): ?>


<form
    method="POST"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= clean($csrf_token) ?>"
    >


    <input
        type="hidden"
        name="result_id"
        value="<?= intval($row['result_id']) ?>"
    >


    <input
        type="hidden"
        name="action"
        value="delete"
    >


    <button
        type="submit"
        class="btn btn-delete"
        onclick="
            return confirm(
                'WARNING: This will permanently delete this result. Continue?'
            );
        "
    >
        🗑 Delete
    </button>

</form>


<?php endif; ?>


</div>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
    colspan="12"
    class="empty"
>

    <div class="empty-icon">
        📄
    </div>


    <h3>
        No Results Found
    </h3>


    <p>
        There are no results matching your search or filter.
    </p>

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


</main>



<!-- =====================================================
     RETURN RESULT MODAL
===================================================== -->

<div
    id="returnModal"
    class="modal"
>

<div class="modal-content">


<h3>
    Return Result to Lecturer
</h3>


<p>

    Explain what needs to be corrected.
    The lecturer will see this message.

</p>


<form
    method="POST"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?= clean($csrf_token) ?>"
>


<input
    type="hidden"
    name="action"
    value="return"
>


<input
    type="hidden"
    name="result_id"
    id="returnResultId"
>


<textarea
    name="remark"
    maxlength="1000"
    placeholder="Example: Please verify the examination mark and resubmit."
    required
></textarea>


<div class="modal-actions">


<button
    type="button"
    class="btn cancel-btn"
    onclick="closeReturnModal()"
>
    Cancel
</button>


<button
    type="submit"
    class="btn btn-return"
>
    ↩ Return Result
</button>


</div>


</form>

</div>

</div>



<script>

/* =========================================================
   RETURN MODAL
========================================================= */

const returnModal =
    document.getElementById(
        "returnModal"
    );


const returnResultId =
    document.getElementById(
        "returnResultId"
    );


function openReturnModal(resultId)
{

    returnResultId.value =
        resultId;

    returnModal.style.display =
        "flex";

}


function closeReturnModal()
{

    returnModal.style.display =
        "none";

    returnResultId.value =
        "";

}


/* =========================================================
   CLOSE MODAL WHEN CLICKING OUTSIDE
========================================================= */

window.addEventListener(
    "click",
    function(event)
    {

        if (
            event.target ===
            returnModal
        ) {

            closeReturnModal();

        }

    }
);


/* =========================================================
   ESC KEY CLOSES MODAL
========================================================= */

window.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Escape"
        ) {

            closeReturnModal();

        }

    }
);


/* =========================================================
   CLOSE MESSAGE
========================================================= */

function closeMessage()
{

    const messageBox =
        document.getElementById(
            "messageBox"
        );


    if (messageBox) {

        messageBox.style.display =
            "none";

    }

}


/* =========================================================
   AUTO HIDE MESSAGE
========================================================= */

setTimeout(
    function()
    {

        closeMessage();

    },
    5000
);

</script>


</body>

</html>