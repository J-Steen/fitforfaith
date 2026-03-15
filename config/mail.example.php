<?php
// Copy to config/mail.php and fill in your mail credentials.
define('MAIL_DRIVER',       getenv('MAIL_DRIVER')      ?: 'smtp');      // 'smtp' or 'mail'
define('MAIL_HOST',         getenv('MAIL_HOST')         ?: 'smtp.yourhost.com');
define('MAIL_PORT',         (int)(getenv('MAIL_PORT')   ?: 587));
define('MAIL_USERNAME',     getenv('MAIL_USERNAME')     ?: 'your@email.com');
define('MAIL_PASSWORD',     getenv('MAIL_PASSWORD')     ?: 'your_password');
define('MAIL_ENCRYPTION',   getenv('MAIL_ENCRYPTION')   ?: 'tls');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@fitforfaith.co.za');
define('MAIL_FROM_NAME',    getenv('MAIL_FROM_NAME')    ?: APP_NAME);
