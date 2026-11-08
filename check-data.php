<?php
require_once './src/Database.php';
require_once './src/car-rental-view.php';

header('Content-Type: application/json; charset=utf-8');

$respond = static function ($status, array $payload) {
    http_response_code($status);
    echo json_encode($payload);
    exit();
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(405, ['success' => false, 'message' => 'Use POST with pick-up and return dates.']);
}

$startInput = isset($_POST['start']) && is_string($_POST['start']) ? trim($_POST['start']) : '';
$endInput = isset($_POST['end']) && is_string($_POST['end']) ? trim($_POST['end']) : '';
$start = app_parse_datetime_input($startInput) ?? app_parse_rental_datetime($startInput);
$end = app_parse_datetime_input($endInput) ?? app_parse_rental_datetime($endInput);

if ($start === null || $end === null || $end <= $start) {
    $respond(422, ['success' => false, 'message' => 'Enter valid pick-up and return dates.']);
}

$startSql = $start->format('Y-m-d H:i:s');
$endSql = $end->format('Y-m-d H:i:s');
$statement = $db->prepare(
    "SELECT c.*
     FROM cars c
     WHERE c.status = 'Active'
       AND NOT EXISTS (
           SELECT 1
           FROM booking b
           WHERE b.car = c.id
             AND b.booking_status IN ('Booked', 'Live')
             AND b.start_date < ?
             AND b.end_date > ?
       )
     ORDER BY CAST(c.price_per_day AS DECIMAL(10, 2)) ASC, c.id ASC"
);
$statement->bind_param('ss', $endSql, $startSql);
$statement->execute();
$respond(200, ['success' => true, 'cars' => $statement->get_result()->fetch_all(MYSQLI_ASSOC)]);
