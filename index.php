<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Главная';
$pageDesc = 'Карамель — агентство детских и семейных праздников: дни рождения, выпускные в саду и школе, квесты, оформление шарами.';
$active = 'home';
$showBanner = false;

$services = db_all('SELECT * FROM services ORDER BY sort_order, id');
$sections = db_all('SELECT s.*, (SELECT COUNT(*) FROM articles a WHERE a.section_id = s.id AND a.is_published = 1) AS cnt FROM sections s ORDER BY s.sort_order, s.id');
$news = db_all('SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC, id DESC LIMIT 3');
$articleCount = (int)db_val('SELECT COUNT(*) FROM articles WHERE is_published = 1');
$popular = db_all('SELECT a.slug, a.title, a.summary, s.title AS section_title, s.image AS image
                   FROM articles a JOIN sections s ON s.id = a.section_id
                   WHERE a.is_published = 1 ORDER BY a.created_at DESC, a.id DESC LIMIT 3');

require __DIR__ . '/inc/header.php';
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="eyebrow">Детские и семейные праздники</p>
            <h1>Праздник, который дети вспоминают весь год</h1>
            <p class="lead">Придумаем сценарий, украсим зал шарами, пригласим аниматоров и возьмём на себя все хлопоты. Вам останется только радоваться вместе с гостями.</p>
            <div class="hero-actions">
                <a class="btn big" href="<?= e(url('request.php')) ?>">Оставить заявку</a>
                <a class="btn big ghost" href="<?= e(url('services.php')) ?>">Смотреть услуги</a>
            </div>
            <ul class="hero-facts">
                <li><b><?= count($services) ?></b> <?= plural(count($services), 'формат', 'формата', 'форматов') ?> праздника</li>
                <li><b><?= $articleCount ?></b> <?= plural($articleCount, 'полезная статья', 'полезные статьи', 'полезных статей') ?></li>
                <li><b>1</b> менеджер на весь праздник</li>
            </ul>
        </div>
        <img class="deco hero-img" src="<?= e(asset('img/ill/hero.svg')) ?>" alt="Иллюстрация: воздушные шары, праздничный торт и конфетти" width="560" height="460">
    </div>
</section>

<?php render_banners(false); ?>

<section class="container block">
    <div class="block-head">
        <h2>Что мы организуем</h2>
        <a class="more" href="<?= e(url('services.php')) ?>">Все услуги и цены</a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($services as $s): ?>
            <article class="card service-card">
                <img class="deco svc-img" src="<?= e(asset('img/ill/' . $s['image'])) ?>" alt="" loading="lazy" width="96" height="96">
                <h3><a href="<?= e(url('services.php') . '#' . $s['slug']) ?>"><?= e($s['title']) ?></a></h3>
                <p><?= e($s['short_text']) ?></p>
                <p class="price">от <?= e(money((int)$s['price_from'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="band">
    <div class="container">
        <h2>Как проходит работа с нами</h2>
        <ol class="steps">
            <li><b>Заявка.</b> Расскажите о поводе, дате и количестве гостей на сайте или в переписке с менеджером.</li>
            <li><b>Сценарий.</b> Мы предлагаем программу, смету и варианты оформления, вы выбираете подходящее.</li>
            <li><b>Подготовка.</b> Заказываем реквизит, согласуем площадку, репетируем с аниматорами.</li>
            <li><b>Праздник.</b> Команда приезжает заранее, всё проверяет и проводит программу от начала до конца.</li>
        </ol>
    </div>
</section>

<section class="container block">
    <div class="block-head">
        <h2>Полезно знать</h2>
        <a class="more" href="<?= e(url('articles.php')) ?>">Все статьи</a>
    </div>
    <div class="grid grid-4 sections-row">
        <?php foreach ($sections as $s): ?>
            <a class="card section-tile" href="<?= e(url('articles.php', ['section' => $s['slug']])) ?>">
                <img class="deco" src="<?= e(asset('img/ill/' . $s['image'])) ?>" alt="" loading="lazy">
                <span class="tile-title"><?= e($s['title']) ?></span>
                <span class="tile-count"><?= (int)$s['cnt'] ?> <?= plural((int)$s['cnt'], 'статья', 'статьи', 'статей') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <h3 class="mt">Новые статьи</h3>
    <div class="grid grid-3">
        <?php foreach ($popular as $a) { echo article_card($a); } ?>
    </div>
</section>

<section class="container block">
    <div class="block-head">
        <h2>Новости агентства</h2>
        <a class="more" href="<?= e(url('news.php')) ?>">Вся лента новостей</a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($news as $n) { echo news_card($n); } ?>
    </div>
</section>

<section class="container block">
    <div class="cta-box">
        <img class="deco" src="<?= e(asset('img/ill/cta.svg')) ?>" alt="" loading="lazy" width="220" height="160">
        <div>
            <h2>Есть вопрос по празднику?</h2>
            <p>Зарегистрируйтесь на сайте: в личном кабинете можно оставить заявку и написать менеджеру прямо из браузера.</p>
        </div>
        <div class="cta-actions">
            <a class="btn big" href="<?= e(url('register.php')) ?>">Создать кабинет</a>
            <a class="btn big ghost" href="<?= e(url('contacts.php')) ?>">Написать нам</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
