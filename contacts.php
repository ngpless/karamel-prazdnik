<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Контакты';
$pageDesc = 'Контакты агентства «Карамель» (учебные данные) и форма обратной связи.';
$active = 'contacts';
$user = current_user();
$errors = [];
$data = ['name' => $user['full_name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '', 'topic' => '', 'body' => ''];
$topics = ['Заказ праздника', 'Вопрос по смете', 'Выпускной в саду или школе', 'Оформление шарами', 'Сотрудничество', 'Другое'];

if (is_post()) {
    csrf_check();
    foreach (['name', 'email', 'phone', 'topic', 'body'] as $k) {
        $data[$k] = post($k);
    }
    if (post('website') !== '') {            // скрытое поле-ловушка для ботов
        redirect('contacts.php');
    }
    if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 100) {
        $errors['name'] = 'Представьтесь, пожалуйста (от 2 до 100 знаков).';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите адрес электронной почты в формате name@example.ru.';
    }
    if ($data['phone'] !== '' && !preg_match('/^[0-9+()\s-]{6,30}$/', $data['phone'])) {
        $errors['phone'] = 'Телефон: только цифры, пробелы, скобки, «+» и «-».';
    }
    if (!in_array($data['topic'], $topics, true)) {
        $errors['topic'] = 'Выберите тему обращения.';
    }
    if (mb_strlen($data['body']) < 10 || mb_strlen($data['body']) > 2000) {
        $errors['body'] = 'Сообщение должно содержать от 10 до 2000 знаков.';
    }
    if (!$errors) {
        db_insert('INSERT INTO feedback (name, email, phone, topic, body, is_done, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)',
            [$data['name'], $data['email'], $data['phone'], $data['topic'], $data['body'], date('Y-m-d H:i:s')]);
        flash('ok', 'Спасибо! Сообщение отправлено, менеджер ответит на указанную почту в течение рабочего дня.');
        redirect('contacts.php');
    }
}

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Контакты']]);
?>
<div class="container layout">
    <div class="content">
        <header class="page-head"><div>
            <h1>Контакты</h1>
            <p class="lead">Позвоните, напишите или приезжайте в офис. Данные ниже условные, так как агентство вымышленное.</p>
        </div></header>

        <div class="grid grid-2 contact-grid">
            <section class="card contact-card" aria-labelledby="c-data">
                <h2 id="c-data">Как нас найти</h2>
                <dl class="dl">
                    <dt>Адрес</dt><dd>г. Москва, ул. Учебная, д. 1, офис 5 (условный адрес)</dd>
                    <dt>Телефон</dt><dd>+7 (000) 000-00-00 (учебный номер)</dd>
                    <dt>Почта</dt><dd>info@karamel.example</dd>
                    <dt>Время работы</dt><dd>Пн–Пт 10:00–19:00, Сб 11:00–16:00, Вс — выходной</dd>
                </dl>
                <p class="muted">Праздники проводим в выходные и в будни по предварительной договорённости.</p>
            </section>
            <figure class="card map-card">
                <img class="deco" src="<?= e(asset('img/ill/map.svg')) ?>" alt="Схема проезда к офису: улица Учебная, остановка, парковка" loading="lazy">
                <figcaption>Схема проезда (условная, нарисована для учебного проекта)</figcaption>
            </figure>
        </div>

        <section class="card form-card" aria-labelledby="f-title">
            <h2 id="f-title">Напишите нам</h2>
            <form method="post" action="<?= e(url('contacts.php')) ?>#f-title" novalidate>
                <?= csrf_field() ?>
                <div class="hp" aria-hidden="true"><label>Не заполняйте это поле<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="form-grid">
                    <div class="field">
                        <label for="c-name">Ваше имя</label>
                        <input id="c-name" name="name" type="text" value="<?= old($data, 'name') ?>" maxlength="100" required<?= field_aria($errors, 'name') ?>>
                        <?= field_error($errors, 'name') ?>
                    </div>
                    <div class="field">
                        <label for="c-email">Электронная почта</label>
                        <input id="c-email" name="email" type="email" value="<?= old($data, 'email') ?>" maxlength="120" required<?= field_aria($errors, 'email') ?>>
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="field">
                        <label for="c-phone">Телефон (необязательно)</label>
                        <input id="c-phone" name="phone" type="tel" value="<?= old($data, 'phone') ?>" maxlength="30"<?= field_aria($errors, 'phone') ?>>
                        <?= field_error($errors, 'phone') ?>
                    </div>
                    <div class="field">
                        <label for="c-topic">Тема</label>
                        <select id="c-topic" name="topic" required<?= field_aria($errors, 'topic') ?>>
                            <option value="">Выберите тему</option>
                            <?php foreach ($topics as $t): ?>
                                <option value="<?= e($t) ?>"<?= $data['topic'] === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'topic') ?>
                    </div>
                </div>
                <div class="field">
                    <label for="c-body">Сообщение</label>
                    <textarea id="c-body" name="body" rows="5" maxlength="2000" required<?= field_aria($errors, 'body') ?>><?= old($data, 'body') ?></textarea>
                    <?= field_error($errors, 'body') ?>
                </div>
                <button class="btn big" type="submit">Отправить сообщение</button>
            </form>
        </section>
    </div>
    <?php render_sidebar(); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
