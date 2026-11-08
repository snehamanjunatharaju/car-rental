<?php
$localConfigPath = __DIR__ . '/local-config.php';
$appConfig = is_file($localConfigPath) ? require $localConfigPath : [];

if (!is_array($appConfig)) {
    throw new RuntimeException('src/local-config.php must return a configuration array.');
}
