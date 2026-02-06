<?php

// ================================
// CONFIGURATION
// ================================
$to_email          = "multialarmspro@gmail.com";
$site_name         = "MultiAlarms";
$secret_key        = "6Leu9GEsAAAAAKJbnObq2Z5Q1wkJ9KfEZhwAs-y9";      // ← Paste your reCAPTCHA secret key here
$from_name         = "MultiAlarms Contact Form";
$success_redirect  = "/thank-you.html";           // Page to redirect after success
$error_redirect    = "/contact.html";             // Back to contact page on error

// ================================
// reCAPTCHA VERIFICATION
// ================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: $error_redirect");
    exit;
}

$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptcha_response)) {
    // No CAPTCHA solved
    header("Location: $error_redirect?error=captcha");
    exit;
}

$verify_url = "https://www.google.com/recaptcha/api/siteverify";
$verify_data = [
    'secret'   => $secret_key,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($verify_data),
    ]
];

$context  = stream_context_create($options);
$result   = file_get_contents($verify_url, false, $context);
$response = json_decode($result);

if (!$response || !$response->success) {
    // CAPTCHA failed
    header("Location: $error_redirect?error=captcha");
    exit;
}

// ================================
// Form data
// ================================
$name    = trim($_POST['name']    ?? 'Not provided');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');

// Basic validation
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: $error_redirect?error=email");
    exit;
}

if (empty($message)) {
    header("Location: $error_redirect?error=message");
    exit;
}

// ================================
// Prepare email
// ================================
$subject = "New Contact from $site_name";

$body = "You received a new message:\n\n";
$body .= "Name:    $name\n";
$body .= "Email:   $email\n";
$body .= "Message:\n$message\n\n";
$body .= "IP:      " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
$body .= "Time:    " . date('r') . "\n";

$headers = "From: $from_name <no-reply@multialarms.com>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// ================================
// Send email
// ================================
if (mail($to_email, $subject, $body, $headers)) {
    header("Location: $success_redirect");
} else {
    // You can log the error here if you want
    // error_log("Mail failed: " . print_r(error_get_last(), true));
    header("Location: $error_redirect?error=send");
}

exit;
