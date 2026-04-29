<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require 'mail-config.php';

$mail = new PHPMailer(true);

try {
    echo "Testing SMTP Configuration...\n";
    echo "Host: " . $config['host'] . "\n";
    echo "Port: " . $config['port'] . "\n";
    echo "Username: " . $config['username'] . "\n";
    echo "Encryption: " . $config['encryption'] . "\n\n";

    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->Port = $config['port'];

    // Add SSL/TLS verification handling
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    if ($config['encryption'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    echo "Attempting SMTP connection...\n";
    $mail->smtpConnect();
    echo "✓ SMTP connection successful!\n";

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress('test@example.com', 'Test Recipient');
    $mail->isHTML(false);
    $mail->Subject = 'Test Email from PHPMailer';
    $mail->Body = 'This is a test email to verify SMTP configuration.';

    echo "Sending test email...\n";
    $mail->send();
    echo "✓ Test email sent successfully!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "SMTP Error Info: " . $mail->ErrorInfo . "\n";
}
