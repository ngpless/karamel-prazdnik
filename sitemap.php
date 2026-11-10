<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Карта сайта';
$pageDesc = 'Все страницы сайта «Карамель» на одной странице.';
$active = '';
$showBanner = false;

$sections = db_all('SELECT * FROM sections ORDER BY sort_order, id');
$news = db_all('SELECT slug, title FROM news WHERE is_published = 1 ORDER BY created_at DESC, id DESC');
$services = db_all('SELECT slug, title FROM services ORDER BY sort_order, id');

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Карта сайта']]);
?>
<div class="container narrow-wide">
    <header class="page-head"><div>
        <h1>Карта сайта</h1>
        <p class="lead">Все разделы и материалы сайта на одной странице.</p>
    </div></header>
    <div class="sitemap">
        <ul class="tree">
            <li><a href="<?= e(url('index.php')) ?>">Главная</a></li>
            <li><a href="<?= e(url('about.php')) ?>">Об агентстве</a></li>
            <li><a href="<?= e(url('services.php')) ?>">Услуги</a>
                <ul>
                    <?php foreach ($services as $s): ?>
                        <li><a href="<?= e(url('services.php') . '#' . $s['slug']) ?>"><?= e($s['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="<?= e(url('articles.php')) ?>">Статьи</a>
                <ul>
                    <?php foreach ($sections as $sec):
                        $arts = db_all('SELECT slug, title FROM articles WHERE section_id = ? AND is_published = 1 ORDER BY created_at, id', [$sec['id']]); ?>
                        <li><a href="<?= e(url('articles.php', ['section' => $sec['slug']])) ?>"><?= e($sec['title']) ?></a> <span class="muted">(<?= count($arts) ?>)</span>
                            <ul>
                                <?php foreach ($arts as $a): ?>
                                    <li><a href="<?= e(url('article.php', ['slug' => $a['slug']])) ?>"><?= e($a['title']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="<?= e(url('news.php')) ?>">Новости</a> <span class="muted">(<?= count($news) ?>)</span>
                <ul>
                    <?php foreach ($news as $n): ?>
                        <li><a href="<?= e(url('news.php', ['slug' => $n['slug']])) ?>"><?= e($n['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="<?= e(url('search.php')) ?>">Поиск по сайту</a></li>
            <li><a href="<?= e(url('contacts.php')) ?>">Контакты</a></li>
            <li>Личный кабинет
                <ul>
                    <li><a href="<?= e(url('register.php')) ?>">Регистрация</a></li>
                    <li><a href="<?= e(url('login.php')) ?>">Вход</a></li>
                    <li><a href="<?= e(url('cabinet.php')) ?>">Профиль клиента</a></li>
                    <li><a href="<?= e(url('messages.php')) ?>">Переписка с менеджером</a></li>
                    <li><a href="<?= e(url('request.php')) ?>">Заявка на праздник</a></li>
                </ul>
            </li>
            <li>Служебные страницы
                <ul>
                    <li><a href="<?= e(url('staff/index.php')) ?>">Панель сотрудника</a></li>
                    <li><a href="<?= e(url('admin/index.php')) ?>">Панель администратора</a></li>
                    <li><a href="<?= e(url('sitemap.php')) ?>">Карта сайта</a></li>
                    <li><a href="<?= e(url('404.php')) ?>">Страница 404 (ошибка «не найдено»)</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
