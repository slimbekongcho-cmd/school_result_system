<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Get student information */
$sql = "SELECT
            s.student_id,
            s.first_name,
            s.last_name,
            s.matricule,
            s.level,
            p.programme_name,
            d.department_name,
            f.faculty_name
        FROM students s

        LEFT JOIN programmes p
            ON s.programme_id = p.programme_id

        LEFT JOIN departments d
            ON s.department_id = d.department_id

        LEFT JOIN faculties f
            ON s.faculty_id = f.faculty_id

        WHERE s.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$student_result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($student_result);

if (!$student) {
    die("Student profile not found.");
}

$student_id = $student['student_id'];


/* Get results */
$sql = "SELECT
            c.course_code,
            c.course_title,
            c.credit_unit,
            r.ca_score,
            r.exam_score,
            r.total_score,
            r.letter_grade,
            r.grade_point,
            r.remark,
            sem.semester_name,
            a.session_name

        FROM results r

        INNER JOIN course_registrations cr
            ON r.registration_id = cr.registration_id

        INNER JOIN courses c
            ON cr.course_id = c.course_id

        LEFT JOIN semesters sem
            ON cr.semester_id = sem.semester_id

        LEFT JOIN academic_sessions a
            ON cr.session_id = a.session_id

        WHERE cr.student_id = ?
        AND r.status = 'Published'

        ORDER BY
            a.session_id DESC,
            sem.semester_id ASC,
            c.course_code ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$results = mysqli_stmt_get_result($stmt);
$total_results = mysqli_num_rows($results);


/* Calculate CGPA */

$total_points = 0;
$total_credits = 0;

$results_list = [];

while ($row = mysqli_fetch_assoc($results)) {

    $results_list[] = $row;

    $total_points += $row['grade_point'] * $row['credit_unit'];
    $total_credits += $row['credit_unit'];
}

$cgpa = $total_credits > 0
    ? $total_points / $total_credits
    : 0;

/* Helper: map a letter grade to a badge color class (display only) */
function grade_class($letter) {
    $letter = strtoupper(trim($letter));
    if (in_array($letter, ['A', 'A+', 'A-'])) return 'grade-a';
    if (in_array($letter, ['B', 'B+', 'B-'])) return 'grade-b';
    if (in_array($letter, ['C', 'C+', 'C-'])) return 'grade-c';
    return 'grade-d';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Result Statement - UniResult</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

<style>

:root {
    --navy: #003366;
    --navy-deep: #002347;
    --blue: #0066cc;
    --light-blue: #4da3ff;
    --bg: #eef1f6;
    --text: #1e293b;
    --muted: #64748b;
    --border: #e2e8f0;
    --green: #0f9d68;
    --amber: #b8770a;
    --red: #c23636;
}

* {
    box-sizing: border-box;
}

body {
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    margin: 0;
    padding: 30px 20px;
    color: var(--text);
    -webkit-font-smoothing: antialiased;
}

h1, h2 {
    font-family: "Sora", "Segoe UI", Arial, sans-serif;
}

/* ===== TOP BAR (screen only) ===== */

.top-bar {
    max-width: 1000px;
    margin: 0 auto 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    background: #fff;
    color: var(--navy);
    border: 1.5px solid #cbd5e1;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s ease;
}

.back-button:hover {
    background: #eef2f7;
    border-color: var(--navy);
    transform: translateY(-2px);
}

.print-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--navy);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s ease;
    font-family: inherit;
}

.print-button:hover {
    background: var(--blue);
    transform: translateY(-2px);
}

/* ===== DOCUMENT ===== */

.result-container {
    max-width: 1000px;
    margin: auto;
    background: white;
    padding: 46px 50px 40px;
    border-radius: 14px;
    box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
    border-top: 6px solid var(--navy);
    position: relative;
    overflow: hidden;
}

.watermark {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}

.watermark span {
    font-family: "Sora", sans-serif;
    font-size: 130px;
    font-weight: 800;
    color: var(--navy);
    opacity: 0.035;
    transform: rotate(-28deg);
    white-space: nowrap;
}

.doc-inner {
    position: relative;
    z-index: 1;
}

/* HEADER */

.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-bottom: 3px solid var(--navy);
    padding-bottom: 22px;
    margin-bottom: 26px;
    flex-wrap: wrap;
}

.header-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}

.header-logo-mark {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--light-blue), var(--blue));
    color: var(--navy-deep);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: "Sora", sans-serif;
    font-size: 22px;
    font-weight: 800;
    flex-shrink: 0;
}

.header-brand h1 {
    color: var(--navy);
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 0.2px;
    line-height: 1.2;
}

.header-brand p {
    margin-top: 2px;
    color: var(--muted);
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-weight: 600;
}

.header-doc-type {
    text-align: right;
}

.header-doc-type .doc-title {
    color: var(--navy);
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 4px;
}

.header-doc-type .doc-meta {
    font-family: "IBM Plex Mono", monospace;
    font-size: 11.5px;
    color: var(--muted);
}

