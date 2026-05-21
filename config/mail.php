<?php
/**
 * config/mail.php
 * Configuration SMTP (PHPMailer)
 * Optional MAIL_* environment variables are supported without requiring dotenv.
 */
return [
    'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME') ?: '',
    'password'   => getenv('MAIL_PASSWORD') ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@intellihire.com',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'IntelliHire',
    'debug'      => (int)(getenv('MAIL_DEBUG') ?: 0),
];

