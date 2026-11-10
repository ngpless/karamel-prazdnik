<?php
/**
 * Шапка страницы. Перед подключением задайте:
 *   $pageTitle (обязательно), $pageDesc, $active (ключ пункта меню), $noIndex.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/components.php';

$user = current_user();
$ui = ui_settings();
$lv = $ui['mode'] === 'lv';
$pageTitle = $pageTitle ?? 'Карамель';
$pageDesc = $pageDesc ?? 'Агентство детских и семейных праздников «Карамель»: дни рождения, выпускные, квесты и оформление шарами. Учебный проект.';
$active = $active ?? '';

// Если клиенту выдан временный пароль, пока он не сменён, пускаем только в кабинет
if ($user && (int)$user['must_change_password'] === 1 && SCRIPT_REL !== 'cabinet.php' && SCRIPT_REL !== 'logout.php' && SCRIPT_REL !== 'setview.php') {
    flash('warn', 'Для продолжения смените временный пароль на свой.');
    redirect('cabinet.php');
}

$navSections = db_all('SELECT slug, title FROM sections ORDER BY sort_order, id');
$unread = 0;
if ($user && $user['role'] === 'client') {
    $unread = (int)db_val('SELECT COUNT(*) FROM messages WHERE client_id = ? AND from_staff = 1 AND is_read = 0', [$user['id']]);
} elseif ($user && is_staff()) {
    $unread = (int)db_val('SELECT COUNT(*) FROM messages WHERE from_staff = 0 AND is_read = 0');
}

$bodyClass = $lv ? 'lv font-' . $ui['font'] . ' scheme-' . $ui['scheme'] . ($ui['img'] ? '' : ' noimg') : 'std';
$css = $lv ? 'css/lowvision.css' : 'css/style.css';
function view_link(array $params): string
{
    return e(url('setview.php', $params + ['back' => $_SERVER['REQUEST_URI'] ?? url('index.php')]));
}
function nav_cls(string $key, string $active): string
{
    return $key === $active ? ' class="is-active" aria-current="page"' : '';
}
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — Карамель</title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <?php if (!empty($noIndex)): ?><meta name="robots" content="noindex"><?php endif; ?>
    <script>document.documentElement.className += " js";</script>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset($css)) ?>?v=<?= (int)@filemtime(ROOT . '/assets/' . $css) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Перейти к содержанию</a>

<div class="a11y-bar" role="region" aria-label="Настройки отображения сайта">
    <div class="container a11y-inner">
        <?php if (!$lv): ?>
            <a class="a11y-btn" href="<?= view_link(['mode' => 'lv']) ?>" title="Включить версию сайта для слабовидящих">
                <span class="a11y-eye" aria-hidden="true">&#128065;</span> Версия для слабовидящих
            </a>
        <?php else: ?>
            <a class="a11y-btn" href="<?= view_link(['mode' => 'normal']) ?>">Обычная версия сайта</a>
            <span class="a11y-group">Размер шрифта:
                <a href="<?= view_link(['font' => 1]) ?>" class="a11y-opt<?= $ui['font'] === 1 ? ' on' : '' ?>" aria-label="Мелкий шрифт"<?= $ui['font'] === 1 ? ' aria-current="true"' : '' ?>>А</a>
                <a href="<?= view_link(['font' => 2]) ?>" class="a11y-opt a2<?= $ui['font'] === 2 ? ' on' : '' ?>" aria-label="Средний шрифт"<?= $ui['font'] === 2 ? ' aria-current="true"' : '' ?>>А</a>
                <a href="<?= view_link(['font' => 3]) ?>" class="a11y-opt a3<?= $ui['font'] === 3 ? ' on' : '' ?>" aria-label="Крупный шрифт"<?= $ui['font'] === 3 ? ' aria-current="true"' : '' ?>>А</a>
            </span>
            <span class="a11y-group">Цвета:
                <a href="<?= view_link(['scheme' => 'bw']) ?>" class="a11y-opt sc-bw<?= $ui['scheme'] === 'bw' ? ' on' : '' ?>" aria-label="Чёрный текст на белом"<?= $ui['scheme'] === 'bw' ? ' aria-current="true"' : '' ?>>Ц</a>
                <a href="<?= view_link(['scheme' => 'wb']) ?>" class="a11y-opt sc-wb<?= $ui['scheme'] === 'wb' ? ' on' : '' ?>" aria-label="Белый текст на чёрном"<?= $ui['scheme'] === 'wb' ? ' aria-current="true"' : '' ?>>Ц</a>
                <a href="<?= view_link(['scheme' => 'bl']) ?>" class="a11y-opt sc-bl<?= $ui['scheme'] === 'bl' ? ' on' : '' ?>" aria-label="Тёмно-синий текст на голубом"<?= $ui['scheme'] === 'bl' ? ' aria-current="true"' : '' ?>>Ц</a>
                <a href="<?= view_link(['scheme' => 'br']) ?>" class="a11y-opt sc-br<?= $ui['scheme'] === 'br' ? ' on' : '' ?>" aria-label="Коричневый текст на бежевом"<?= $ui['scheme'] === 'br' ? ' aria-current="true"' : '' ?>>Ц</a>
            </span>
            <span class="a11y-group">Изображения:
                <a href="<?= view_link(['img' => $ui['img'] ? 0 : 1]) ?>" class="a11y-btn small"><?= $ui['img'] ? 'Выключить' : 'Включить' ?></a>
            </span>
        <?php endif; ?>
    </div>
</div>

<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= e(url('index.php')) ?>" aria-label="Карамель — на главную">
            <img class="deco logo-mark" src="<?= e(asset('img/logo-mark.svg')) ?>" alt="" width="48" height="48">
            <span class="logo-text"><b>Карамель</b><small>агентство праздников</small></span>
        </a>
        <form class="search-mini" action="<?= e(url('search.php')) ?>" method="get" role="search">
            <label class="sr-only" for="q-head">Поиск по сайту</label>
            <input id="q-head" type="search" name="q" placeholder="Поиск по сайту" minlength="2" maxlength="80" value="<?= e(get('q')) ?>">
            <button type="submit" class="btn small">Найти</button>
        </form>
        <div class="user-links">
            <?php if ($user): ?>
                <span class="who">Вы вошли: <b><?= e($user['full_name']) ?></b></span>
                <a class="btn small ghost" href="<?= e(url($user['role'] === 'client' ? 'cabinet.php' : ($user['role'] === 'admin' ? 'admin/index.php' : 'staff/index.php'))) ?>">
                    <?= $user['role'] === 'client' ? 'Личный кабинет' : 'Панель' ?><?= $unread ? ' <span class="badge" title="Непрочитанных сообщений">' . $unread . '</span>' : '' ?>
                </a>
                <form class="inline" action="<?= e(url('logout.php')) ?>" method="post"><?= csrf_field() ?><button class="btn small ghost" type="submit">Выйти</button></form>
            <?php else: ?>
                <a class="btn small ghost" href="<?= e(url('login.php')) ?>">Войти</a>
                <a class="btn small" href="<?= e(url('register.php')) ?>">Регистрация</a>
            <?php endif; ?>
        </div>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mainnav">Меню</button>
    </div>
    <nav id="mainnav" class="main-nav" aria-label="Главное меню">
        <ul class="container">
            <li><a href="<?= e(url('index.php')) ?>"<?= nav_cls('home', $active) ?>>Главная</a></li>
            <li><a href="<?= e(url('about.php')) ?>"<?= nav_cls('about', $active) ?>>Об агентстве</a></li>
            <li><a href="<?= e(url('services.php')) ?>"<?= nav_cls('services', $active) ?>>Услуги</a></li>
            <li class="has-sub">
                <a href="<?= e(url('articles.php')) ?>"<?= nav_cls('articles', $active) ?>>Статьи</a>
                <ul class="sub">
                    <?php foreach ($navSections as $s): ?>
                        <li><a href="<?= e(url('articles.php', ['section' => $s['slug']])) ?>"><?= e($s['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="<?= e(url('news.php')) ?>"<?= nav_cls('news', $active) ?>>Новости</a></li>
            <li><a href="<?= e(url('contacts.php')) ?>"<?= nav_cls('contacts', $active) ?>>Контакты</a></li>
            <li><a href="<?= e(url('search.php')) ?>"<?= nav_cls('search', $active) ?>>Поиск</a></li>
        </ul>
    </nav>
</header>

<main id="main">
<?php foreach (flash_take() as $f): ?>
    <div class="container"><div class="flash flash-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($f['text']) ?></div></div>
<?php endforeach; ?>
