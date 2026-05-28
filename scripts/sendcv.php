<?php
/**
 * Job application handler — DevRev API
 *
 * Flow
 * ────
 * 1. Validate submitted form fields (first name, last name, e-mail, CV file).
 * 2. Look up / create the applicant in DevRev as a rev-user (by e-mail).
 * 3. Create a DevRev ticket with applicant details and CV attached.
 *
 * Setup
 * ─────
 * Same .env as sendmail.php:
 *   DEVREV_API_KEY=your_token_here
 *   DEVREV_PART_ID=don:core:dvrv-eu-1:devo/xxxx:product/2
 *
 * Deploy to: https://www.konato.be/send-application.php
 */

// ── Logging ────────────────────────────────────────────────────────────────
function log_msg(string $msg): void
{
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg);
}

// ── Load config from .env ──────────────────────────────────────────────────
$env_file = __DIR__ . '/../../.env';
if (!file_exists($env_file)) {
    $env_file = __DIR__ . '/.env';
}

if (!file_exists($env_file)) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'status' => 'error', 'message' => 'Server configuration error.']));
}

$env = [];
foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    $pos = strpos($line, '=');
    if ($pos === false) continue;
    $env[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
}
$api_key = $env['DEVREV_API_KEY'] ?? '';
$part_id = $env['DEVREV_PART_ID'] ?? '';

if (empty($api_key) || empty($part_id)) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'status' => 'error', 'message' => 'Server configuration error.']));
}

// ── Security headers ───────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ── Detect AJAX ────────────────────────────────────────────────────────────
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function respond(bool $ok, string $msg, bool $ajax): void
{
    if ($ajax || true) { // apply form always expects JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $ok,
            'status'  => $ok ? 'success' : 'error',
            'message' => $msg,
        ]);
    } else {
        header('Location: ../contact/?status=' . ($ok ? 'success' : 'error'));
    }
    exit;
}

// ── POST only ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.', $is_ajax);
}

// ── Origin check ───────────────────────────────────────────────────────────
$origin       = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_host = 'at-yourservice.ai';
if ($origin !== '' && strpos($origin, $allowed_host) === false) {
    log_msg('blocked: bad origin "' . $origin . '"');
    http_response_code(403);
    exit(json_encode(['success' => false, 'status' => 'error', 'message' => 'Forbidden.']));
}

// ── Rate limiting — max 5 submissions per IP per hour ─────────────────────
function check_rate_limit(string $ip, int $max = 5, int $window = 3600): bool
{
    $file = sys_get_temp_dir() . '/ays_apply_rl.json';
    $now  = time();

    $fh = @fopen($file, 'c+');
    if (!$fh) return true;

    if (!flock($fh, LOCK_EX)) { fclose($fh); return true; }

    $content = stream_get_contents($fh);
    $data    = ($content !== '' && $content !== false)
               ? (json_decode($content, true) ?? []) : [];

    foreach ($data as $key => $entry) {
        if ($now - $entry['t'] >= $window) unset($data[$key]);
    }

    $entry = $data[$ip] ?? ['c' => 0, 't' => $now];
    if ($now - $entry['t'] >= $window) {
        $entry = ['c' => 0, 't' => $now];
    }
    $entry['c']++;
    $data[$ip] = $entry;
    $allowed   = $entry['c'] <= $max;

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    flock($fh, LOCK_UN);
    fclose($fh);

    return $allowed;
}

$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

if (!check_rate_limit($ip)) {
    log_msg('rate limited: ' . $ip);
    http_response_code(429);
    exit(json_encode(['success' => false, 'status' => 'error', 'message' => 'Te veel pogingen. Probeer het later opnieuw.']));
}

// ── Honeypot ───────────────────────────────────────────────────────────────
if (!empty($_POST['website'])) {
    respond(true, 'Application received.', $is_ajax);
}

// ── Sanitise inputs ────────────────────────────────────────────────────────
$first_name = trim(strip_tags($_POST['first-name']  ?? ''));
$last_name  = trim(strip_tags($_POST['last-name']   ?? ''));
$email      = trim(strip_tags($_POST['email']       ?? ''));
$phone      = trim(strip_tags($_POST['phone']       ?? ''));
$motivation = trim(strip_tags($_POST['motivation']  ?? ''));
$job_title  = trim(strip_tags($_POST['job-title']   ?? ''));
$job_id     = trim(strip_tags($_POST['job-id']      ?? ''));
$gdpr       = !empty($_POST['gdpr']);

