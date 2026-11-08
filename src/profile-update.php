<?php

require_once __DIR__ . '/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function profile_update_response($status, array $payload)
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    profile_update_response(405, ['msg' => 'Use POST to update your profile.']);
}

$user = isset($_SESSION['user']) && is_object($_SESSION['user'])
    ? $_SESSION['user']
    : null;
$customerId = isset($_SESSION['isLogged'], $user->id) && $_SESSION['isLogged'] === true
    ? (int) $user->id
    : 0;

if ($customerId < 1) {
    profile_update_response(401, ['msg' => 'Sign in again before updating your profile.']);
}

$submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$sessionToken = isset($_SESSION['profile_csrf_token']) && is_string($_SESSION['profile_csrf_token'])
    ? $_SESSION['profile_csrf_token']
    : '';
if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    profile_update_response(403, ['msg' => 'Reload the page before updating your profile.']);
}

$statement = $db->prepare(
    'SELECT driving_license_image1, driving_license_image2, address_proof_image
     FROM customers
     WHERE id = ?'
);
$statement->bind_param('i', $customerId);
$statement->execute();
$existing = $statement->get_result()->fetch_assoc();

if (!$existing) {
    profile_update_response(404, ['msg' => 'Your customer profile could not be found.']);
}

$fieldNames = [
    'name' => 'name',
    'email' => 'email',
    'phone' => 'phone',
    'address' => 'address',
    'driving_license_no' => 'driving-license-no',
    'address_proof_no' => 'address-proof-no',
];
$values = [];
$errors = [];
foreach ($fieldNames as $errorKey => $postKey) {
    $value = isset($_POST[$postKey]) && is_string($_POST[$postKey])
        ? trim($_POST[$postKey])
        : '';
    $values[$errorKey] = $value;
    if ($value === '') {
        $errors[$errorKey] = 'This field is required.';
    }
}

if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}
if ($values['phone'] !== '' && !preg_match('/^\+?[0-9\s()-]{8,20}$/', $values['phone'])) {
    $errors['phone'] = 'Enter a valid phone number.';
}
$fieldLimits = [
    'name' => 150,
    'email' => 50,
    'phone' => 20,
    'driving_license_no' => 50,
    'address_proof_no' => 50,
];
foreach ($fieldLimits as $field => $limit) {
    if (strlen($values[$field]) > $limit) {
        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be ' . $limit . ' characters or fewer.';
    }
}
if (!isset($errors['email'])) {
    $emailCheck = $db->prepare('SELECT id FROM customers WHERE email = ? AND id <> ? LIMIT 1');
    $emailCheck->bind_param('si', $values['email'], $customerId);
    $emailCheck->execute();
    if ($emailCheck->get_result()->fetch_assoc()) {
        $errors['email'] = 'This email address is already linked to another account.';
    }
}

$uploadSpecs = [
    'driving-license-image1' => [
        'error_key' => 'driving_license_image1',
        'folder' => 'DL',
        'column' => 'driving_license_image1',
        'label' => 'Driving licence front image',
    ],
    'driving-license-image2' => [
        'error_key' => 'driving_license_image2',
        'folder' => 'DL',
        'column' => 'driving_license_image2',
        'label' => 'Driving licence back image',
    ],
    'address-proof-image' => [
        'error_key' => 'address_proof_image',
        'folder' => 'address-proof',
        'column' => 'address_proof_image',
        'label' => 'Address proof image',
    ],
];
$uploads = [];
foreach ($uploadSpecs as $inputName => $spec) {
    $file = isset($_FILES[$inputName]) && is_array($_FILES[$inputName])
        ? $_FILES[$inputName]
        : null;
    $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        if (empty($existing[$spec['column']])) {
            $errors[$spec['error_key']] = 'Please select ' . strtolower($spec['label']) . '.';
        }
        continue;
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        $errors[$spec['error_key']] = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
            ? $spec['label'] . ' exceeds the upload limit.'
            : 'Could not receive ' . strtolower($spec['label']) . '. Please select it again.';
        continue;
    }

    $temporaryPath = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
    $fileSize = isset($file['size']) ? (int) $file['size'] : 0;
    if ($fileSize < 1 || $fileSize > 300 * 1024 || !is_uploaded_file($temporaryPath)) {
        $errors[$spec['error_key']] = $fileSize > 300 * 1024
            ? $spec['label'] . ' must be 300 KB or smaller.'
            : 'The selected ' . strtolower($spec['label']) . ' is not valid.';
        continue;
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    $extensionByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($extensionByMime[$mimeType]) || @getimagesize($temporaryPath) === false) {
        $errors[$spec['error_key']] = $spec['label'] . ' must be a valid JPG or PNG image.';
        continue;
    }

    $folder = __DIR__ . '/../admin/uploaded-files/customer-doc/' . $spec['folder'];
    if (!is_dir($folder) || !is_writable($folder)) {
        error_log('Profile upload directory is missing or not writable: ' . $folder);
        $errors[$spec['error_key']] = 'Document upload is temporarily unavailable. Please contact support.';
        continue;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensionByMime[$mimeType];
    $uploads[$inputName] = [
        'destination' => $folder . '/' . $filename,
        'stored_path' => '../admin/uploaded-files/customer-doc/' . $spec['folder'] . '/' . $filename,
        'column' => $spec['column'],
        'error_key' => $spec['error_key'],
        'temporary_path' => $temporaryPath,
        'label' => $spec['label'],
    ];
}

