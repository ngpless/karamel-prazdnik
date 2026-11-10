<?php
/** Подвал страницы. */
declare(strict_types=1);
$ui = ui_settings();
if (!isset($showBanner)) {
    $showBanner = true;
}
if ($showBanner) {
    render_banners(true);
}
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="footer-logo"><img class="deco" src="<?= e(asset('img/logo-mark.svg')) ?>" alt="" width="36" height="36"> <b>Карамель</b></p>
            <p>Агентство детских и семейных праздников. Дни рождения, выпускные, квесты, оформление шарами.</p>
        </div>
        <div>
            <h2>Разделы сайта</h2>
            <ul class="plain">
                <li><a href="<?= e(url('about.php')) ?>">Об агентстве</a></li>
                <li><a href="<?= e(url('services.php')) ?>">Услуги</a></li>
                <li><a href="<?= e(url('articles.php')) ?>">Статьи</a></li>
                <li><a href="<?= e(url('news.php')) ?>">Новости</a></li>
                <li><a href="<?= e(url('contacts.php')) ?>">Контакты</a></li>
                <li><a href="<?= e(url('sitemap.php')) ?>">Карта сайта</a></li>
            </ul>
        </div>
        <div>
            <h2>Контакты (учебные данные)</h2>
            <p>Москва, ул. Учебная, д. 1<br>Тел.: +7 (000) 000-00-00<br>info@karamel.example</p>
            <p class="muted">Агентство вымышленное, адрес и телефон условные.</p>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>Учебный проект студентки Бутенко А.Э. · эксплуатационная практика, МУ им. С.Ю. Витте · 2025</p>
        </div>
    </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>?v=<?= (int)@filemtime(ROOT . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