// ── Validate ───────────────────────────────────────────────────────────────
$errors = [];

if ($first_name === '' || strlen($first_name) < 2 || strlen($first_name) > 100) {
    $errors[] = 'Ongeldige voornaam.';
}
if ($last_name === '' || strlen($last_name) < 2 || strlen($last_name) > 100) {
    $errors[] = 'Ongeldige achternaam.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ongeldig e-mailadres.';
}
if (!$gdpr) {
    $errors[] = 'Gegevensverwerking niet geaccepteerd.';
}

// ── CV file validation ─────────────────────────────────────────────────────
$cv_file = $_FILES['cv'] ?? null;

if (!$cv_file || $cv_file['error'] !== UPLOAD_ERR_OK || $cv_file['size'] === 0) {
    $errors[] = 'CV ontbreekt of kon niet worden geüpload.';
} else {
    $allowed_mime  = ['application/pdf', 'application/msword',
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                      'image/jpeg', 'image/png'];
    $allowed_ext   = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_bytes     = 5 * 1024 * 1024; // 5 MB

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime     = $finfo->file($cv_file['tmp_name']);
    $ext      = strtolower(pathinfo($cv_file['name'], PATHINFO_EXTENSION));

    if (!in_array($mime, $allowed_mime, true) || !in_array($ext, $allowed_ext, true)) {
        $errors[] = 'Ongeldig bestandstype. Gebruik PDF, Word of een afbeelding.';
    } elseif ($cv_file['size'] > $max_bytes) {
        $errors[] = 'CV is te groot (max. 5 MB).';
    }
}

if (!empty($errors)) {
    respond(false, implode(' ', $errors), $is_ajax);
}

// ── Helper: DevRev API request ─────────────────────────────────────────────
function devrev_request(string $method, string $url, array $payload, string $api_key): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body       = curl_exec($ch);
    $status     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'status'     => $status,
        'body'       => $body ? json_decode($body, true) : null,
        'curl_error' => $curl_error,
    ];
}

// ── Step 1: Look up rev-user by e-mail ─────────────────────────────────────
$full_name   = $first_name . ' ' . $last_name;
$rev_user_id = null;

$lookup = devrev_request(
    'GET',
    'https://api.devrev.ai/rev-users.list?email=' . urlencode($email),
    [],
    $api_key
);

if ($lookup['curl_error']) {
    log_msg('rev-users.list cURL error: ' . $lookup['curl_error']);
} elseif ($lookup['status'] === 200) {
    $rev_users   = $lookup['body']['rev_users'] ?? [];
    $rev_user_id = !empty($rev_users) ? ($rev_users[0]['id'] ?? null) : null;
} else {
    log_msg('rev-users.list unexpected HTTP ' . $lookup['status'] . ': ' . json_encode($lookup['body']));
}

// ── Step 1b: Create rev-user if not found ──────────────────────────────────
if ($rev_user_id === null) {
    $create = devrev_request(
        'POST',
        'https://api.devrev.ai/rev-users.create',
        [
            'email'        => $email,
            'display_name' => $full_name,
        ],
        $api_key
    );

    if (!$create['curl_error'] && $create['status'] >= 200 && $create['status'] < 300) {
        $rev_user_id = $create['body']['rev_user']['id'] ?? null;
        log_msg('rev-user created: ' . $rev_user_id . ' for ' . $email);
    } else {
        log_msg('rev-users.create failed — HTTP ' . $create['status'] . ': ' . json_encode($create['body']) . ' | cURL: ' . $create['curl_error']);
    }
}

// ── Step 2: Build ticket body ──────────────────────────────────────────────
$body_lines = [
    'Applicant: ' . $full_name,
    'Email:     ' . $email,
];
if ($phone !== '') {
    $body_lines[] = 'Phone:     ' . $phone;
}
if ($job_title !== '') {
    $body_lines[] = 'Job:       ' . $job_title . ($job_id !== '' ? ' (' . $job_id . ')' : '');
}
if ($motivation !== '') {
    $body_lines[] = '';
    $body_lines[] = 'Motivation:';
    $body_lines[] = $motivation;
}
$body_lines[] = '';
$body_lines[] = 'CV attached: ' . $cv_file['name'] . ' (' . round($cv_file['size'] / 1024) . ' KB)';

