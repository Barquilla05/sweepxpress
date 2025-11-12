<?php
// SMTP Configuration for PHPMailer
// 
// Tiyakin na ang file na ito ay nasa: /sweepxpress/includes/email_config.php

// Palitan ang mga ito ng iyong aktwal na credentials!
// Kung gumagamit ng Gmail, kailangan mo ng 'App Password', hindi ang iyong normal na password.

// Mail Server Configuration
define('SMTP_HOST', 'smtp.gmail.com');      // O 'smtp.mail.yahoo.com', etc.
define('SMTP_USER', 'iyong_email_address@gmail.com'); 
define('SMTP_PASS', 'iyong_app_password_o_key'); // NAPAKAHALAGA: App Password kung Gmail
define('SMTP_PORT', 465);                   // Karaniwan: 465 para sa SSL
define('SMTP_SECURE', 'ssl');               // 'ssl' (karaniwan sa 465) o 'tls' (karaniwan sa 587)

// PHPMailer Library is now ready to be used in forgot_password.php
?>