if ($errors) {
    profile_update_response(422, [
        'msg' => 'Correct the highlighted fields and try again.',
        'errors' => $errors,
    ]);
}

$storedPaths = [
    'driving_license_image1' => (string) $existing['driving_license_image1'],
    'driving_license_image2' => (string) $existing['driving_license_image2'],
    'address_proof_image' => (string) $existing['address_proof_image'],
];
$movedFiles = [];
$transactionStarted = false;

try {
    foreach ($uploads as $upload) {
        if (!move_uploaded_file($upload['temporary_path'], $upload['destination'])) {
            throw new RuntimeException('Could not move uploaded ' . strtolower($upload['label']) . '.');
        }
        $movedFiles[] = $upload['destination'];
        $storedPaths[$upload['column']] = $upload['stored_path'];
    }

    $db->begin_transaction();
    $transactionStarted = true;
    $update = $db->prepare(
        'UPDATE customers
         SET name = ?, email = ?, phone = ?, address = ?,
             driving_license_no = ?, driving_license_image1 = ?,
             driving_license_image2 = ?, address_proof_no = ?,
             address_proof_image = ?
         WHERE id = ?'
    );
    $update->bind_param(
        'sssssssssi',
        $values['name'],
        $values['email'],
        $values['phone'],
        $values['address'],
        $values['driving_license_no'],
        $storedPaths['driving_license_image1'],
        $storedPaths['driving_license_image2'],
        $values['address_proof_no'],
        $storedPaths['address_proof_image'],
        $customerId
    );
    $update->execute();
    $db->commit();
    $transactionStarted = false;
} catch (Throwable $exception) {
    if ($transactionStarted) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackException) {
            error_log('Profile update rollback failed: ' . $rollbackException->getMessage());
        }
    }
    foreach ($movedFiles as $movedFile) {
        if (is_file($movedFile) && !unlink($movedFile)) {
            error_log('Could not remove incomplete profile upload: ' . $movedFile);
        }
    }
    error_log('Profile update failed for customer ' . $customerId . ': ' . $exception->getMessage());
    profile_update_response(500, ['msg' => 'Could not update your profile. Please try again.']);
}

foreach ($uploads as $upload) {
    $oldPath = $existing[$upload['column']] ?? '';
    if (
        is_string($oldPath)
        && strpos($oldPath, '../admin/uploaded-files/customer-doc/') === 0
    ) {
        $oldFile = __DIR__ . '/' . $oldPath;
        if (is_file($oldFile) && !unlink($oldFile)) {
            error_log('Could not remove replaced profile document: ' . $oldFile);
        }
    }
}

$user->name = $values['name'];
$user->email = $values['email'];
$user->phone = $values['phone'];
$user->address = $values['address'];
$_SESSION['user'] = $user;

profile_update_response(200, ['msg' => 'Your profile was updated successfully.']);
