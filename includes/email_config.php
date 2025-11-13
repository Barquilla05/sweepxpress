<?php
// E:\xampp\htdocs\sweepxpress\includes\email_config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // siguraduhing naka-install ang PHPMailer via Composer

// Mail Server Configuration
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_USER')) define('SMTP_USER', 'ianpaul.barquilla2001@gmail.com'); // Gmail
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'rimjgykkvdnokbuu'); // App password
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465); 
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', PHPMailer::ENCRYPTION_SMTPS);

// Gumawa ng ready-to-use PHPMailer object
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_USER, 'SweepXpress'); // pangalan ng sender
    $mail->isHTML(true); // HTML email
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
    exit;
}
?>
