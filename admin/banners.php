<?php
/** Управление баннерами: список, добавление, изменение, порядок, включение, удаление. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('admin');
$pageTitle = 'Администратор: баннеры';
$active = '';
$noIndex = true;
$showBanner = false;

$images = [];
foreach (glob(ROOT . '/assets/img/banners/*.svg') ?: [] as $f) {
    $images[] = basename($f);
}
sort($images);

$errors = [];
$editId = (int)get('id', '0');
$data = ['title' => '', 'text' => '', 'link' => 'services.php', 'button' => 'Подробнее', 'image' => $images[0] ?? 'b1.svg', 'sort_order' => '10', 'is_active' => '1'];
if ($editId) {
    $row = db_one('SELECT * FROM banners WHERE id = ?', [$editId]);
    if ($row) {
        $data = array_map('strval', array_intersect_key($row, $data));
    } else {
        $editId = 0;
    }
}

if (is_post()) {
    csrf_check();
    $act = post('action');
    $bid = (int)post('id', '0');
    if ($act === 'delete') {
        db_exec('DELETE FROM banners WHERE id = ?', [$bid]);
        flash('ok', 'Баннер удалён.');
        redirect('admin/banners.php');
    }
    if ($act === 'toggle') {
        db_exec('UPDATE banners SET is_active = 1 - is_active WHERE id = ?', [$bid]);
        flash('ok', 'Состояние баннера изменено.');
        redirect('admin/banners.php');
    }
    $editId = $bid;
    foreach (array_keys($data) as $k) {
        $data[$k] = post($k);
    }
    $data['is_active'] = post('is_active') === '1' ? '1' : '0';
    if (mb_strlen($data['title']) < 3 || mb_strlen($data['title']) > 120) {
        $errors['title'] = 'Заголовок: от 3 до 120 знаков.';
    }
    if (mb_strlen($data['text']) < 10 || mb_strlen($data['text']) > 300) {
        $errors['text'] = 'Текст: от 10 до 300 знаков.';
    }
    // Ссылка только внутри сайта: относительный адрес страницы
    if (!preg_match('~^[A-Za-z0-9_./?=&#-]{1,200}$~', $data['link']) || str_contains($data['link'], '..') || str_starts_with($data['link'], '/')) {
        $errors['link'] = 'Адрес внутри сайта, например services.php или articles.php?section=decor.';
    }
    if (mb_strlen($data['button']) < 2 || mb_strlen($data['button']) > 40) {
        $errors['button'] = 'Надпись на кнопке: от 2 до 40 знаков.';
    }
    if (!in_array($data['image'], $images, true)) {
        $errors['image'] = 'Выберите картинку из списка.';
    }
    if (!ctype_digit($data['sort_order']) || (int)$data['sort_order'] > 9999) {
        $errors['sort_order'] = 'Порядок: целое число от 0 до 9999.';
    }
    if (!$errors) {
        if ($editId) {
            db_exec('UPDATE banners SET title = ?, text = ?, link = ?, button = ?, image = ?, sort_order = ?, is_active = ? WHERE id = ?',
                [$data['title'], $data['text'], $data['link'], $data['button'], $data['image'], (int)$data['sort_order'], (int)$data['is_active'], $editId]);
            flash('ok', 'Баннер сохранён.');
        } else {
            db_insert('INSERT INTO banners (title, text, link, button, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$data['title'], $data['text'], $data['link'], $data['button'], $data['image'], (int)$data['sort_order'], (int)$data['is_active']]);
            flash('ok', 'Баннер добавлен.');
        }
        redirect('admin/banners.php');
    }
}

$list = db_all('SELECT * FROM banners ORDER BY sort_order, id');
$showForm = $editId || get('new') === '1' || $errors;

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель администратора', url('admin/index.php')], ['Баннеры']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Баннеры</h1><p class="lead">Баннеры меняются по кругу на главной странице. Здесь их можно добавить, выключить или поменять местами.</p></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <?php if ($showForm): ?>
    <section class="card form-card" aria-labelledby="b-form">
        <h2 id="b-form"><?= $editId ? 'Изменение баннера' : 'Новый баннер' ?></h2>
        <form method="post" action="<?= e(url('admin/banners.php')) ?>" novalidate>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $editId ?>">
            <div class="form-grid">
                <div class="field"><label for="b-title">Заголовок</label>
                    <input id="b-title" name="title" type="text" value="<?= old($data, 'title') ?>" maxlength="120" required<?= field_aria($errors, 'title') ?>><?= field_error($errors, 'title') ?></div>
                <div class="field"><label for="b-button">Надпись на кнопке</label>
                    <input id="b-button" name="button" type="text" value="<?= old($data, 'button') ?>" maxlength="40" required<?= field_aria($errors, 'button') ?>><?= field_error($errors, 'button') ?></div>
                <div class="field"><label for="b-link">Куда ведёт (адрес на сайте)</label>
                    <input id="b-link" name="link" type="text" value="<?= old($data, 'link') ?>" maxlength="200" required<?= field_aria($errors, 'link') ?>><?= field_error($errors, 'link') ?></div>
                <div class="field"><label for="b-image">Картинка</label>
                    <select id="b-image" name="image"<?= field_aria($errors, 'image') ?>>
                        <?php foreach ($images as $im): ?><option value="<?= e($im) ?>"<?= $data['image'] === $im ? ' selected' : '' ?>><?= e($im) ?></option><?php endforeach; ?>
                    </select><?= field_error($errors, 'image') ?></div>
                <div class="field"><label for="b-sort">Порядок показа</label>
                    <input id="b-sort" name="sort_order" type="number" min="0" max="9999" value="<?= old($data, 'sort_order') ?>"<?= field_aria($errors, 'sort_order') ?>><?= field_error($errors, 'sort_order') ?></div>
            </div>
            <div class="field"><label for="b-text">Текст баннера</label>
                <textarea id="b-text" name="text" rows="3" maxlength="300" required<?= field_aria($errors, 'text') ?>><?= old($data, 'text') ?></textarea><?= field_error($errors, 'text') ?></div>
            <div class="field check"><input id="b-act" name="is_active" type="checkbox" value="1"<?= $data['is_active'] === '1' ? ' checked' : '' ?>><label for="b-act">Показывать на сайте</label></div>
            <button class="btn" type="submit">Сохранить</button>
            <a class="btn ghost" href="<?= e(url('admin/banners.php')) ?>">Отмена</a>
        </form>
    </section>
    <?php endif; ?>

    <section class="card block-card">
        <div class="block-head"><h2>Все баннеры (<?= count($list) ?>)</h2><a class="btn small" href="<?= e(url('admin/banners.php', ['new' => 1])) ?>">Добавить баннер</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Порядок</th><th>Баннер</th><th>Ссылка</th><th>Показ</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach ($list as $b): ?>
                <tr><td><?= (int)$b['sort_order'] ?></td>
                    <td><b><?= e($b['title']) ?></b><br><small class="muted"><?= e($b['text']) ?></small></td>
                    <td><?= e($b['link']) ?></td>
                    <td><?= $b['is_active'] ? 'показывается' : 'выключен' ?></td>
                    <td class="actions"><a class="btn small ghost" href="<?= e(url('admin/banners.php', ['id' => $b['id']])) ?>">Изменить</a>
                        <form class="inline" method="post" action="<?= e(url('admin/banners.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button class="btn small ghost" type="submit"><?= $b['is_active'] ? 'Выключить' : 'Включить' ?></button></form>
                        <form class="inline" method="post" action="<?= e(url('admin/banners.php')) ?>" onsubmit="return confirm('Удалить баннер?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button class="btn small danger" type="submit">Удалить</button></form></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
