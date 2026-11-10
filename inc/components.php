<?php
/**
 * Повторно используемые блоки интерфейса: баннеры, хлебные крошки, карточки, пагинация, поля форм.
 */
declare(strict_types=1);

/** Активные баннеры в порядке показа. */
function active_banners(): array
{
    return db_all('SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order, id');
}

/** Баннеры с ротацией (JS) или списком (для слабовидящих). $compact — узкая полоса на внутренних страницах. */
function render_banners(bool $compact = false): void
{
    $items = active_banners();
    if (!$items) {
        return;
    }
    $lv = ui_is_lv();
    if ($compact) {
        // На внутренних страницах показываем один баннер, который меняется при каждой загрузке
        $items = [$items[(int)(time() / 15) % count($items)]];
    }
    echo '<section class="container banners' . ($compact ? ' banners-compact' : '') . '" aria-label="Акции и предложения"'
        . ($compact || $lv ? '' : ' data-carousel') . '>';
    if (!$compact && !$lv) {
        echo '<div class="slides" aria-live="off">';
    } else {
        echo '<div class="slides static">';
    }
    foreach ($items as $i => $b) {
        $img = preg_match('/^[a-z0-9-]+\.svg$/', $b['image']) ? $b['image'] : 'b1.svg';
        echo '<article class="slide' . ($i === 0 ? ' is-current' : '') . '" data-slide>';
        echo '<img class="deco slide-img" src="' . e(asset('img/banners/' . $img)) . '" alt="" loading="lazy">';
        echo '<div class="slide-text"><h2>' . e($b['title']) . '</h2><p>' . e($b['text']) . '</p>';
        echo '<a class="btn" href="' . e(url($b['link'])) . '">' . e($b['button']) . '</a></div>';
        echo '</article>';
    }
    echo '</div>';
    if (!$compact && !$lv && count($items) > 1) {
        echo '<div class="carousel-ctl" hidden>'
            . '<button type="button" class="c-prev" aria-label="Предыдущий баннер">&#10094;</button>'
            . '<span class="c-dots">';
        foreach ($items as $i => $b) {
            echo '<button type="button" class="c-dot' . ($i === 0 ? ' on' : '') . '" aria-label="Баннер ' . ($i + 1) . ' из ' . count($items) . '"></button>';
        }
        echo '</span><button type="button" class="c-next" aria-label="Следующий баннер">&#10095;</button>'
            . '<button type="button" class="c-pause" aria-label="Остановить смену баннеров">II</button></div>';
    }
    echo '</section>';
}

/** Хлебные крошки: [['Название', 'адрес'], ..., ['Текущая']]. */
function breadcrumbs(array $items): string
{
    $html = '<nav class="crumbs" aria-label="Вы здесь"><ol class="container">';
    $html .= '<li><a href="' . e(url('index.php')) . '">Главная</a></li>';
    $last = count($items) - 1;
    foreach ($items as $i => $it) {
        if ($i === $last || empty($it[1])) {
            $html .= '<li aria-current="page">' . e($it[0]) . '</li>';
        } else {
            $html .= '<li><a href="' . e($it[1]) . '">' . e($it[0]) . '</a></li>';
        }
    }
    return $html . '</ol></nav>';
}

/** Карточка статьи для списков. */
function article_card(array $a): string
{
    $href = url('article.php', ['slug' => $a['slug']]);
    return '<article class="card article-card">'
        . '<img class="deco card-img" src="' . e(asset('img/ill/' . $a['image'])) . '" alt="" loading="lazy">'
        . '<div class="card-body"><p class="eyebrow">' . e($a['section_title']) . '</p>'
        . '<h3><a href="' . e($href) . '">' . e($a['title']) . '</a></h3>'
        . '<p>' . e($a['summary']) . '</p>'
        . '<a class="more" href="' . e($href) . '">Читать статью<span class="sr-only">: ' . e($a['title']) . '</span></a></div></article>';
}

function news_card(array $n): string
{
    $href = url('news.php', ['slug' => $n['slug']]);
    return '<article class="card news-card"><div class="card-body">'
        . '<p class="eyebrow"><time datetime="' . e(substr($n['created_at'], 0, 10)) . '">' . e(ru_date($n['created_at'])) . '</time></p>'
        . '<h3><a href="' . e($href) . '">' . e($n['title']) . '</a></h3>'
        . '<p>' . e($n['summary']) . '</p>'
        . '<a class="more" href="' . e($href) . '">Читать новость<span class="sr-only">: ' . e($n['title']) . '</span></a></div></article>';
}

/** Постраничная навигация. */
function pagination(int $page, int $pages, string $script, array $query = []): string
{
    if ($pages <= 1) {
        return '';
    }
    $h = '<nav class="pager" aria-label="Страницы"><ul>';
    if ($page > 1) {
        $h .= '<li><a href="' . e(url($script, $query + ['page' => $page - 1])) . '" rel="prev">&larr; Назад</a></li>';
    }
    for ($i = 1; $i <= $pages; $i++) {
        $h .= $i === $page
            ? '<li><span class="cur" aria-current="page">' . $i . '</span></li>'
            : '<li><a href="' . e(url($script, $query + ['page' => $i])) . '">' . $i . '</a></li>';
    }
    if ($page < $pages) {
        $h .= '<li><a href="' . e(url($script, $query + ['page' => $page + 1])) . '" rel="next">Вперёд &rarr;</a></li>';
    }
    return $h . '</ul></nav>';
}

/** Боковая колонка внутренних страниц: разделы и свежие новости. */
function render_sidebar(string $currentSection = ''): void
{
    $secs = db_all('SELECT s.slug, s.title, (SELECT COUNT(*) FROM articles a WHERE a.section_id = s.id AND a.is_published = 1) AS cnt
                    FROM sections s ORDER BY s.sort_order, s.id');
    $news = db_all('SELECT slug, title, created_at FROM news WHERE is_published = 1 ORDER BY created_at DESC, id DESC LIMIT 3');
    echo '<aside class="sidebar" aria-label="Дополнительно">';
    echo '<section class="side-block"><h2>Разделы статей</h2><ul class="plain">';
    foreach ($secs as $s) {
        echo '<li><a href="' . e(url('articles.php', ['section' => $s['slug']])) . '"' . ($s['slug'] === $currentSection ? ' class="is-active"' : '') . '>'
            . e($s['title']) . '</a> <span class="count">' . (int)$s['cnt'] . '</span></li>';
    }
    echo '</ul></section>';
    echo '<section class="side-block"><h2>Свежие новости</h2><ul class="plain news-mini">';
    foreach ($news as $n) {
        echo '<li><a href="' . e(url('news.php', ['slug' => $n['slug']])) . '">' . e($n['title']) . '</a><br><small>' . e(ru_date($n['created_at'])) . '</small></li>';
    }
    echo '</ul></section>';
    echo '<section class="side-block side-cta"><h2>Нужен праздник?</h2><p>Оставьте заявку, и менеджер свяжется с вами.</p>'
        . '<a class="btn" href="' . e(url('request.php')) . '">Заказать праздник</a></section>';
    echo '</aside>';
}

/** Блок ошибки у поля формы. */
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-error" id="err-' . e($key) . '" role="alert">' . e($errors[$key]) . '</span>' : '';
}

/** Атрибуты доступности для поля с ошибкой. */
function field_aria(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}
