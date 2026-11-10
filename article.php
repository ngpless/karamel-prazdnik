<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$slug = get('slug');
$article = $slug === '' ? null : db_one(
    'SELECT a.*, s.slug AS section_slug, s.title AS section_title, s.image AS image
     FROM articles a JOIN sections s ON s.id = a.section_id
     WHERE a.slug = ? AND a.is_published = 1', [$slug]);
if (!$article) {
    require __DIR__ . '/404.php';
    exit;
}

$prev = db_one('SELECT slug, title FROM articles WHERE section_id = ? AND is_published = 1 AND (created_at < ? OR (created_at = ? AND id < ?))
                ORDER BY created_at DESC, id DESC LIMIT 1', [$article['section_id'], $article['created_at'], $article['created_at'], $article['id']]);
$next = db_one('SELECT slug, title FROM articles WHERE section_id = ? AND is_published = 1 AND (created_at > ? OR (created_at = ? AND id > ?))
                ORDER BY created_at, id LIMIT 1', [$article['section_id'], $article['created_at'], $article['created_at'], $article['id']]);
$related = db_all('SELECT slug, title FROM articles WHERE section_id = ? AND id <> ? AND is_published = 1 ORDER BY created_at, id LIMIT 4',
    [$article['section_id'], $article['id']]);

$pageTitle = $article['title'];
$pageDesc = $article['summary'];
$active = 'articles';
require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Статьи', url('articles.php')], [$article['section_title'], url('articles.php', ['section' => $article['section_slug']])], [$article['title']]]);
?>
<div class="container layout">
    <article class="content article">
        <header class="article-head">
            <p class="eyebrow"><a href="<?= e(url('articles.php', ['section' => $article['section_slug']])) ?>"><?= e($article['section_title']) ?></a></p>
            <h1><?= e($article['title']) ?></h1>
            <p class="meta">
                <time datetime="<?= e(substr($article['created_at'], 0, 10)) ?>"><?= e(ru_date($article['created_at'])) ?></time>
                <?= $article['author'] !== '' ? ' · ' . e($article['author']) : '' ?>
                · чтение около <?= max(1, (int)round(word_count($article['body']) / 180)) ?> мин
            </p>
        </header>
        <figure class="article-fig">
            <img class="deco" src="<?= e(asset('img/ill/' . $article['image'])) ?>" alt="Иллюстрация к разделу «<?= e($article['section_title']) ?>»" loading="lazy">
            <figcaption>Иллюстрация раздела «<?= e($article['section_title']) ?>»</figcaption>
        </figure>
        <p class="lead"><?= e($article['summary']) ?></p>
        <div class="prose"><?= format_body($article['body']) ?></div>

        <nav class="prev-next" aria-label="Другие статьи раздела">
            <?php if ($prev): ?><a class="pn prev" href="<?= e(url('article.php', ['slug' => $prev['slug']])) ?>"><small>&larr; Предыдущая</small><?= e($prev['title']) ?></a><?php else: ?><span></span><?php endif; ?>
            <?php if ($next): ?><a class="pn next" href="<?= e(url('article.php', ['slug' => $next['slug']])) ?>"><small>Следующая &rarr;</small><?= e($next['title']) ?></a><?php endif; ?>
        </nav>

        <?php if ($related): ?>
            <section class="related">
                <h2>Читайте также в разделе «<?= e($article['section_title']) ?>»</h2>
                <ul>
                    <?php foreach ($related as $r): ?>
                        <li><a href="<?= e(url('article.php', ['slug' => $r['slug']])) ?>"><?= e($r['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
        <p class="back-link"><a href="<?= e(url('articles.php', ['section' => $article['section_slug']])) ?>">&larr; Все статьи раздела</a></p>
    </article>
    <?php render_sidebar($article['section_slug']); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
