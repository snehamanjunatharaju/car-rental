<?php
require_once './src/Database.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST with a brand identifier.']);
    exit();
}

$brandRaw = isset($_POST['brand']) && is_string($_POST['brand']) ? $_POST['brand'] : '';
if (!ctype_digit($brandRaw) || (int) $brandRaw < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a valid brand.']);
    exit();
}

$brandId = (int) $brandRaw;
$statement = $db->prepare("SELECT * FROM cars WHERE brand = ? AND status = 'Active' ORDER BY car_name, id");
$statement->bind_param('i', $brandId);
$statement->execute();
echo json_encode(['success' => true, 'cars' => $statement->get_result()->fetch_all(MYSQLI_ASSOC)]);
