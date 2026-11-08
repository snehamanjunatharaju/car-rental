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
    $respond(405, ['msg' => 'Use POST to add a brand.']);
}
if (!isset($_SESSION['user'], $_SESSION['name'])) {
    $respond(401, ['msg' => 'Sign in as an administrator to add a brand.']);
}
$submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$sessionToken = isset($_SESSION['admin_brand_csrf_token']) && is_string($_SESSION['admin_brand_csrf_token'])
    ? $_SESSION['admin_brand_csrf_token']
    : '';
if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $respond(403, ['msg' => 'Reload the form before adding a brand.']);
}

$isError = false;
$errors = [
    "name" => "",
    "brandImage" => "",
];

$brandName = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
if ($brandName === '' || strlen($brandName) > 100) {
    $isError = true;
    $errors['name'] = 'Enter a brand name of 1 to 100 characters.';
}



# File validation

//photo validation

$allowedImages = [
    'png' => 'image/png',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
];
$maxAllowedSize = 1024 * 500; // 500KB

$photo = isset($_FILES['photo']) && is_array($_FILES['photo']) ? $_FILES['photo'] : [];
$photoError = isset($photo['error']) && is_int($photo['error']) ? $photo['error'] : UPLOAD_ERR_NO_FILE;
if ($photoError !== UPLOAD_ERR_OK) {
    $isError = true;
    $errors['brandImage'] = 'Choose a brand image and make sure it uploads successfully.';
} else {
    $extension = strtolower(pathinfo((string) ($photo['name'] ?? ''), PATHINFO_EXTENSION));
    $temporaryPath = isset($photo['tmp_name']) && is_string($photo['tmp_name'])
        ? $photo['tmp_name']
        : '';
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $temporaryPath !== '' && is_uploaded_file($temporaryPath)
        ? $fileInfo->file($temporaryPath)
        : false;
    if (
        !isset($allowedImages[$extension])
        || $mimeType !== $allowedImages[$extension]
        || @getimagesize($temporaryPath) === false
    ) {
        $isError = true;
        $errors['brandImage'] = 'Invalid file format for photo (png, jpg, jpeg only)';
    }

    if (!isset($photo['size']) || !is_int($photo['size']) || $photo['size'] > $maxAllowedSize) {
        $isError = true;
        $errors['brandImage'] = 'Image must be 500 KB or smaller.';
    }
}

if ($isError) {
    http_response_code(400);
    $respond(422, ['msg' => 'Correct the following fields.', 'errors' => $errors]);
}

$name = $brandName;
$fileName = bin2hex(random_bytes(16));
$ext = strtolower(pathinfo((string) $photo['name'], PATHINFO_EXTENSION));
$uploadDirectory = __DIR__ . '/../admin/uploaded-files/brands';
$fullFilePath = $uploadDirectory . '/' . $fileName . '.' . $ext;
$storedFilePath = '../admin/uploaded-files/brands/' . $fileName . '.' . $ext;
$transactionStarted = false;

try {
    if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
        throw new RuntimeException('Brand image upload directory is unavailable.');
    }

    $db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    $transactionStarted = true;
    if (!isset($photo['tmp_name']) || !is_string($photo['tmp_name'])
        || !move_uploaded_file($photo['tmp_name'], $fullFilePath)) {
        throw new Exception('Failed to move uploaded file');
    }

    $insert = $db->prepare('INSERT INTO brands (brand_name, image) VALUES (?, ?)');
    $insert->bind_param('ss', $name, $storedFilePath);
    $insert->execute();
    $db->commit();
    $transactionStarted = false;
    $respond(200, ['msg' => 'Your request is submitted.']);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $db->rollback();
    }
    if (is_file($fullFilePath)) {
        unlink($fullFilePath);
    }
    error_log('Brand creation failed: ' . $exception->getMessage());
    $respond(500, ['msg' => 'Could not add the brand. Please try again.']);
}