/* ===== STUDENT INFO ===== */

.student-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px 26px;
    margin-bottom: 26px;
    padding: 20px 22px;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 10px;
}

.student-info .info-item .info-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}

.student-info .info-item .info-value {
    font-size: 14.5px;
    font-weight: 600;
    color: var(--text);
}

.student-info .info-item.matric .info-value {
    font-family: "IBM Plex Mono", monospace;
    color: var(--navy);
}

/* ===== RESULTS TABLE ===== */

.table-wrap {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid var(--border);
}

table {
    width: 100%;
    min-width: 620px;
    border-collapse: collapse;
}

th,
td {
    border-bottom: 1px solid var(--border);
    border-right: 1px solid var(--border);
    padding: 11px 10px;
    text-align: center;
    font-size: 13.5px;
}

th:last-child, td:last-child { border-right: none; }

th {
    background: var(--navy);
    color: white;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

td:nth-child(2) {
    text-align: left;
    font-weight: 500;
}

td:nth-child(1) {
    font-family: "IBM Plex Mono", monospace;
    color: var(--muted);
    font-size: 12.5px;
}

tbody tr:nth-child(even) {
    background: #f8fafc;
}

tbody tr:last-child td {
    border-bottom: none;
}

.grade-cell {
    font-family: "IBM Plex Mono", monospace;
    font-weight: 700;
    font-size: 12.5px;
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    min-width: 30px;
}

.grade-a { background: #e5f7ee; color: var(--green); }
.grade-b { background: #eef4fb; color: var(--blue); }
.grade-c { background: #fdf3e0; color: var(--amber); }
.grade-d { background: #fdecec; color: var(--red); }

.empty {
    padding: 30px !important;
    color: var(--muted);
}

/* ===== SUMMARY ===== */

.summary {
    margin-top: 26px;
    display: flex;
    justify-content: flex-end;
}

.summary-box {
    background: var(--navy-deep);
    background: linear-gradient(135deg, var(--navy-deep), var(--navy));
    border-radius: 12px;
    padding: 20px 26px;
    min-width: 320px;
    color: white;
}

.summary-box .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.summary-box .summary-row:not(:last-child) {
    border-bottom: 1px solid rgba(255,255,255,.14);
}

.summary-box .summary-label {
    font-size: 13px;
    color: rgba(255,255,255,.7);
    font-weight: 600;
}

.summary-box .summary-value {
    font-family: "IBM Plex Mono", monospace;
    font-size: 18px;
    font-weight: 700;
    color: white;
}

.summary-box .summary-row.cgpa .summary-value {
    color: #7fbaff;
    font-size: 22px;
}

/* ===== VERIFICATION FOOTER ===== */

.verification {
    margin-top: 30px;
    padding-top: 16px;
    border-top: 1px dashed var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.verification-note {
    font-size: 11.5px;
    color: var(--muted);
    line-height: 1.6;
    max-width: 520px;
}

.verification-id {
    font-family: "IBM Plex Mono", monospace;
    font-size: 11.5px;
    color: var(--navy);
    background: #eef4fb;
    padding: 6px 12px;
    border-radius: 6px;
    white-space: nowrap;
}

/* ===== SIGNATURE ===== */

.signature {
    margin-top: 60px;
    display: flex;
    justify-content: space-between;
    gap: 30px;
}

.signature-block {
    width: 220px;
    text-align: center;
}

.signature-line {
    border-top: 1px solid #94a3b8;
    padding-top: 10px;
    font-size: 13px;
    color: var(--muted);
}

.seal {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    border: 2px solid var(--navy);
    outline: 1px solid var(--navy);
    outline-offset: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    transform: rotate(-8deg);
}

.seal-inner {
    font-family: "Sora", sans-serif;
    font-size: 9px;
    font-weight: 800;
    color: var(--navy);
    text-align: center;
    line-height: 1.5;
    letter-spacing: 0.5px;
}

/* ===== MOBILE ===== */

@media (max-width: 700px) {

    body {
        padding: 16px 12px;
    }

    .result-container {
        padding: 26px 20px;
        border-radius: 12px;
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-doc-type {
        text-align: left;
    }

    .header-brand h1 {
        font-size: 20px;
    }

    .watermark span {
        font-size: 70px;
    }

    .student-info {
        grid-template-columns: 1fr 1fr;
        padding: 16px;
    }

    .summary {
        justify-content: stretch;
    }

    .summary-box {
        min-width: 0;
        width: 100%;
    }

    .verification {
        flex-direction: column;
        align-items: flex-start;
    }

    .signature {
        flex-direction: column;
        gap: 40px;
        margin-top: 50px;
        align-items: center;
    }

}

@media (max-width: 420px) {

    .student-info {
        grid-template-columns: 1fr;
    }

}

/* ===== PRINT — force a single page ===== */

@media print {

    @page {
        size: A4;
        margin: 10mm 12mm;
    }

    html, body {
        background: white;
        padding: 0;
        margin: 0;
        font-size: 12px;
    }

    .top-bar {
        display: none;
    }

    .result-container {
        box-shadow: none;
        border-radius: 0;
        border-top: 4px solid var(--navy);
        width: 100%;
        max-width: none;
        padding: 14px 20px 16px;
        margin: 0;
    }

    .watermark span {
        font-size: 110px;
    }

    .header {
        padding-bottom: 14px;
        margin-bottom: 16px;
    }

    .header-logo-mark {
        width: 38px;
        height: 38px;
        font-size: 18px;
    }

    .header-brand h1 {
        font-size: 19px;
    }

    .student-info {
        padding: 12px 16px;
        margin-bottom: 16px;
        gap: 10px 20px;
    }

    .student-info .info-value {
        font-size: 13px;
    }

    table {
        min-width: 0;
    }

    th, td {
        padding: 6px 8px;
        font-size: 11.5px;
    }

    .summary {
        margin-top: 16px;
    }

    .summary-box {
        padding: 14px 18px;
        min-width: 260px;
    }

    .verification {
        margin-top: 16px;
        padding-top: 10px;
    }

    .signature {
        margin-top: 34px;
    }

    .seal {
        width: 64px;
        height: 64px;
    }

    table, .summary, .signature, .verification {
        page-break-inside: avoid;
    }

    tr {
        page-break-inside: avoid;
    }

}

</style>

</head>

<body>

<div class="top-bar">

    <a href="result.php" class="back-button">
        ← Back to Results
    </a>

    <button class="print-button" onclick="window.print()">
        🖨️ Print / Save as PDF
    </button>

</div>


<div class="result-container">

    <div class="watermark"><span>UNIRESULT</span></div>

    <div class="doc-inner">

        <div class="header">

            <div class="header-brand">
                <div class="header-logo-mark">U</div>
                <div>
                    <h1>UniResult</h1>
                    <p>Academic Result Management System</p>
                </div>
            </div>

            <div class="header-doc-type">
                <div class="doc-title">Official Statement of Results</div>
                <div class="doc-meta">Issued: <?= date("d M Y") ?></div>
            </div>

        </div>


        <div class="student-info">

            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value">
                    <?= htmlspecialchars($student['first_name'] . " " . $student['last_name']) ?>
                </div>
            </div>

            <div class="info-item matric">
                <div class="info-label">Matricule</div>
                <div class="info-value">
                    <?= htmlspecialchars($student['matricule']) ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Level</div>
                <div class="info-value">
                    <?= htmlspecialchars($student['level']) ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Programme</div>
                <div class="info-value">
                    <?= htmlspecialchars($student['programme_name'] ?? 'N/A') ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Department</div>
                <div class="info-value">
                    <?= htmlspecialchars($student['department_name'] ?? 'N/A') ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Faculty</div>
                <div class="info-value">
                    <?= htmlspecialchars($student['faculty_name'] ?? 'N/A') ?>
                </div>
            </div>

        </div>


        <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Title</th>
                    <th>Credit</th>
                    <th>CA</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>GP</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($total_results > 0): ?>

                <?php foreach ($results_list as $row): ?>

                    <tr>

                        <td><?= htmlspecialchars($row['course_code']) ?></td>
                        <td><?= htmlspecialchars($row['course_title']) ?></td>
                        <td><?= htmlspecialchars($row['credit_unit']) ?></td>
                        <td><?= htmlspecialchars($row['ca_score']) ?></td>
                        <td><?= htmlspecialchars($row['exam_score']) ?></td>
                        <td><?= htmlspecialchars($row['total_score']) ?></td>
                        <td>
                            <span class="grade-cell <?= grade_class($row['letter_grade']) ?>">
                                <?= htmlspecialchars($row['letter_grade']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['grade_point']) ?></td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8" class="empty">
                        No results available.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

        </div>


        <div class="summary">

            <div class="summary-box">

                <div class="summary-row">
                    <span class="summary-label">Total Credit Units</span>
                    <span class="summary-value"><?= number_format($total_credits, 1) ?></span>
                </div>

                <div class="summary-row cgpa">
                    <span class="summary-label">Cumulative GPA</span>
                    <span class="summary-value"><?= number_format($cgpa, 2) ?></span>
                </div>

            </div>

        </div>


        <div class="verification">

            <p class="verification-note">
                This is a system-generated statement of results issued by UniResult
                on behalf of the university. Any alteration renders this document invalid.
            </p>

            <span class="verification-id">
                REF: <?= strtoupper(htmlspecialchars($student['matricule'])) ?>-<?= date("Ymd") ?>
            </span>

        </div>


        <div class="signature">

            <div class="signature-block">
                <div class="signature-line">Student Signature</div>
            </div>

            <div class="signature-block">
                <div class="seal">
                    <div class="seal-inner">OFFICIAL<br>SEAL</div>
                </div>
                <div class="signature-line">Authorized Officer</div>
            </div>

        </div>

    </div>

</div>

</body>

</html>