<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// Set error handling to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

try {
    // Enable error logging for debugging
    error_log("send-product-query.php called at " . date('Y-m-d H:i:s'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Handle FormData from frontend
error_log("Raw POST data: " . print_r($_POST, true));
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

// Check if we have POST data
if (empty($_POST)) {
    error_log("ERROR: Empty POST data received!");
    http_response_code(400);
    echo json_encode([
        'ok' => false, 
        'message' => 'No data received. Check your form submission.'
    ]);
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
$manualPhpMailer = __DIR__ . '/PHPMailer/src/PHPMailer.php';

if (is_file($autoload)) {
    require $autoload;
} elseif (is_file($manualPhpMailer)) {
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
} else {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'PHPMailer is not installed. Run composer install or upload PHPMailer/src on the server.',
    ]);
    exit;
}

$config = require __DIR__ . '/mail-config.php';

$clean = static function ($value): string {
    return trim(str_replace(["\r", "\0"], '', (string) $value));
};

$fields = [];

foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        $value = implode(', ', array_map($clean, $value));
    }

    $key = $clean($key);
    $value = $clean($value);

    if ($key !== '' && $value !== '') {
        $fields[$key] = $value;
    }
}

$subject = $fields['Subject'] ?? 'Website Product Query';
unset($fields['Subject']);

$htmlRows = '';
$plainLines = [];

foreach ($fields as $key => $value) {
    $label = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
    $safeValue = nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    $htmlRows .= "<tr><th align=\"left\" style=\"padding:8px;border:1px solid #ddd;background:#f7f7f7;\">{$label}</th><td style=\"padding:8px;border:1px solid #ddd;\">{$safeValue}</td></tr>";
    $plainLines[] = "{$key}: {$value}";
}

$htmlBody = "<h2>Website Product Query</h2><table cellspacing=\"0\" cellpadding=\"0\" style=\"border-collapse:collapse;width:100%;\">{$htmlRows}</table>";
$plainBody = implode("\n", $plainLines);

$mail = new PHPMailer(true);

try {
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

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);

    if (!empty($fields['Email']) && filter_var($fields['Email'], FILTER_VALIDATE_EMAIL)) {
        $mail->addReplyTo($fields['Email'], $fields['Name'] ?? '');
    }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $plainBody;

    $mail->send();

    echo json_encode(['ok' => true, 'message' => 'Query sent successfully.']);
} catch (Exception $exception) {
    error_log("PHPMailer Exception: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Mail could not be sent.',
        'error' => $mail->ErrorInfo ?: $exception->getMessage(),
        'debug' => [
            'host' => $mail->Host ?? 'N/A',
            'port' => $mail->Port ?? 'N/A',
            'username' => substr($mail->Username ?? '', 0, 5) . '***',
            'encryption' => $config['encryption'] ?? 'N/A',
        ]
    ]);
}
} catch (Throwable $e) {
    error_log("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'An unexpected error occurred.',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
