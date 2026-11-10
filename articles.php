<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$active = 'articles';
$slug = get('section');

if ($slug !== '') {
    $section = db_one('SELECT * FROM sections WHERE slug = ?', [$slug]);
    if (!$section) {
        require __DIR__ . '/404.php';
        exit;
    }
    $articles = db_all('SELECT a.slug, a.title, a.summary, a.created_at, s.title AS section_title, s.image AS image
                        FROM articles a JOIN sections s ON s.id = a.section_id
                        WHERE a.section_id = ? AND a.is_published = 1 ORDER BY a.created_at, a.id', [$section['id']]);
    $pageTitle = $section['title'];
    $pageDesc = $section['description'];
    require __DIR__ . '/inc/header.php';
    echo breadcrumbs([['Статьи', url('articles.php')], [$section['title']]]);
    ?>
    <div class="container layout">
        <div class="content">
            <header class="page-head">
                <img class="deco page-head-img" src="<?= e(asset('img/ill/' . $section['image'])) ?>" alt="" width="220" height="150">
                <div>
                    <h1><?= e($section['title']) ?></h1>
                    <p class="lead"><?= e($section['description']) ?></p>
                    <p class="muted">В разделе <?= count($articles) ?> <?= plural(count($articles), 'статья', 'статьи', 'статей') ?></p>
                </div>
            </header>
            <div class="grid grid-2">
                <?php foreach ($articles as $a) { echo article_card($a); } ?>
            </div>
        </div>
        <?php render_sidebar($section['slug']); ?>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

$pageTitle = 'Статьи';
$pageDesc = 'Статьи агентства «Карамель» о детских праздниках, выпускных, оформлении шарами, квестах и семейных программах.';
$sections = db_all('SELECT * FROM sections ORDER BY sort_order, id');
require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Статьи']]);
?>
<div class="container layout">
    <div class="content">
        <header class="page-head">
            <div>
                <h1>Статьи о праздниках</h1>
                <p class="lead">Практические советы для родителей и воспитателей: как выбрать тему, составить программу, рассчитать бюджет и не упустить мелочи.</p>
            </div>
        </header>
        <?php foreach ($sections as $s):
            $list = db_all('SELECT a.slug, a.title, a.summary, a.created_at, s.title AS section_title, s.image AS image
                            FROM articles a JOIN sections s ON s.id = a.section_id
                            WHERE a.section_id = ? AND a.is_published = 1 ORDER BY a.created_at, a.id', [$s['id']]); ?>
            <section class="sec-block" id="<?= e($s['slug']) ?>">
                <div class="block-head">
                    <h2><a href="<?= e(url('articles.php', ['section' => $s['slug']])) ?>"><?= e($s['title']) ?></a></h2>
                    <span class="muted"><?= count($list) ?> <?= plural(count($list), 'статья', 'статьи', 'статей') ?></span>
                </div>
                <p><?= e($s['description']) ?></p>
                <ul class="article-list">
                    <?php foreach ($list as $a): ?>
                        <li><a href="<?= e(url('article.php', ['slug' => $a['slug']])) ?>"><?= e($a['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
    <?php render_sidebar(); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
