<?php
/** Меню служебных разделов (подключается внутрь страниц панели). */
if (!defined('KARAMEL_BOOT')) {
    http_response_code(404);
    exit;
}
$here = basename($_SERVER['SCRIPT_NAME'] ?? '');
$items = [
    'index.php'    => 'Сводка',
    'requests.php' => 'Заявки',
    'messages.php' => 'Сообщения',
    'feedback.php' => 'Обращения',
    'clients.php'  => 'Клиенты',
    'articles.php' => 'Статьи',
    'news.php'     => 'Новости',
];
?>
<nav class="subnav" aria-label="Разделы панели сотрудника">
    <ul>
        <?php foreach ($items as $file => $title): ?>
            <li><a href="<?= e(url('staff/' . $file)) ?>"<?= $here === $file ? ' class="is-active" aria-current="page"' : '' ?>><?= e($title) ?></a></li>
        <?php endforeach; ?>
        <?php if (is_role('admin')): ?>
            <li><a href="<?= e(url('admin/index.php')) ?>">Администратор &rarr;</a></li>
        <?php endif; ?>
    </ul>
</nav>
