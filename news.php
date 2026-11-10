<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$active = 'news';
$slug = get('slug');

if ($slug !== '') {
    $item = db_one('SELECT * FROM news WHERE slug = ? AND is_published = 1', [$slug]);
    if (!$item) {
        require __DIR__ . '/404.php';
        exit;
    }
    $others = db_all('SELECT slug, title, created_at FROM news WHERE is_published = 1 AND id <> ? ORDER BY created_at DESC, id DESC LIMIT 4', [$item['id']]);
    $pageTitle = $item['title'];
    $pageDesc = $item['summary'];
    require __DIR__ . '/inc/header.php';
    echo breadcrumbs([['Новости', url('news.php')], [$item['title']]]);
    ?>
    <div class="container layout">
        <article class="content article">
            <header class="article-head">
                <p class="eyebrow">Новости агентства</p>
                <h1><?= e($item['title']) ?></h1>
                <p class="meta"><time datetime="<?= e(substr($item['created_at'], 0, 10)) ?>"><?= e(ru_date($item['created_at'])) ?></time></p>
            </header>
            <p class="lead"><?= e($item['summary']) ?></p>
            <div class="prose"><?= format_body($item['body']) ?></div>
            <section class="related">
                <h2>Другие новости</h2>
                <ul>
                    <?php foreach ($others as $o): ?>
                        <li><a href="<?= e(url('news.php', ['slug' => $o['slug']])) ?>"><?= e($o['title']) ?></a> <span class="muted">(<?= e(ru_date($o['created_at'])) ?>)</span></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <p class="back-link"><a href="<?= e(url('news.php')) ?>">&larr; Вся лента новостей</a></p>
        </article>
        <?php render_sidebar(); ?>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

$perPage = 6;
$total = (int)db_val('SELECT COUNT(*) FROM news WHERE is_published = 1');
$pages = max(1, (int)ceil($total / $perPage));
$page = min(max(1, (int)get('page', '1')), $pages);
$list = db_all('SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));

$pageTitle = 'Новости' . ($page > 1 ? ', страница ' . $page : '');
$pageDesc = 'Лента новостей агентства «Карамель»: новые программы, акции, итоги праздников и советы.';
require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Новости']]);
?>
<div class="container layout">
    <div class="content">
        <header class="page-head">
            <div>
                <h1>Лента новостей</h1>
                <p class="lead">Что нового в агентстве: сезонные программы, акции для выпускников, итоги прошедших праздников.</p>
                <p class="muted">Всего новостей: <?= $total ?></p>
            </div>
        </header>
        <div class="news-feed">
            <?php foreach ($list as $n) { echo news_card($n); } ?>
        </div>
        <?= pagination($page, $pages, 'news.php') ?>
    </div>
    <?php render_sidebar(); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
