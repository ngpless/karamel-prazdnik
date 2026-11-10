<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$active = 'search';
$pageTitle = 'Поиск по сайту';
$pageDesc = 'Поиск по статьям и новостям агентства «Карамель».';
$q = mb_substr(get('q'), 0, 80);
$words = [];
$articles = $news = [];
$message = '';

if ($q !== '') {
    $words = array_values(array_unique(array_filter(preg_split('/\s+/u', mb_strtolower($q)) ?: [], fn($w) => mb_strlen($w) >= 2)));
    $words = array_slice($words, 0, 5);
    if (!$words) {
        $message = 'Введите слово не короче двух букв.';
    } else {
        // Каждое слово должно встретиться в тексте (поиск по столбцу search_text в нижнем регистре)
        $cond = [];
        $params = [];
        foreach ($words as $w) {
            $cond[] = "search_text LIKE ? ESCAPE '!'";
            $params[] = '%' . like_escape($w) . '%';
        }
        $where = implode(' AND ', $cond);
        $articles = db_all("SELECT a.slug, a.title, a.summary, a.body, s.title AS section_title
                            FROM articles a JOIN sections s ON s.id = a.section_id
                            WHERE a.is_published = 1 AND $where ORDER BY a.created_at DESC LIMIT 50", $params);
        $news = db_all("SELECT slug, title, summary, body, created_at FROM news WHERE is_published = 1 AND $where ORDER BY created_at DESC LIMIT 50", $params);
    }
}
$found = count($articles) + count($news);

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Поиск']]);
?>
<div class="container layout">
    <div class="content">
        <header class="page-head"><div>
            <h1>Поиск по сайту</h1>
            <p class="lead">Ищем по заголовкам и текстам всех статей и новостей.</p>
        </div></header>
        <form class="search-big" action="<?= e(url('search.php')) ?>" method="get" role="search">
            <label for="q-main">Что вы ищете?</label>
            <div class="row">
                <input id="q-main" type="search" name="q" value="<?= e($q) ?>" maxlength="80" placeholder="Например: шары, квест, выпускной" required>
                <button class="btn" type="submit">Найти</button>
            </div>
        </form>

        <?php if ($q === ''): ?>
            <p class="muted">Введите слово или фразу и нажмите «Найти». Подсказка: попробуйте «шары», «бюджет» или «безопасность».</p>
        <?php elseif ($message !== ''): ?>
            <div class="flash flash-warn"><?= e($message) ?></div>
        <?php elseif ($found === 0): ?>
            <div class="card empty-result">
                <h2>Ничего не найдено</h2>
                <p>По запросу «<b><?= e($q) ?></b>» нет ни статей, ни новостей. Проверьте написание, попробуйте другое слово или более короткий запрос.</p>
                <p>Можно перейти в <a href="<?= e(url('articles.php')) ?>">каталог статей</a> или <a href="<?= e(url('sitemap.php')) ?>">карту сайта</a>.</p>
            </div>
        <?php else: ?>
            <p class="result-count">По запросу «<b><?= e($q) ?></b>» найдено: <?= $found ?> (статей: <?= count($articles) ?>, новостей: <?= count($news) ?>)</p>
            <?php if ($articles): ?>
                <h2>Статьи</h2>
                <ul class="results">
                    <?php foreach ($articles as $a): ?>
                        <li>
                            <h3><a href="<?= e(url('article.php', ['slug' => $a['slug']])) ?>"><?= highlight($a['title'], $words) ?></a></h3>
                            <p class="eyebrow"><?= e($a['section_title']) ?></p>
                            <p><?= highlight(snippet($a['body'], $words), $words) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($news): ?>
                <h2>Новости</h2>
                <ul class="results">
                    <?php foreach ($news as $n): ?>
                        <li>
                            <h3><a href="<?= e(url('news.php', ['slug' => $n['slug']])) ?>"><?= highlight($n['title'], $words) ?></a></h3>
                            <p class="eyebrow"><?= e(ru_date($n['created_at'])) ?></p>
                            <p><?= highlight(snippet($n['body'], $words), $words) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php render_sidebar(); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
