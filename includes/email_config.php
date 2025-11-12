<?php
// E:\xampp\htdocs\sweepxpress\includes\email_config.php

// =================================================================
// SOLUSYON PARA SA "CONSTANT ALREADY DEFINED" WARNING:
// Ginagamit ang if (!defined()) para maiwasan ang re-definition error.
// =================================================================

// Mail Server Configuration
if (!defined('SMTP_HOST')) {
    // Host: smtp.gmail.com
    define('SMTP_HOST', 'smtp.gmail.com');
}

if (!defined('SMTP_USER')) {
    // IYONG GMAIL EMAIL ADDRESS
    define('SMTP_USER', 'ianpaul.barquilla2001@gmail.com'); 
}

if (!defined('SMTP_PASS')) {
    // IYONG APP PASSWORD
    define('SMTP_PASS', 'rimjgykkvdnokbuu'); 
}

if (!defined('SMTP_PORT')) {
    // Port: Karaniwang 465 (para sa SSL) o 587 (para sa TLS)
    define('SMTP_PORT', 465); 
}

if (!defined('SMTP_SECURE')) {
    define('SMTP_SECURE', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS);
}


// =================================================================

// PHPMailer Library is now ready to be used in forgot_password.php
?>