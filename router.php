<?php
/**
 * Маршрутизатор для встроенного сервера PHP:
 *     php -S localhost:8080 router.php
 * Правила (то же самое для Apache делает .htaccess):
 *  - путь декодируется, приводится к реальному (realpath) и должен лежать внутри корня сайта;
 *  - статические файлы отдаются только из assets/ (и robots.txt) и только с разрешёнными расширениями;
 *  - .php выполняется только для публичных точек входа: файлы в корне и в staff/, admin/, без «_» в начале имени;
 *  - всё остальное (inc, sql, data, content, tools, .git, config.php, скрытые файлы) показывает страницу 404.
 */
$root = realpath(__DIR__);

$show404 = function () use ($root) {
    $_SERVER['SCRIPT_NAME'] = '/404.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . DIRECTORY_SEPARATOR . '404.php';
    chdir($root);
    require $root . '/404.php';
};

$raw = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$path = rawurldecode($raw);
// Нулевой байт, обратные слэши и сегменты «..» не допускаются вообще
if (preg_match('~[\x00-\x1f\x7f\\\\]~', $path) || preg_match('~(^|/)\.\.(/|$)~', $path)) {
    $show404();
    return true;
}
$path = '/' . ltrim($path, '/');

$real = realpath($root . $path);
if ($real === false || stripos($real, $root . DIRECTORY_SEPARATOR) !== 0 && strcasecmp($real, $root) !== 0) {
    $show404();
    return true;
}
// Относительный путь уже после разрешения, сравнение без учёта регистра
$rel = strtolower(str_replace('\\', '/', substr($real, strlen($root))));
$rel = ltrim($rel, '/');

// Папка: открываем её index.php, если он публичный
if (is_dir($real)) {
    $index = $real . DIRECTORY_SEPARATOR . 'index.php';
    $relIndex = ($rel === '' ? '' : $rel . '/') . 'index.php';
    $okDir = ($rel === '' || $rel === 'staff' || $rel === 'admin');
    if ($okDir && is_file($index)) {
        $_SERVER['SCRIPT_NAME'] = '/' . $relIndex;
        $_SERVER['SCRIPT_FILENAME'] = $index;
        chdir($real);
        require $index;
        return true;
    }
    $show404();
    return true;
}

$segments = $rel === '' ? [] : explode('/', $rel);
foreach ($segments as $seg) {
    if ($seg !== '' && $seg[0] === '.') {       // скрытые файлы и каталоги (.git, .htaccess, .gitignore)
        $show404();
        return true;
    }
}
$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));

if ($ext === 'php') {
    $dir = count($segments) > 1 ? $segments[0] : '';
    $name = end($segments);
    $public = ($dir === '' || $dir === 'staff' || $dir === 'admin') && count($segments) <= 2
        && $name[0] !== '_' && !in_array($name, ['router.php', 'config.php', 'config.example.php'], true);
    if (!$public) {
        $show404();
        return true;
    }
    $_SERVER['SCRIPT_NAME'] = '/' . $rel;
    $_SERVER['SCRIPT_FILENAME'] = $real;
    chdir(dirname($real));
    require $real;
    return true;
}

// Статика: только assets/ и robots.txt, по белому списку расширений
$allowed = ['css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2'];
if ((isset($segments[0]) && $segments[0] === 'assets' && in_array($ext, $allowed, true)) || $rel === 'robots.txt') {
    return false;   // файл отдаёт сам сервер
}
$show404();
return true;
