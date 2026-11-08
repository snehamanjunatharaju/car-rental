<?php

require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST to save the pickup address.']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The pickup address request is invalid.']);
    exit;
}

$submittedToken = isset($payload['csrf_token']) && is_string($payload['csrf_token'])
    ? $payload['csrf_token']
    : '';
$sessionToken = isset($_SESSION['pickup_address_csrf_token']) && is_string($_SESSION['pickup_address_csrf_token'])
    ? $_SESSION['pickup_address_csrf_token']
    : '';

if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Reload the page before saving the pickup address.']);
    exit;
}

$address = isset($payload['address']) && is_string($payload['address'])
    ? trim($payload['address'])
    : null;
$city = isset($payload['city']) && is_string($payload['city'])
    ? app_supported_rental_city($payload['city'])
    : null;

if ($address === null || strlen($address) > 1000 || $city === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a supported city and enter a pickup address of 250 characters or fewer.']);
    exit;
}

$address = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $address);
if (!is_string($address)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'The pickup address contains unsupported characters.']);
    exit;
}
$address = trim(preg_replace('/\s+/', ' ', $address));

if ($address === '') {
    unset($_SESSION['pickup_address'], $_SESSION['pickup_city']);
} else {
    $_SESSION['pickup_address'] = $address;
    $_SESSION['pickup_city'] = $city;
}

echo json_encode(['success' => true]);
