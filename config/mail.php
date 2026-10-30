<?php

/*
|--------------------------------------------------------------------------
| UniResult Gmail SMTP Configuration
|--------------------------------------------------------------------------
*/

// The Gmail address you created the App Password for
define('MAIL_USERNAME', 'cot-result.notifications@gmail.com');

// Paste the 16-character App Password here.
// REMOVE ALL SPACES from the App Password.
define('MAIL_PASSWORD', 'gpckgnfoxndmbxsf');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');

// This is the name students will see
define('MAIL_FROM_NAME', 'COT-Result System');

// Usually the same Gmail address
define('MAIL_FROM_ADDRESS', MAIL_USERNAME);