<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Услуги и цены';
$pageDesc = 'Услуги агентства «Карамель»: детские дни рождения, выпускные, квесты, оформление шарами, семейные торжества, аниматоры. Цены условные.';
$active = 'services';
$services = db_all('SELECT * FROM services ORDER BY sort_order, id');
require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Услуги']]);
?>
<div class="container layout">
    <div class="content">
        <header class="page-head"><div>
            <h1>Услуги и цены</h1>
            <p class="lead">Выберите формат праздника. Окончательная смета зависит от даты, площадки и числа гостей.</p>
            <p class="muted">Цены в этом учебном проекте условные.</p>
        </div></header>
        <?php foreach ($services as $s): ?>
            <article class="card service-full" id="<?= e($s['slug']) ?>">
                <img class="deco svc-img" src="<?= e(asset('img/ill/' . $s['image'])) ?>" alt="" width="120" height="120" loading="lazy">
                <div class="service-main">
                    <h2><?= e($s['title']) ?></h2>
                    <p><?= e($s['description']) ?></p>
                    <h3>Что входит</h3>
                    <ul>
                        <?php foreach (preg_split('/\R/u', trim($s['includes'])) ?: [] as $line): if (trim($line) === '') continue; ?>
                            <li><?= e(trim($line)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="service-side">
                    <p class="price">от <?= e(money((int)$s['price_from'])) ?></p>
                    <p class="muted">Длительность: <?= e($s['duration']) ?></p>
                    <a class="btn" href="<?= e(url('request.php', ['service' => $s['id']])) ?>">Заказать</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php render_sidebar(); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
