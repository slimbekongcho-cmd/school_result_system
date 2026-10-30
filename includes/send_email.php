<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/mail.php';


/*
|--------------------------------------------------------------------------
| SEND EMAIL FUNCTION
|--------------------------------------------------------------------------
*/

function sendEmail($to, $recipientName, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {

        /*
        |--------------------------------------------------------------------------
        | SMTP SETTINGS
        |--------------------------------------------------------------------------
        */

        $mail->isSMTP();

        $mail->Host = MAIL_HOST;

        $mail->SMTPAuth = true;

        $mail->Username = MAIL_USERNAME;

        $mail->Password = MAIL_PASSWORD;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = MAIL_PORT;


        /*
        |--------------------------------------------------------------------------
        | SENDER
        |--------------------------------------------------------------------------
        */

        $mail->setFrom(
            MAIL_FROM_ADDRESS,
            MAIL_FROM_NAME
        );


        /*
        |--------------------------------------------------------------------------
        | RECIPIENT
        |--------------------------------------------------------------------------
        */

        $mail->addAddress(
            $to,
            $recipientName
        );


        /*
        |--------------------------------------------------------------------------
        | EMAIL FORMAT
        |--------------------------------------------------------------------------
        */

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = $message;


        /*
        |--------------------------------------------------------------------------
        | PLAIN TEXT VERSION
        |--------------------------------------------------------------------------
        */

        $mail->AltBody = strip_tags($message);


        /*
        |--------------------------------------------------------------------------
        | SEND
        |--------------------------------------------------------------------------
        */

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            "Email could not be sent: " .
            $mail->ErrorInfo
        );

        return false;
    }
}