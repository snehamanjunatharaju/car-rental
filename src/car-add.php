<?php
require_once './Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

$respond = static function ($status, array $payload) {
    http_response_code($status);
    echo json_encode($payload);
    exit();
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(405, ['msg' => 'Use POST to add a car.']);
}
if (!isset($_SESSION['user'], $_SESSION['name'])) {
    $respond(401, ['msg' => 'Sign in as an administrator to add a car.']);
}
$submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$sessionToken = isset($_SESSION['admin_car_csrf_token']) && is_string($_SESSION['admin_car_csrf_token'])
    ? $_SESSION['admin_car_csrf_token']
    : '';
if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $respond(403, ['msg' => 'Reload the form before adding a car.']);
}

$errors = [
    'carName' => '',
    'brandName' => '',
    'carBodyType' => '',
    'transmission' => '',
    'fuel_type' => '',
    'model' => '',
    'seating_capacity' => '',
    'km_driven' => '',
    'price_per_day' => '',
    'reg_no' => '',
    'image' => '',
];
$fields = [];
foreach ([
    'car_name',
    'brand',
    'car_body_type',
    'transmission',
    'fuel_type',
    'model',
    'seating_capacity',
    'km_driven',
    'price_per_day',
    'reg_no',
    'description',
] as $field) {
    $fields[$field] = isset($_POST[$field]) && is_string($_POST[$field])
        ? trim($_POST[$field])
        : '';
}

if ($fields['car_name'] === '' || strlen($fields['car_name']) > 150) {
    $errors['carName'] = 'Enter a car name of 1 to 150 characters.';
}
if (!ctype_digit($fields['brand']) || (int) $fields['brand'] < 1) {
    $errors['brandName'] = 'Choose a valid brand.';
} else {
    $brandId = (int) $fields['brand'];
    $brandCheck = $db->prepare('SELECT id FROM brands WHERE id = ? LIMIT 1');
    $brandCheck->bind_param('i', $brandId);
    $brandCheck->execute();
    if (!$brandCheck->get_result()->fetch_assoc()) {
        $errors['brandName'] = 'Choose a brand that exists.';
    }
}
if ($fields['car_body_type'] === '' || strlen($fields['car_body_type']) > 50) {
    $errors['carBodyType'] = 'Enter a vehicle style of 1 to 50 characters.';
}
if ($fields['transmission'] === '' || strlen($fields['transmission']) > 50) {
    $errors['transmission'] = 'Enter a transmission of 1 to 50 characters.';
}
if (!in_array($fields['fuel_type'], ['Petrol', 'Diesel', 'CNG'], true)) {
    $errors['fuel_type'] = 'Choose a supported fuel type.';
}
if ($fields['model'] === '' || strlen($fields['model']) > 20) {
    $errors['model'] = 'Enter a model of 1 to 20 characters.';
}
if (!ctype_digit($fields['seating_capacity']) || (int) $fields['seating_capacity'] < 1 || (int) $fields['seating_capacity'] > 99) {
    $errors['seating_capacity'] = 'Enter a valid seating capacity.';
}
if (!ctype_digit($fields['km_driven']) || strlen($fields['km_driven']) > 20) {
    $errors['km_driven'] = 'Enter the vehicle mileage as a whole number.';
}
if (
    !preg_match('/^\d+(?:\.\d{1,2})?$/', $fields['price_per_day'])
    || (float) $fields['price_per_day'] < 1
    || (float) $fields['price_per_day'] > 100000
) {
    $errors['price_per_day'] = 'Enter a daily estimate between ₹1 and ₹100,000.';
}
if ($fields['reg_no'] === '' || strlen($fields['reg_no']) > 20) {
    $errors['reg_no'] = 'Enter a registration number of 1 to 20 characters.';
}

$featureValues = [];
foreach (['ac', 'sun_roof', 'air_bags', 'central_lock'] as $feature) {
    $value = isset($_POST[$feature]) && is_string($_POST[$feature]) ? $_POST[$feature] : '0';
    if (!in_array($value, ['0', '1'], true)) {
        $value = '0';
    }
    $featureValues[$feature] = (int) $value;
}

