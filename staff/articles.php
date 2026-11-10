<?php
/** Управление статьями: список, добавление, изменение, удаление. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Статьи: управление';
$active = '';
$noIndex = true;
$showBanner = false;
$sections = db_all('SELECT id, title FROM sections ORDER BY sort_order, id');
$errors = [];
$editId = (int)get('id', '0');
$data = ['section_id' => '', 'title' => '', 'slug' => '', 'summary' => '', 'body' => '', 'is_published' => '1'];

if ($editId) {
    $row = db_one('SELECT * FROM articles WHERE id = ?', [$editId]);
    if ($row) {
        $data = array_map('strval', array_intersect_key($row, $data));
    } else {
        $editId = 0;
    }
}

if (is_post()) {
    csrf_check();
    $act = post('action');
    if ($act === 'delete') {
        $did = (int)post('id', '0');
        db_exec('DELETE FROM articles WHERE id = ?', [$did]);
        flash('ok', 'Статья удалена.');
        redirect('staff/articles.php');
    }
    $editId = (int)post('id', '0');
    foreach (array_keys($data) as $k) {
        $data[$k] = post($k);
    }
    $data['is_published'] = post('is_published') === '1' ? '1' : '0';
    if (!db_val('SELECT id FROM sections WHERE id = ?', [(int)$data['section_id']])) {
        $errors['section_id'] = 'Выберите раздел.';
    }
    if (mb_strlen($data['title']) < 5 || mb_strlen($data['title']) > 200) {
        $errors['title'] = 'Заголовок: от 5 до 200 знаков.';
    }
    if (mb_strlen($data['summary']) < 20 || mb_strlen($data['summary']) > 500) {
        $errors['summary'] = 'Краткое описание: от 20 до 500 знаков.';
    }
    if (mb_strlen($data['body']) < 200) {
        $errors['body'] = 'Текст статьи слишком короткий (не менее 200 знаков).';
    }
    $slug = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['title']);
    if (db_val('SELECT COUNT(*) FROM articles WHERE slug = ? AND id <> ?', [$slug, $editId])) {
        $errors['slug'] = 'Такой адрес уже занят другой статьёй.';
    }
    if (!$errors) {
        $st = search_text($data['title'], $data['summary'], $data['body']);
        $now = date('Y-m-d H:i:s');
        if ($editId) {
            db_exec('UPDATE articles SET section_id = ?, slug = ?, title = ?, summary = ?, body = ?, search_text = ?, is_published = ?, updated_at = ? WHERE id = ?',
                [(int)$data['section_id'], $slug, $data['title'], $data['summary'], $data['body'], $st, (int)$data['is_published'], $now, $editId]);
            flash('ok', 'Статья сохранена.');
        } else {
            db_insert('INSERT INTO articles (section_id, slug, title, summary, body, search_text, author, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [(int)$data['section_id'], $slug, $data['title'], $data['summary'], $data['body'], $st, $user['full_name'], (int)$data['is_published'], $now, $now]);
            flash('ok', 'Статья добавлена.');
        }
        redirect('staff/articles.php');
    }
}

$list = db_all('SELECT a.id, a.title, a.is_published, a.created_at, s.title AS section_title FROM articles a JOIN sections s ON s.id = a.section_id ORDER BY s.sort_order, a.created_at, a.id');
$showForm = $editId || get('new') === '1' || $errors;

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника', url('staff/index.php')], ['Статьи']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Управление статьями</h1></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <?php if ($showForm): ?>
    <section class="card form-card" aria-labelledby="art-form">
        <h2 id="art-form"><?= $editId ? 'Изменение статьи' : 'Новая статья' ?></h2>
        <?php if ($errors): ?><div class="flash flash-error" role="alert">Проверьте поля формы: есть ошибки.</div><?php endif; ?>
        <form method="post" action="<?= e(url('staff/articles.php')) ?>" novalidate>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $editId ?>">
            <div class="form-grid">
                <div class="field"><label for="a-sec">Раздел</label>
                    <select id="a-sec" name="section_id" required<?= field_aria($errors, 'section_id') ?>>
                        <option value="">Выберите раздел</option>
                        <?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>"<?= $data['section_id'] === (string)$s['id'] ? ' selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?>
                    </select><?= field_error($errors, 'section_id') ?></div>
                <div class="field"><label for="a-slug">Адрес (необязательно)</label>
                    <input id="a-slug" name="slug" type="text" value="<?= old($data, 'slug') ?>" maxlength="80"<?= field_aria($errors, 'slug') ?>>
                    <span class="hint">Латиницей; если пусто, возьмём из заголовка.</span><?= field_error($errors, 'slug') ?></div>
            </div>
            <div class="field"><label for="a-title">Заголовок</label>
                <input id="a-title" name="title" type="text" value="<?= old($data, 'title') ?>" maxlength="200" required<?= field_aria($errors, 'title') ?>><?= field_error($errors, 'title') ?></div>
            <div class="field"><label for="a-sum">Краткое описание</label>
                <textarea id="a-sum" name="summary" rows="2" maxlength="500" required<?= field_aria($errors, 'summary') ?>><?= old($data, 'summary') ?></textarea><?= field_error($errors, 'summary') ?></div>
            <div class="field"><label for="a-body">Текст статьи</label>
                <textarea id="a-body" name="body" rows="14" required<?= field_aria($errors, 'body') ?>><?= old($data, 'body') ?></textarea>
                <span class="hint">Абзацы разделяйте пустой строкой. «## Заголовок» — подзаголовок, строки с «- » — список.</span><?= field_error($errors, 'body') ?></div>
            <div class="field check"><input id="a-pub" name="is_published" type="checkbox" value="1"<?= $data['is_published'] === '1' ? ' checked' : '' ?>><label for="a-pub">Опубликована на сайте</label></div>
            <button class="btn" type="submit">Сохранить</button>
            <a class="btn ghost" href="<?= e(url('staff/articles.php')) ?>">Отмена</a>
        </form>
    </section>
    <?php endif; ?>

    <section class="card block-card">
        <div class="block-head"><h2>Все статьи (<?= count($list) ?>)</h2><a class="btn small" href="<?= e(url('staff/articles.php', ['new' => 1])) ?>">Добавить статью</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Заголовок</th><th>Раздел</th><th>Дата</th><th>Показ</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach ($list as $a): ?>
                <tr><td><?= e($a['title']) ?></td><td><?= e($a['section_title']) ?></td><td><?= e(ru_date($a['created_at'])) ?></td><td><?= $a['is_published'] ? 'да' : 'скрыта' ?></td>
                    <td class="actions"><a class="btn small ghost" href="<?= e(url('staff/articles.php', ['id' => $a['id']])) ?>">Изменить</a>
                        <form class="inline" method="post" action="<?= e(url('staff/articles.php')) ?>" onsubmit="return confirm('Удалить статью безвозвратно?');">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button class="btn small danger" type="submit">Удалить</button></form></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
