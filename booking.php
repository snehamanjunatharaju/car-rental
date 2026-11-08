<?php
require_once './src/car-rental-view.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./cars.php');
    exit();
}

$carId = isset($_POST['car_id']) && is_string($_POST['car_id'])
    ? $_POST['car_id']
    : '';
$startInput = isset($_POST['start_date']) && is_string($_POST['start_date'])
    ? trim($_POST['start_date'])
    : '';
$endInput = isset($_POST['end_date']) && is_string($_POST['end_date'])
    ? trim($_POST['end_date'])
    : '';

$parseLegacyDate = function ($value) {
    $date = app_parse_datetime_input($value);
    if ($date !== null) {
        return $date;
    }

    $legacyDate = DateTimeImmutable::createFromFormat('!d-m-Y h:i A', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $legacyDate === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $legacyDate->format('d-m-Y h:i A') !== $value
    ) {
        return null;
    }

    return $legacyDate;
};

$start = $parseLegacyDate($startInput);
$end = $parseLegacyDate($endInput);

if (!ctype_digit($carId) || (int) $carId < 1 || $start === null || $end === null || $end <= $start) {
    http_response_code(400);
    header('Location: ./cars.php');
    exit();
}

header('Location: ./car-details.php?' . http_build_query([
    'id' => (int) $carId,
    'start' => $start->format('Y-m-d\TH:i'),
    'end' => $end->format('Y-m-d\TH:i'),
]));
exit();
