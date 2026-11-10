<?php
/**
 * Переключатель оформления: запоминает выбор в cookie на год
 * (режим, размер шрифта, цветовая схема, показ изображений) и возвращает на прежнюю страницу.
 */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$opts = ['expires' => time() + 365 * 86400, 'path' => BASE === '' ? '/' : BASE . '/', 'samesite' => 'Lax'];

if (isset($_GET['mode'])) {
    setcookie('ui_mode', $_GET['mode'] === 'lv' ? 'lv' : 'normal', $opts);
}
if (isset($_GET['font']) && in_array((int)$_GET['font'], [1, 2, 3], true)) {
    setcookie('ui_font', (string)(int)$_GET['font'], $opts);
}
if (isset($_GET['scheme']) && in_array($_GET['scheme'], ['bw', 'wb', 'bl', 'br'], true)) {
    setcookie('ui_scheme', $_GET['scheme'], $opts);
}
if (isset($_GET['img'])) {
    setcookie('ui_img', $_GET['img'] === '1' ? '1' : '0', $opts);
}

header('Location: ' . safe_local(get('back')));
exit;
