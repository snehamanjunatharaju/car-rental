<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/../src/Database.php';

$name = getenv('ADMIN_NAME') ?: '';
$username = getenv('ADMIN_USERNAME') ?: '';
$password = getenv('ADMIN_PASSWORD') ?: '';

if ($name === '' || strlen($name) > 150) {
    fwrite(STDERR, "ADMIN_NAME must contain 1 to 150 characters.\n");
    exit(1);
}
if ($username === '' || strlen($username) > 150) {
    fwrite(STDERR, "ADMIN_USERNAME must contain 1 to 150 characters.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "ADMIN_PASSWORD must contain at least 12 characters.\n");
    exit(1);
}

$check = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$check->bind_param('s', $username);
$check->execute();
if ($check->get_result()->fetch_assoc()) {
    fwrite(STDERR, "That administrator username already exists.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $db->prepare('INSERT INTO users (name, username, password) VALUES (?, ?, ?)');
$insert->bind_param('sss', $name, $username, $passwordHash);
$insert->execute();
fwrite(STDOUT, "Administrator account created.\n");
