<?php
/**
 * Маршрутизатор для встроенного сервера PHP:
 *     php -S localhost:8080 router.php
 * Отдаёт существующие файлы как есть, закрывает служебные папки
 * и показывает страницу 404 для любого несуществующего адреса
 * (то же самое делает .htaccess под Apache).
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$path = '/' . ltrim(str_replace('\\', '/', $path), '/');
$root = __DIR__;

$show404 = function () use ($root) {
    $_SERVER['SCRIPT_NAME'] = '/404.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/404.php';
    chdir($root);
    require $root . '/404.php';
};

// Служебные места закрыты, как и в .htaccess
if (preg_match('~^/(inc|sql|data|content|tools|\.git)(/|$)~', $path) || preg_match('~/(config(\.example)?\.php|router\.php|\.htaccess|\.gitignore)$~', $path)
    || preg_match('~/_menu\.php$~', $path)) {
    $show404();
    return true;
}

$file = realpath($root . $path);
if ($file !== false && strpos($file, realpath($root)) === 0) {
    if (is_dir($file)) {
        $index = $file . DIRECTORY_SEPARATOR . 'index.php';
        if (is_file($index)) {
            $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
            $_SERVER['SCRIPT_FILENAME'] = $index;
            chdir(dirname($index));
            require $index;
            return true;
        }
    } elseif (is_file($file)) {
        if (substr($file, -4) === '.php') {
            $_SERVER['SCRIPT_NAME'] = $path;
            $_SERVER['SCRIPT_FILENAME'] = $file;
            chdir(dirname($file));
            require $file;
            return true;
        }
        return false;   // статические файлы отдаёт сам сервер
    }
}
$show404();
return true;
