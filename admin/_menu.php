<?php
/** Меню администратора (подключается внутрь страниц панели). */
if (!defined('KARAMEL_BOOT')) {
    http_response_code(404);
    exit;
}
$here = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="subnav" aria-label="Разделы панели администратора">
    <ul>
        <li><a href="<?= e(url('admin/index.php')) ?>"<?= $here === 'index.php' ? ' class="is-active" aria-current="page"' : '' ?>>Пользователи и роли</a></li>
        <li><a href="<?= e(url('admin/banners.php')) ?>"<?= $here === 'banners.php' ? ' class="is-active" aria-current="page"' : '' ?>>Баннеры</a></li>
        <li><a href="<?= e(url('staff/index.php')) ?>">Панель сотрудника &rarr;</a></li>
    </ul>
</nav>
