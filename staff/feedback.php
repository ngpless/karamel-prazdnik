<?php
/** Обращения из формы на странице «Контакты». */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Обращения с сайта';
$active = '';
$noIndex = true;
$showBanner = false;

if (is_post()) {
    csrf_check();
    $fid = (int)post('id', '0');
    db_exec('UPDATE feedback SET is_done = ? WHERE id = ?', [post('done') === '1' ? 1 : 0, $fid]);
    flash('ok', 'Отметка сохранена.');
    redirect('staff/feedback.php');
}
$list = db_all('SELECT * FROM feedback ORDER BY is_done, created_at DESC, id DESC');

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника', url('staff/index.php')], ['Обращения']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Обращения с сайта</h1><p class="lead">Сообщения из формы на странице «Контакты».</p></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>
    <?php if (!$list): ?><div class="card"><p class="muted">Обращений пока нет.</p></div><?php endif; ?>
    <?php foreach ($list as $f): ?>
        <article class="card fb-item<?= $f['is_done'] ? ' done' : '' ?>">
            <header>
                <h2><?= e($f['topic']) ?></h2>
                <p class="muted"><?= e($f['name']) ?> · <a href="mailto:<?= e($f['email']) ?>"><?= e($f['email']) ?></a><?= $f['phone'] !== '' ? ' · ' . e($f['phone']) : '' ?> · <?= e(ru_date($f['created_at'], true)) ?></p>
            </header>
            <p><?= nl2br(e($f['body'])) ?></p>
            <form method="post" action="<?= e(url('staff/feedback.php')) ?>">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <input type="hidden" name="done" value="<?= $f['is_done'] ? 0 : 1 ?>">
                <button class="btn small<?= $f['is_done'] ? ' ghost' : '' ?>" type="submit"><?= $f['is_done'] ? 'Вернуть в работу' : 'Отметить обработанным' ?></button>
            </form>
        </article>
    <?php endforeach; ?>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
