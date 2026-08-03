<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configFile = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configFile)) {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
        header('Location: ' . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../install.php' : 'install.php'));
        exit;
    }
    return;
}

$config = require $configFile;
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

$db = Database::instance($config['db']);
