<?php
/**
 * send-mail.php — contact form mailer for konato.be
 *
 * Deploy this file to the WordPress server root (www.konato.be/send-mail.php).
 * The static site's contact-form.js POSTs JSON to it cross-origin.
 *
 * Requirements: PHP 7.4+, mail() enabled on the server (standard on cPanel/Plesk).
 */

// ── Configuration ────────────────────────────────────────────────────────────
define('RECIPIENT',    'info@konato.be');
define('SITE_NAME',    'Konato');
define('RATE_LIMIT',   10);          // max submissions per IP per hour
define('RATE_DIR',     sys_get_temp_dir() . '/konato_rl/');

// Allowed origins (static site domain + live WP site)
$ALLOWED_ORIGINS = [
    'https://at-yourservice.github.io',
    'https://www.konato.be',
    'http://localhost:8080',          // local dev
];
// ─────────────────────────────────────────────────────────────────────────────

// ── CORS ─────────────────────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $ALLOWED_ORIGINS, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function json_out(int $code, string $status, string $message): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// ── Method guard ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, 'error', 'Method not allowed.');
}

// ── Parse body (JSON or form-encoded) ────────────────────────────────────────
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $body = $_POST;
}

// ── Honeypot (bots fill hidden fields; humans leave them empty) ───────────────
if (!empty($body['website'])) {
    // Pretend success so bots don't know they were caught
    json_out(200, 'success', 'Message sent successfully!');
}

// ── Input validation ─────────────────────────────────────────────────────────
$name    = trim($body['name']    ?? '');
$email   = trim($body['email']   ?? '');
$message = trim($body['message'] ?? '');

$errors = [];
if ($name === '')                          $errors[] = 'Name is required.';
if ($name !== '' && strlen($name) > 100)   $errors[] = 'Name is too long.';
if ($email === '')                         $errors[] = 'Email is required.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                           $errors[] = 'Invalid email address.';
}
if ($message === '')                       $errors[] = 'Message is required.';
if ($message !== '' && strlen($message) > 5000) {
                                           $errors[] = 'Message is too long.';
}
if (!empty($errors)) {
    json_out(422, 'error', implode(' ', $errors));
}

// ── Rate limiting (per IP, max 10 requests/hour) ──────────────────────────────
$ip      = preg_replace('/[^a-f0-9:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '');
$rlFile  = RATE_DIR . md5($ip) . '.json';

if (!is_dir(RATE_DIR)) {
    mkdir(RATE_DIR, 0700, true);
}

$now  = time();
$data = is_file($rlFile) ? json_decode(file_get_contents($rlFile), true) : [];
$data = array_filter($data ?? [], fn($t) => $t > $now - 3600); // keep last hour

if (count($data) >= RATE_LIMIT) {
    json_out(429, 'error', 'Too many requests. Please try again later.');
}

$data[] = $now;
file_put_contents($rlFile, json_encode(array_values($data)), LOCK_EX);

// ── Build and send email ──────────────────────────────────────────────────────
$safeName    = preg_replace('/[\r\n]/', '', $name);
$safeEmail   = preg_replace('/[\r\n]/', '', $email);

$subject = '=?UTF-8?B?' . base64_encode('Contact form — ' . SITE_NAME . ' — ' . $safeName) . '?=';

$body_text = implode("\n", [
    'Name:    ' . $safeName,
    'Email:   ' . $safeEmail,
    '',
    'Message:',
    $message,
    '',
    '---',
    'Sent via the contact form on ' . SITE_NAME,
]);

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers .= 'From: ' . SITE_NAME . ' <noreply@konato.be>' . "\r\n";
$headers .= 'Reply-To: ' . $safeName . ' <' . $safeEmail . '>' . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

if (mail(RECIPIENT, $subject, $body_text, $headers)) {
    json_out(200, 'success', 'Message sent successfully!');
} else {
    error_log('[send-mail.php] mail() failed for ' . $safeEmail);
    json_out(500, 'error', 'Could not send the message. Please email us directly at ' . RECIPIENT . '.');
}
