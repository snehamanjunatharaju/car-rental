<?php
require_once __DIR__ . '/app-config.php';

$key = ($appConfig['PAYU_KEY'] ?? '') ?: (getenv('PAYU_KEY') ?: '');
$salt = ($appConfig['PAYU_SALT'] ?? '') ?: (getenv('PAYU_SALT') ?: '');
$mode = ($appConfig['PAYU_MODE'] ?? '') ?: (getenv('PAYU_MODE') ?: 'test');
if (!in_array($mode, ['test', 'live'], true)) {
    throw new RuntimeException('PAYU_MODE must be set to test or live.');
}
$requestHost = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
    ? $_SERVER['HTTP_HOST']
    : '';
if (!preg_match('/\A(?:[a-zA-Z0-9.-]+|\[[0-9A-Fa-f:]+\])(?::([0-9]{1,5}))?\z/', $requestHost, $hostParts)) {
    throw new RuntimeException('The checkout host is invalid; payment callback URLs cannot be generated.');
}
if (isset($hostParts[1]) && (int) $hostParts[1] > 65535) {
    throw new RuntimeException('The checkout port is invalid; payment callback URLs cannot be generated.');
}

$requestIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
$callbackOrigin = ($requestIsHttps ? 'https://' : 'http://') . $requestHost;
$applicationDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
$applicationPath = $applicationDirectory === '/' || $applicationDirectory === '.'
    ? ''
    : '/' . trim($applicationDirectory, '/');
$success_url = $callbackOrigin . $applicationPath . '/response.php';
$failed_url = $callbackOrigin . $applicationPath . '/failed.php';
$cancelled_url = $callbackOrigin . $applicationPath . '/cancelled.php';
if ($mode === 'live') {
    $action = 'https://secure.payu.in/_payment';
} else {
    $action = 'https://test.payu.in/_payment';
}
?>