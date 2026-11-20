<?php
/**
 * Her sayfanın en başında dahil edilen ortak başlangıç dosyası.
 * Yapılandırmayı yükler, oturumu başlatır ve veritabanı bağlantısını kurar.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Yapılandırma bulunamadı. config/config.example.php dosyasını config/config.php olarak kopyalayıp düzenleyin.');
}
$config = require $configFile;

if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$conn = db_connect($config['db']);

oturum_zaman_asimi_kontrol((int) ($config['oturum_suresi_dk'] ?? 30));
