<?php
/**
 * send-application.php — job application handler for konato.be
 *
 * Deploy this file to the WordPress server root (www.konato.be/send-application.php).
 * The static site's apply-form.js POSTs multipart/form-data to it cross-origin.
 *
 * Fields expected:
 *   first-name   (string, required)
 *   last-name    (string, required)
 *   email        (email,  required)
 *   phone        (string, optional)
 *   job-title    (string, optional — populated from URL param)
 *   motivation   (string, optional)
 *   cv           (file,   required — PDF / Word / JPG / PNG, max 5 MB)
 *   website      (honeypot — must be empty)
 *
 * Requirements: PHP 7.4+, mail() enabled, file uploads enabled.
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('RECIPIENT',     'info@9yards.be');
define('SITE_NAME',     'Konato');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);   // 5 MB
define('RATE_LIMIT',    5);                  // max applications per IP per hour
define('RATE_DIR',      sys_get_temp_dir() . '/konato_apply_rl/');

$ALLOWED_ORIGINS = [
    'https://at-yourservice.github.io',
    'https://www.konato.be',
    'http://localhost:8080',
    'http://localhost:8001',
];

$ALLOWED_MIME = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
];
// ──────────────────────────────────────────────────────────────────────────────

// ── CORS ──────────────────────────────────────────────────────────────────────
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

// ── Helpers ───────────────────────────────────────────────────────────────────
function json_out(int $code, string $status, string $message): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// ── Method guard ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, 'error', 'Method not allowed.');
}

// ── Honeypot ──────────────────────────────────────────────────────────────────
if (!empty($_POST['website'])) {
    json_out(200, 'success', 'Application received!');
}

// ── Input validation ──────────────────────────────────────────────────────────
$firstName  = trim($_POST['first-name']  ?? '');
$lastName   = trim($_POST['last-name']   ?? '');
$email      = trim($_POST['email']       ?? '');
$phone      = trim($_POST['phone']       ?? '');
$jobTitle   = trim($_POST['job-title']   ?? '');
$motivation = trim($_POST['motivation']  ?? '');

$errors = [];
if ($firstName === '')                              $errors[] = 'First name is required.';
if ($firstName !== '' && strlen($firstName) > 100) $errors[] = 'First name is too long.';
if ($lastName === '')                               $errors[] = 'Last name is required.';
if ($lastName !== '' && strlen($lastName) > 100)   $errors[] = 'Last name is too long.';
if ($email === '')                                  $errors[] = 'Email is required.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                    $errors[] = 'Invalid email address.';
}
if (strlen($motivation) > 5000)                    $errors[] = 'Motivation text is too long.';

// ── CV validation ─────────────────────────────────────────────────────────────
$hasCv = isset($_FILES['cv']) && $_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE;

if (!$hasCv) {
    $errors[] = 'Please attach your CV.';
} elseif ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'File upload failed (code ' . $_FILES['cv']['error'] . '). Please try again.';
} else {
    if ($_FILES['cv']['size'] > MAX_FILE_SIZE) {
        $errors[] = 'CV file is too large (max 5 MB).';
    }
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $cvMime  = $finfo->file($_FILES['cv']['tmp_name']);
    if (!in_array($cvMime, $ALLOWED_MIME, true)) {
        $errors[] = 'Invalid file type. Please upload a PDF, Word document (.doc/.docx), JPG or PNG.';
    }
}

if (!empty($errors)) {
    json_out(422, 'error', implode(' ', $errors));
}

// ── Rate limiting (per IP, max 5 per hour) ────────────────────────────────────
$ip     = preg_replace('/[^a-f0-9:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '');
$rlFile = RATE_DIR . md5($ip) . '.json';

if (!is_dir(RATE_DIR)) {
    mkdir(RATE_DIR, 0700, true);
}

$now  = time();
$data = is_file($rlFile) ? json_decode(file_get_contents($rlFile), true) : [];
$data = array_filter($data ?? [], fn($t) => $t > $now - 3600);

if (count($data) >= RATE_LIMIT) {
    json_out(429, 'error', 'Too many requests. Please try again later.');
}

$data[] = $now;
file_put_contents($rlFile, json_encode(array_values($data)), LOCK_EX);

// ── Build multipart email with CV attachment ──────────────────────────────────
$safeName  = preg_replace('/[\r\n]/', '', $firstName . ' ' . $lastName);
$safeEmail = preg_replace('/[\r\n]/', '', $email);
$safeJob   = preg_replace('/[\r\n]/', '', $jobTitle ?: 'Unknown position');

$subject = '=?UTF-8?B?' . base64_encode('Job application — ' . SITE_NAME . ' — ' . $safeJob . ' — ' . $safeName) . '?=';

$bodyText = implode("\n", [
    'Application for: ' . $safeJob,
    '',
    'Name:       ' . $safeName,
    'Email:      ' . $safeEmail,
    'Phone:      ' . ($phone ?: '—'),
    '',
    'Motivation:',
    ($motivation ?: '(none)'),
    '',
    '---',
    'Sent via the application form on ' . SITE_NAME,
]);

$boundary = '----=_Part_' . bin2hex(random_bytes(8));
$cvData   = base64_encode(file_get_contents($_FILES['cv']['tmp_name']));
$cvName   = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['cv']['name']);

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n";
$headers .= 'From: ' . SITE_NAME . ' <noreply@konato.be>' . "\r\n";
$headers .= 'Reply-To: ' . $safeName . ' <' . $safeEmail . '>' . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

$body  = '--' . $boundary . "\r\n";
$body .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$body .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
$body .= $bodyText . "\r\n\r\n";
$body .= '--' . $boundary . "\r\n";
$body .= 'Content-Type: ' . $cvMime . '; name="' . $cvName . '"' . "\r\n";
$body .= 'Content-Transfer-Encoding: base64' . "\r\n";
$body .= 'Content-Disposition: attachment; filename="' . $cvName . '"' . "\r\n\r\n";
$body .= chunk_split($cvData) . "\r\n";
$body .= '--' . $boundary . '--';

if (mail(RECIPIENT, $subject, $body, $headers)) {
    json_out(200, 'success', 'Application received! We will contact you soon.');
} else {
    error_log('[send-application.php] mail() failed for ' . $safeEmail);
    json_out(500, 'error', 'Could not send your application. Please email us directly at ' . RECIPIENT . '.');
}
