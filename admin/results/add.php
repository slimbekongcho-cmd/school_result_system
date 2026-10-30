<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$message = "";

/*
|--------------------------------------------------------------------------
| Get Course Registrations
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            cr.registration_id,

            s.matricule,
            s.first_name,
            s.last_name,

            c.course_code,
            c.course_title,

            sem.semester_name,

            a.session_name

        FROM course_registrations cr

        INNER JOIN students s
            ON cr.student_id = s.student_id

        INNER JOIN courses c
            ON cr.course_id = c.course_id

        INNER JOIN semesters sem
            ON cr.semester_id = sem.semester_id

        INNER JOIN academic_sessions a
            ON cr.session_id = a.session_id

        LEFT JOIN results r
            ON cr.registration_id = r.registration_id

        WHERE r.result_id IS NULL

        ORDER BY s.first_name ASC";

$registration_result = mysqli_query($conn, $sql);


/*
|--------------------------------------------------------------------------
| Add Result
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $registration_id = $_POST['registration_id'];
    $ca_score = $_POST['ca_score'];
    $exam_score = $_POST['exam_score'];

    /*
    |--------------------------------------------------------------------------
    | Validate scores
    |--------------------------------------------------------------------------
    */

    if (
        empty($registration_id) ||
        $ca_score === "" ||
        $exam_score === ""
    ) {

        $message = "Please fill in all fields.";

    } elseif ($ca_score < 0 || $ca_score > 30) {

        $message = "CA score must be between 0 and 30.";

    } elseif ($exam_score < 0 || $exam_score > 70) {

        $message = "Exam score must be between 0 and 70.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Calculate Total
        |--------------------------------------------------------------------------
        */

        $total_score = $ca_score + $exam_score;


        /*
        |--------------------------------------------------------------------------
        | Calculate Grade
        |--------------------------------------------------------------------------
        */

        if ($total_score >= 80) {

            $letter_grade = "A";
            $grade_point = 4.0;
            $remark = "Excellent";

        } elseif ($total_score >= 70) {

            $letter_grade = "B";
            $grade_point = 3.0;
            $remark = "Very Good";

        } elseif ($total_score >= 60) {

            $letter_grade = "C";
            $grade_point = 2.0;
            $remark = "Good";

        } elseif ($total_score >= 50) {

            $letter_grade = "D";
            $grade_point = 1.0;
            $remark = "Pass";

        } else {

            $letter_grade = "F";
            $grade_point = 0.0;
            $remark = "Fail";
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = "Draft";


        /*
        |--------------------------------------------------------------------------
        | Insert Result
        |--------------------------------------------------------------------------
        */

        $sql = "INSERT INTO results
                (
                    registration_id,
                    ca_score,
                    exam_score,
                    total_score,
                    letter_grade,
                    grade_point,
                    remark,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iiidsdss",
            $registration_id,
            $ca_score,
            $exam_score,
            $total_score,
            $letter_grade,
            $grade_point,
            $remark,
            $status
        );

        if (mysqli_stmt_execute($stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Result - UniResult</title>


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


        /* Header */

        .header {
            background: #003366;
            color: white;
            padding: 22px 35px;
        }


        .header h1 {
            font-size: 26px;
        }


        /* Container */

        .container {
            width: 95%;
            max-width: 800px;
            margin: 40px auto;
        }


        /* Card */

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }


        .card h2 {
            color: #003366;
            margin-bottom: 25px;
        }


        /* Message */

        .message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }


        /* Form */

        .form-group {
            margin-bottom: 20px;
        }


        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }


        select,
        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            outline: none;
            background: white;
        }


        select:focus,
        input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0,51,102,0.1);
        }


        /* Registration */

        .registration-info {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 7px;
            margin-top: 10px;
            color: #475569;
            font-size: 14px;
        }


        /* Score information */

        .score-info {
            background: #eff6ff;
            color: #1e40af;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }


        /* Button */

        button {
            width: 100%;
            border: none;
            background: #003366;
            color: white;
            padding: 13px;
            border-radius: 7px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }


        button:hover {
            background: #00509e;
        }


        /* Back */

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #003366;
            text-decoration: none;
            font-weight: 600;
        }


        .back:hover {
            color: #00509e;
        }


        /* Mobile */

        @media (max-width: 600px) {

            .header {
                padding: 18px 20px;
            }

            .header h1 {
                font-size: 22px;
            }

            .container {
                width: 92%;
                margin: 25px auto;
            }

            .card {
                padding: 20px;
            }

        }

    </style>

</head>


<body>


<div class="header">

    <h1>UniResult - Add Result</h1>

</div>


<div class="container">

    <div class="card">

        <h2>Enter Student Result</h2>


        <?php if ($message): ?>

            <div class="message">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <div class="score-info">

            <strong>Score Structure:</strong>

            CA = 30 marks &nbsp; | &nbsp;
            Exam = 70 marks &nbsp; | &nbsp;
            Total = 100 marks

        </div>


        <form method="POST">


            <!-- Registration -->

            <div class="form-group">

                <label>
                    Student / Course Registration
                </label>


                <select
                    name="registration_id"
                    required
                >

                    <option value="">
                        -- Select Student & Course --
                    </option>


                    <?php while (
                        $registration =
                        mysqli_fetch_assoc(
                            $registration_result
                        )
                    ): ?>

                        <option
                            value="<?= $registration['registration_id'] ?>"
                        >

                            <?= htmlspecialchars(
                                $registration['matricule']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $registration['first_name']
                                . " "
                                . $registration['last_name']
                            ) ?>

                            |

                            <?= htmlspecialchars(
                                $registration['course_code']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $registration['course_title']
                            ) ?>

                            |

                            <?= htmlspecialchars(
                                $registration['semester_name']
                            ) ?>

                            |

                            <?= htmlspecialchars(
                                $registration['session_name']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- CA -->

            <div class="form-group">

                <label>
                    CA Score
                </label>

                <input
                    type="number"
                    name="ca_score"
                    min="0"
                    max="30"
                    step="0.01"
                    placeholder="Enter CA score (0 - 30)"
                    required
                >

            </div>


            <!-- Exam -->

            <div class="form-group">

                <label>
                    Examination Score
                </label>

                <input
                    type="number"
                    name="exam_score"
                    min="0"
                    max="70"
                    step="0.01"
                    placeholder="Enter exam score (0 - 70)"
                    required
                >

            </div>


            <!-- Submit -->

            <button type="submit">

                Save Result

            </button>


        </form>


        <a
            href="index.php"
            class="back"
        >

            ← Back to Results

        </a>

    </div>

</div>


</body>

</html>