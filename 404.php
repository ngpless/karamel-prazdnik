<?php
/**
 * Страница 404. Apache (.htaccess) и встроенный сервер PHP (router.php)
 * направляют сюда любой несуществующий адрес. Код ответа — 404.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/bootstrap.php';

http_response_code(404);
$pageTitle = 'Страница не найдена';
$pageDesc = 'Такой страницы нет на сайте «Карамель».';
$active = '';
$noIndex = true;
$showBanner = false;
require __DIR__ . '/inc/header.php';
?>
<section class="container narrow not-found">
    <img class="deco" src="<?= e(asset('img/ill/404.svg')) ?>" alt="" width="360" height="260">
    <p class="big-code" aria-hidden="true">404</p>
    <h1>Праздник здесь не состоялся</h1>
    <p class="lead">Страница, которую вы искали, не найдена: возможно, она переехала или в адресе опечатка. Давайте вернёмся к веселью.</p>
    <p class="actions">
        <a class="btn big" href="<?= e(url('index.php')) ?>">На главную</a>
        <a class="btn big ghost" href="<?= e(url('sitemap.php')) ?>">Карта сайта</a>
    </p>
    <form class="search-big" action="<?= e(url('search.php')) ?>" method="get" role="search">
        <label for="q-404">Или найдите нужное по слову</label>
        <div class="row">
            <input id="q-404" type="search" name="q" maxlength="80" placeholder="Например: выпускной">
            <button class="btn" type="submit">Найти</button>
        </div>
    </form>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
