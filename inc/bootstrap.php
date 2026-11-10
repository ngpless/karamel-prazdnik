<?php
/**
 * Общая инициализация: конфигурация, сессия, адреса, подключение модулей.
 * Подключается первой строкой каждой страницы.
 */
declare(strict_types=1);

if (defined('KARAMEL_BOOT')) {
    return;
}
define('KARAMEL_BOOT', true);
define('ROOT', dirname(__DIR__));

// --- Конфигурация (если config.php нет, берётся пример с режимом SQLite) ---
$GLOBALS['CFG'] = is_file(ROOT . '/config.php')
    ? require ROOT . '/config.php'
    : require ROOT . '/config.example.php';

$debug = !empty($GLOBALS['CFG']['site']['debug']);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('UTF-8');

// --- Базовый адрес сайта (работает и в корне домена, и в подпапке htdocs/karamel) ---
(function (): void {
    // Пути сравниваются в канонической форме: на Windows Apache и PHP могут записывать один и тот же каталог
    // по-разному (короткие имена 8.3, кириллица в имени профиля), и без realpath подкаталоги staff/ и admin/ теряли бы префикс.
    $scriptRaw = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
    $script = str_replace('\\', '/', (string)(realpath($scriptRaw) ?: $scriptRaw));
    $root = str_replace('\\', '/', (string)(realpath(ROOT) ?: ROOT));
    $rel = (stripos($script, $root . '/') === 0) ? substr($script, strlen($root) + 1) : basename($script);
    $name = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = '';
    if ($rel !== '' && strlen($name) >= strlen($rel) && strcasecmp(substr($name, -strlen($rel)), $rel) === 0) {
        $base = substr($name, 0, strlen($name) - strlen($rel));
    } else {
        $base = rtrim(dirname($name), '/');
    }
    define('BASE', rtrim($base, '/'));
    define('SCRIPT_REL', $rel);
})();

// --- Сессия ---
if (session_status() === PHP_SESSION_NONE) {
    session_name('karamel_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE === '' ? '/' : BASE . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
