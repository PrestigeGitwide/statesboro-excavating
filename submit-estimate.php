<?php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

require_once __DIR__ . '/config.php';

if (
    TURNSTILE_SECRET_KEY === 'PASTE_YOUR_TURNSTILE_SECRET_KEY_HERE' ||
    GOHIGHLEVEL_WEBHOOK_URL === 'PASTE_YOUR_GOHIGHLEVEL_WEBHOOK_URL_HERE'
) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Server configuration is incomplete. Add your Turnstile secret key and GoHighLevel webhook URL in config.php.'
    ]);
    exit;
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function postJson(string $url, array $payload): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'body' => $body,
        'error' => $error,
        'status' => $status,
    ];
}

function postForm(string $url, array $payload): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'body' => $body,
        'error' => $error,
        'status' => $status,
    ];
}

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$serviceType = trim($_POST['service_type'] ?? 'Free Estimate Request');
$pageUrl = trim($_POST['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
$turnstileToken = trim($_POST['cf-turnstile-response'] ?? '');
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

if ($name === '' || $address === '' || $phone === '' || $email === '' || $message === '') {
    respond(422, [
        'ok' => false,
        'message' => 'Please complete all required fields.'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        'ok' => false,
        'message' => 'Please enter a valid email address.'
    ]);
}

if ($turnstileToken === '') {
    respond(422, [
        'ok' => false,
        'message' => 'Please complete the spam protection check.'
    ]);
}

$turnstileResponse = postForm(
    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $turnstileToken,
        'remoteip' => $remoteIp,
    ]
);

if ($turnstileResponse['error']) {
    error_log('Statesboro Excavating form Turnstile request failed: ' . $turnstileResponse['error']);

    respond(502, [
        'ok' => false,
        'message' => 'Unable to verify spam protection right now. Please try again.'
    ]);
}

$turnstileJson = json_decode($turnstileResponse['body'] ?? '', true);

if (!is_array($turnstileJson) || empty($turnstileJson['success'])) {
    error_log('Statesboro Excavating form Turnstile verification failed: HTTP ' . $turnstileResponse['status'] . ' body ' . substr((string) $turnstileResponse['body'], 0, 500));

    respond(422, [
        'ok' => false,
        'message' => 'Spam protection verification failed. Please try again.'
    ]);
}

$nameParts = preg_split('/\s+/', $name);
$firstName = $nameParts[0] ?? $name;
$lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

$leadPayload = [
    'name' => $name,
    'fullName' => $name,
    'first_name' => $firstName,
    'firstName' => $firstName,
    'last_name' => $lastName,
    'lastName' => $lastName,
    'address' => $address,
    'address1' => $address,
    'phone' => $phone,
    'email' => $email,
    'message' => $message,
    'comments' => $message,
    'service_type' => $serviceType,
    'serviceType' => $serviceType,
    'source' => 'Statesboro Excavating Website',
    'page' => $pageUrl,
    'page_url' => $pageUrl,
    'pageUrl' => $pageUrl,
    'customData' => [
        'service_type' => $serviceType,
        'serviceType' => $serviceType,
        'address' => $address,
        'address1' => $address,
        'message' => $message,
        'comments' => $message,
        'page_url' => $pageUrl,
        'pageUrl' => $pageUrl,
    ],
];

$webhookResponse = postJson(GOHIGHLEVEL_WEBHOOK_URL, $leadPayload);

if ($webhookResponse['error'] || $webhookResponse['status'] >= 400) {
    error_log('Statesboro Excavating form GHL webhook failed: HTTP ' . $webhookResponse['status'] . ' error ' . $webhookResponse['error'] . ' body ' . substr((string) $webhookResponse['body'], 0, 500));

    respond(502, [
        'ok' => false,
        'message' => 'Your request could not be sent right now. Please call 555-555-5555.'
    ]);
}

error_log('Statesboro Excavating form GHL webhook delivered: HTTP ' . $webhookResponse['status'] . ' body ' . substr((string) $webhookResponse['body'], 0, 500));

respond(200, [
    'ok' => true,
    'message' => 'Thanks. Your request has been sent successfully.'
]);