$imageFiles = isset($_FILES['images']) && is_array($_FILES['images']) ? $_FILES['images'] : [];
$imageErrors = isset($imageFiles['error']) && is_array($imageFiles['error'])
    ? $imageFiles['error']
    : [];
if (count($imageErrors) !== 3) {
    $errors['image'] = 'Select exactly 3 car images.';
} else {
    $allowedImages = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($imageErrors as $index => $uploadError) {
        $fileName = isset($imageFiles['name'][$index]) && is_string($imageFiles['name'][$index])
            ? $imageFiles['name'][$index]
            : '';
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $temporaryPath = isset($imageFiles['tmp_name'][$index]) && is_string($imageFiles['tmp_name'][$index])
            ? $imageFiles['tmp_name'][$index]
            : '';
        $fileSize = isset($imageFiles['size'][$index]) && is_int($imageFiles['size'][$index])
            ? $imageFiles['size'][$index]
            : 0;
        $mimeType = $temporaryPath !== '' && is_uploaded_file($temporaryPath)
            ? $fileInfo->file($temporaryPath)
            : false;

        if (
            $uploadError !== UPLOAD_ERR_OK
            || !isset($allowedImages[$extension])
            || $mimeType !== $allowedImages[$extension]
            || $fileSize < 1
            || $fileSize > 512000
            || @getimagesize($temporaryPath) === false
        ) {
            $errors['image'] = 'Upload exactly 3 valid JPG or PNG images, each 500 KB or smaller.';
            break;
        }
    }
}

foreach ($errors as $error) {
    if ($error !== '') {
        $respond(422, ['msg' => 'Correct the following fields.', 'errors' => $errors]);
    }
}

$pricePerDay = number_format((float) $fields['price_per_day'], 2, '.', '');
$imageDirectory = __DIR__ . '/../admin/uploaded-files/cars';
$imagePaths = [];
$transactionStarted = false;

try {
    if (!is_dir($imageDirectory) || !is_writable($imageDirectory)) {
        throw new RuntimeException('Car image upload directory is unavailable.');
    }

    foreach ($imageErrors as $index => $_uploadError) {
        $extension = strtolower(pathinfo((string) $imageFiles['name'][$index], PATHINFO_EXTENSION));
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $imageDirectory . '/' . $fileName;
        if (!move_uploaded_file($imageFiles['tmp_name'][$index], $absolutePath)) {
            throw new RuntimeException('Could not store an uploaded car image.');
        }
        $imagePaths[] = '../admin/uploaded-files/cars/' . $fileName;
    }

    $db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    $transactionStarted = true;
    $insert = $db->prepare(
        'INSERT INTO cars
         (car_name, brand, car_body_type, transmission, fuel_type, model, seating_capacity,
          km_driven, price_per_day, reg_no, ac, sun_roof, air_bags, central_lock,
          description, image1, image2, image3)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->bind_param(
        'sissssssssiiiissss',
        $fields['car_name'],
        $brandId,
        $fields['car_body_type'],
        $fields['transmission'],
        $fields['fuel_type'],
        $fields['model'],
        $fields['seating_capacity'],
        $fields['km_driven'],
        $pricePerDay,
        $fields['reg_no'],
        $featureValues['ac'],
        $featureValues['sun_roof'],
        $featureValues['air_bags'],
        $featureValues['central_lock'],
        $fields['description'],
        $imagePaths[0],
        $imagePaths[1],
        $imagePaths[2]
    );
    $insert->execute();
    $db->commit();
    $transactionStarted = false;
    $respond(200, ['msg' => 'Car added successfully.']);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $db->rollback();
    }
    foreach ($imagePaths as $imagePath) {
        $absolutePath = $imageDirectory . '/' . basename($imagePath);
        if (is_file($absolutePath) && !unlink($absolutePath)) {
            error_log('Could not remove car image after failed creation: ' . $absolutePath);
        }
    }
    error_log('Car creation failed: ' . $exception->getMessage());
    $respond(500, ['msg' => 'Could not add the car. Please try again.']);
}
