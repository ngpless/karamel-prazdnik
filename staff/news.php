<?php
/** Управление новостями: список, добавление, изменение, удаление. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Новости: управление';
$active = '';
$noIndex = true;
$showBanner = false;
$errors = [];
$editId = (int)get('id', '0');
$data = ['title' => '', 'slug' => '', 'summary' => '', 'body' => '', 'is_published' => '1'];

if ($editId) {
    $row = db_one('SELECT * FROM news WHERE id = ?', [$editId]);
    if ($row) {
        $data = array_map('strval', array_intersect_key($row, $data));
    } else {
        $editId = 0;
    }
}

if (is_post()) {
    csrf_check();
    if (post('action') === 'delete') {
        db_exec('DELETE FROM news WHERE id = ?', [(int)post('id', '0')]);
        flash('ok', 'Новость удалена.');
        redirect('staff/news.php');
    }
    $editId = (int)post('id', '0');
    foreach (array_keys($data) as $k) {
        $data[$k] = post($k);
    }
    $data['is_published'] = post('is_published') === '1' ? '1' : '0';
    if (mb_strlen($data['title']) < 5 || mb_strlen($data['title']) > 200) {
        $errors['title'] = 'Заголовок: от 5 до 200 знаков.';
    }
    if (mb_strlen($data['summary']) < 20 || mb_strlen($data['summary']) > 500) {
        $errors['summary'] = 'Краткое описание: от 20 до 500 знаков.';
    }
    if (mb_strlen($data['body']) < 100) {
        $errors['body'] = 'Текст новости слишком короткий (не менее 100 знаков).';
    }
    $slug = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['title']);
    if (db_val('SELECT COUNT(*) FROM news WHERE slug = ? AND id <> ?', [$slug, $editId])) {
        $errors['slug'] = 'Такой адрес уже занят другой новостью.';
    }
    if (!$errors) {
        $st = search_text($data['title'], $data['summary'], $data['body']);
        if ($editId) {
            db_exec('UPDATE news SET slug = ?, title = ?, summary = ?, body = ?, search_text = ?, is_published = ? WHERE id = ?',
                [$slug, $data['title'], $data['summary'], $data['body'], $st, (int)$data['is_published'], $editId]);
            flash('ok', 'Новость сохранена.');
        } else {
            db_insert('INSERT INTO news (slug, title, summary, body, search_text, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$slug, $data['title'], $data['summary'], $data['body'], $st, (int)$data['is_published'], date('Y-m-d H:i:s')]);
            flash('ok', 'Новость добавлена.');
        }
        redirect('staff/news.php');
    }
}

$list = db_all('SELECT id, title, is_published, created_at FROM news ORDER BY created_at DESC, id DESC');
$showForm = $editId || get('new') === '1' || $errors;

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника', url('staff/index.php')], ['Новости']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Управление новостями</h1></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <?php if ($showForm): ?>
    <section class="card form-card" aria-labelledby="news-form">
        <h2 id="news-form"><?= $editId ? 'Изменение новости' : 'Новая новость' ?></h2>
        <?php if ($errors): ?><div class="flash flash-error" role="alert">Проверьте поля формы: есть ошибки.</div><?php endif; ?>
        <form method="post" action="<?= e(url('staff/news.php')) ?>" novalidate>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $editId ?>">
            <div class="field"><label for="n-title">Заголовок</label>
                <input id="n-title" name="title" type="text" value="<?= old($data, 'title') ?>" maxlength="200" required<?= field_aria($errors, 'title') ?>><?= field_error($errors, 'title') ?></div>
            <div class="field"><label for="n-slug">Адрес (необязательно)</label>
                <input id="n-slug" name="slug" type="text" value="<?= old($data, 'slug') ?>" maxlength="80"<?= field_aria($errors, 'slug') ?>><?= field_error($errors, 'slug') ?></div>
            <div class="field"><label for="n-sum">Краткое описание</label>
                <textarea id="n-sum" name="summary" rows="2" maxlength="500" required<?= field_aria($errors, 'summary') ?>><?= old($data, 'summary') ?></textarea><?= field_error($errors, 'summary') ?></div>
            <div class="field"><label for="n-body">Текст новости</label>
                <textarea id="n-body" name="body" rows="9" required<?= field_aria($errors, 'body') ?>><?= old($data, 'body') ?></textarea><?= field_error($errors, 'body') ?></div>
            <div class="field check"><input id="n-pub" name="is_published" type="checkbox" value="1"<?= $data['is_published'] === '1' ? ' checked' : '' ?>><label for="n-pub">Опубликована на сайте</label></div>
            <button class="btn" type="submit">Сохранить</button>
            <a class="btn ghost" href="<?= e(url('staff/news.php')) ?>">Отмена</a>
        </form>
    </section>
    <?php endif; ?>

    <section class="card block-card">
        <div class="block-head"><h2>Все новости (<?= count($list) ?>)</h2><a class="btn small" href="<?= e(url('staff/news.php', ['new' => 1])) ?>">Добавить новость</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Заголовок</th><th>Дата</th><th>Показ</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach ($list as $n): ?>
                <tr><td><?= e($n['title']) ?></td><td><?= e(ru_date($n['created_at'])) ?></td><td><?= $n['is_published'] ? 'да' : 'скрыта' ?></td>
                    <td class="actions"><a class="btn small ghost" href="<?= e(url('staff/news.php', ['id' => $n['id']])) ?>">Изменить</a>
                        <form class="inline" method="post" action="<?= e(url('staff/news.php')) ?>" onsubmit="return confirm('Удалить новость безвозвратно?');">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                            <button class="btn small danger" type="submit">Удалить</button></form></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
