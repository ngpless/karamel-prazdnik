<?php
/** Заявка на праздник (только для клиента). */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$user = require_role('client');
$pageTitle = 'Заявка на праздник';
$pageDesc = 'Форма заявки на организацию детского или семейного праздника.';
$active = '';
$showBanner = false;
$services = db_all('SELECT id, title, price_from FROM services ORDER BY sort_order, id');
$errors = [];
$data = ['service_id' => get('service'), 'event_date' => '', 'guests' => '', 'child_age' => '', 'place' => '', 'budget' => '', 'comment' => ''];

if (is_post()) {
    csrf_check();
    foreach (array_keys($data) as $k) {
        $data[$k] = post($k);
    }
    $ids = array_map(fn($s) => (string)$s['id'], $services);
    if (!in_array($data['service_id'], $ids, true)) {
        $errors['service_id'] = 'Выберите услугу из списка.';
    }
    $d = DateTime::createFromFormat('Y-m-d', $data['event_date']);
    if (!$d || $d->format('Y-m-d') !== $data['event_date']) {
        $errors['event_date'] = 'Укажите дату праздника.';
    } elseif ($data['event_date'] <= date('Y-m-d')) {
        $errors['event_date'] = 'Дата должна быть позже сегодняшней: подготовка занимает не менее суток.';
    } elseif ($data['event_date'] > date('Y-m-d', strtotime('+2 years'))) {
        $errors['event_date'] = 'Слишком далёкая дата, выберите её в пределах двух лет.';
    }
    if (!ctype_digit($data['guests']) || (int)$data['guests'] < 1 || (int)$data['guests'] > 300) {
        $errors['guests'] = 'Число гостей: целое число от 1 до 300.';
    }
    if (mb_strlen($data['child_age']) > 20) {
        $errors['child_age'] = 'Не более 20 знаков.';
    }
    if (mb_strlen($data['place']) > 200) {
        $errors['place'] = 'Не более 200 знаков.';
    }
    if ($data['budget'] !== '' && (!ctype_digit($data['budget']) || (int)$data['budget'] > 10000000)) {
        $errors['budget'] = 'Бюджет: целое число рублей без пробелов.';
    }
    if (mb_strlen($data['comment']) > 2000) {
        $errors['comment'] = 'Комментарий не длиннее 2000 знаков.';
    }
    if (!$errors) {
        $id = db_insert('INSERT INTO requests (client_id, service_id, event_date, guests, child_age, place, budget, comment, status, manager_note, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$user['id'], (int)$data['service_id'], $data['event_date'], (int)$data['guests'], $data['child_age'], $data['place'],
                $data['budget'] === '' ? null : (int)$data['budget'], $data['comment'], 'new', '', date('Y-m-d H:i:s')]);
        flash('ok', 'Заявка № ' . $id . ' принята. Менеджер свяжется с вами в личном кабинете или по почте.');
        redirect('cabinet.php');
    }
}

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Личный кабинет', url('cabinet.php')], ['Заявка на праздник']]);
?>
<div class="container narrow-wide">
    <header class="page-head"><div>
        <h1>Заявка на праздник</h1>
        <p class="lead">Заполните форму, менеджер уточнит детали и пришлёт смету.</p>
    </div></header>
    <section class="card form-card">
        <?php if ($errors): ?><div class="flash flash-error" role="alert">Проверьте поля формы: есть ошибки.</div><?php endif; ?>
        <form method="post" action="<?= e(url('request.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="q-service">Услуга</label>
                    <select id="q-service" name="service_id" required<?= field_aria($errors, 'service_id') ?>>
                        <option value="">Выберите услугу</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"<?= $data['service_id'] === (string)$s['id'] ? ' selected' : '' ?>><?= e($s['title']) ?> (от <?= e(money((int)$s['price_from'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'service_id') ?>
                </div>
                <div class="field">
                    <label for="q-date">Дата праздника</label>
                    <input id="q-date" name="event_date" type="date" value="<?= old($data, 'event_date') ?>" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" required<?= field_aria($errors, 'event_date') ?>>
                    <?= field_error($errors, 'event_date') ?>
                </div>
                <div class="field">
                    <label for="q-guests">Число гостей</label>
                    <input id="q-guests" name="guests" type="number" min="1" max="300" value="<?= old($data, 'guests') ?>" required<?= field_aria($errors, 'guests') ?>>
                    <?= field_error($errors, 'guests') ?>
                </div>
                <div class="field">
                    <label for="q-age">Возраст детей (необязательно)</label>
                    <input id="q-age" name="child_age" type="text" value="<?= old($data, 'child_age') ?>" maxlength="20" placeholder="например, 6–7 лет"<?= field_aria($errors, 'child_age') ?>>
                    <?= field_error($errors, 'child_age') ?>
                </div>
                <div class="field">
                    <label for="q-place">Место проведения</label>
                    <input id="q-place" name="place" type="text" value="<?= old($data, 'place') ?>" maxlength="200" placeholder="дом, детский сад, кафе, школа"<?= field_aria($errors, 'place') ?>>
                    <?= field_error($errors, 'place') ?>
                </div>
                <div class="field">
                    <label for="q-budget">Бюджет, ₽ (необязательно)</label>
                    <input id="q-budget" name="budget" type="number" min="0" step="500" value="<?= old($data, 'budget') ?>"<?= field_aria($errors, 'budget') ?>>
                    <?= field_error($errors, 'budget') ?>
                </div>
            </div>
            <div class="field">
                <label for="q-comment">Пожелания</label>
                <textarea id="q-comment" name="comment" rows="4" maxlength="2000"<?= field_aria($errors, 'comment') ?>><?= old($data, 'comment') ?></textarea>
                <?= field_error($errors, 'comment') ?>
            </div>
            <button class="btn big" type="submit">Отправить заявку</button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