$ticket_title = 'Job application'
    . ($job_title !== '' ? ' — ' . $job_title : '')
    . ' from ' . $full_name;

// ── Step 3: Create DevRev ticket ───────────────────────────────────────────
$payload = [
    'type'            => 'ticket',
    'title'           => $ticket_title,
    'body'            => implode("\n", $body_lines),
    'applies_to_part' => $part_id,
];

if ($rev_user_id !== null) {
    $payload['reported_by'] = [$rev_user_id];
}

$ticket = devrev_request(
    'POST',
    'https://api.devrev.ai/works.create',
    $payload,
    $api_key
);

$ticket_ok = !$ticket['curl_error']
    && $ticket['status'] >= 200
    && $ticket['status'] < 300;

if (!$ticket_ok) {
    log_msg('[sendcv] works.create failed — HTTP ' . $ticket['status'] . ': ' . json_encode($ticket['body']) . ' | cURL: ' . $ticket['curl_error']);
    respond(false, 'Verzenden mislukt. Probeer het opnieuw of stuur je CV rechtstreeks naar info@konato.be.', $is_ajax);
}

// ── Step 4: Upload CV as artifact ──────────────────────────────────────────
// Prepare a multipart upload to DevRev artifacts API
$ticket_id = $ticket['body']['work']['id'] ?? null;

$artifact_ch = curl_init('https://api.devrev.ai/artifacts.prepare');
curl_setopt_array($artifact_ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'file_name' => $cv_file['name'],
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
]);

$artifact_body  = curl_exec($artifact_ch);
$artifact_status = curl_getinfo($artifact_ch, CURLINFO_HTTP_CODE);
$artifact_error  = curl_error($artifact_ch);
curl_close($artifact_ch);

if ($artifact_error || $artifact_status < 200 || $artifact_status >= 300) {
    log_msg('[sendcv] artifacts.prepare failed — HTTP ' . $artifact_status . ': ' . $artifact_body . ' | cURL: ' . $artifact_error);
    // Ticket was created — still respond success, just log the upload failure
    respond(true, 'Uw sollicitatie is succesvol verzonden.', $is_ajax);
}

$artifact_data = json_decode($artifact_body, true);
$upload_url    = $artifact_data['url']         ?? null;
$artifact_id   = $artifact_data['artifact']['id'] ?? null;
$form_fields   = $artifact_data['form_data']   ?? [];

if (!$upload_url) {
    log_msg('[sendcv] artifacts.prepare: no upload URL in response');
    respond(true, 'Uw sollicitatie is succesvol verzonden.', $is_ajax);
}

// PUT the file to the pre-signed URL
$cv_contents = file_get_contents($cv_file['tmp_name']);

$headers = [];
foreach ($form_fields as $field) {
    $headers[] = $field['key'] . ': ' . $field['value'];
}
$headers[] = 'Content-Type: ' . $cv_file['type'];

$put_ch = curl_init($upload_url);
curl_setopt_array($put_ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_POSTFIELDS     => $cv_contents,
    CURLOPT_HTTPHEADER     => $headers,
]);
$put_status = curl_getinfo($put_ch, CURLINFO_HTTP_CODE);
$put_error  = curl_error($put_ch);
curl_exec($put_ch);
curl_close($put_ch);

if ($put_error || ($put_status !== 0 && ($put_status < 200 || $put_status >= 300))) {
    log_msg('[sendcv] CV upload PUT failed — HTTP ' . $put_status . ' | cURL: ' . $put_error);
}

// ── Step 5: Link artifact to ticket ───────────────────────────────────────
if ($artifact_id && $ticket_id) {
    $link = devrev_request(
        'POST',
        'https://api.devrev.ai/artifact-attachments.create',
        [
            'artifact' => $artifact_id,
            'parent'   => $ticket_id,
        ],
        $api_key
    );

    if ($link['curl_error'] || $link['status'] < 200 || $link['status'] >= 300) {
        log_msg('[sendcv] artifact-attachments.create failed — HTTP ' . $link['status'] . ': ' . json_encode($link['body']));
    } else {
        log_msg('[sendcv] CV artifact ' . $artifact_id . ' linked to ticket ' . $ticket_id);
    }
}

respond(true, 'Uw sollicitatie is succesvol verzonden.', $is_ajax);
