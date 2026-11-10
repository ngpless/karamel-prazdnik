<?php
/** Личный кабинет: профиль, смена пароля, заявки и сообщения клиента. */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$user = require_role('client', 'manager', 'admin');
$pageTitle = 'Личный кабинет';
$pageDesc = 'Личный кабинет: профиль, заявки на праздник и переписка с менеджером.';
$active = '';
$showBanner = false;
$errors = [];
$perr = [];

if (is_post()) {
    csrf_check();
    $action = post('action');
    if ($action === 'profile') {
        $d = ['login' => $user['login'], 'full_name' => post('full_name'), 'email' => post('email'), 'phone' => post('phone')];
        $errors = validate_user_fields($d, false, (int)$user['id']);
        if (!$errors) {
            db_exec('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?', [$d['full_name'], $d['email'], $d['phone'], $user['id']]);
            flash('ok', 'Профиль сохранён.');
            redirect('cabinet.php');
        }
        $user = array_merge($user, $d);
    } elseif ($action === 'password') {
        $row = db_one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['old_password'] ?? '', (string)$row['password_hash'])) {
            $perr['old_password'] = 'Текущий пароль указан неверно.';
        }
        $p = password_problem($new);
        if ($p !== '') {
            $perr['new_password'] = $p;
        } elseif ($new !== ($_POST['new_password2'] ?? '')) {
            $perr['new_password2'] = 'Пароли не совпадают.';
        } elseif (($_POST['old_password'] ?? '') === $new) {
            $perr['new_password'] = 'Новый пароль должен отличаться от текущего.';
        }
        if (!$perr) {
            db_exec('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('ok', 'Пароль изменён.');
            redirect('cabinet.php');
        }
    }
}

$mustChange = (int)$user['must_change_password'] === 1;
$requests = $unreadMsgs = [];
$lastMsg = null;
if ($user['role'] === 'client') {
    $requests = db_all('SELECT r.*, s.title AS service_title FROM requests r JOIN services s ON s.id = r.service_id
                        WHERE r.client_id = ? ORDER BY r.created_at DESC, r.id DESC LIMIT 5', [$user['id']]);
    $lastMsg = db_one('SELECT * FROM messages WHERE client_id = ? ORDER BY created_at DESC, id DESC LIMIT 1', [$user['id']]);
}
$unreadCount = $user['role'] === 'client'
    ? (int)db_val('SELECT COUNT(*) FROM messages WHERE client_id = ? AND from_staff = 1 AND is_read = 0', [$user['id']]) : 0;
$statuses = request_statuses();

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Личный кабинет']]);
?>
<div class="container">
    <header class="page-head"><div>
        <h1>Личный кабинет</h1>
        <p class="lead">Здравствуйте, <?= e($user['full_name']) ?>! Роль: <b><?= e(roles_info()[$user['role']]['title']) ?></b>.</p>
    </div></header>

    <?php if ($mustChange): ?>
        <div class="flash flash-warn" role="alert">Вам выдан временный пароль. Пожалуйста, задайте свой пароль в форме ниже, иначе остальные разделы сайта будут недоступны.</div>
    <?php endif; ?>

    <?php if (!$mustChange): ?>
    <div class="grid grid-3 dash">
        <?php if ($user['role'] === 'client'): ?>
            <a class="card dash-tile" href="<?= e(url('request.php')) ?>"><b>Заявка на праздник</b><span>Заполните форму, менеджер подтвердит детали</span></a>
            <a class="card dash-tile" href="<?= e(url('messages.php')) ?>"><b>Переписка с менеджером<?= $unreadCount ? ' <span class="badge">' . $unreadCount . '</span>' : '' ?></b><span><?= $unreadCount ? 'Есть новые ответы' : 'Задайте вопрос, ответ придёт сюда же' ?></span></a>
            <a class="card dash-tile" href="<?= e(url('services.php')) ?>"><b>Каталог услуг</b><span>Форматы праздников и цены</span></a>
        <?php else: ?>
            <a class="card dash-tile" href="<?= e(url('staff/index.php')) ?>"><b>Панель сотрудника</b><span>Заявки, сообщения, статьи, новости</span></a>
            <?php if ($user['role'] === 'admin'): ?>
                <a class="card dash-tile" href="<?= e(url('admin/index.php')) ?>"><b>Панель администратора</b><span>Пользователи, роли, баннеры</span></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'client' && !$mustChange): ?>
        <section class="card block-card">
            <h2>Мои заявки</h2>
            <?php if (!$requests): ?>
                <p class="muted">Заявок пока нет. <a href="<?= e(url('request.php')) ?>">Оставить первую заявку</a>.</p>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>№</th><th>Услуга</th><th>Дата праздника</th><th>Гостей</th><th>Статус</th></tr></thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr><td><?= (int)$r['id'] ?></td><td><?= e($r['service_title']) ?></td><td><?= e(ru_date($r['event_date'])) ?></td><td><?= (int)$r['guests'] ?></td>
                            <td><span class="status st-<?= e($r['status']) ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="grid grid-2">
        <section class="card form-card" aria-labelledby="prof">
            <h2 id="prof">Профиль</h2>
            <form method="post" action="<?= e(url('cabinet.php')) ?>" novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <div class="field"><label for="p-login">Логин</label><input id="p-login" type="text" value="<?= e($user['login']) ?>" disabled></div>
                <div class="field"><label for="p-name">Фамилия и имя</label>
                    <input id="p-name" name="full_name" type="text" value="<?= e($user['full_name']) ?>" maxlength="120" required<?= field_aria($errors, 'full_name') ?>><?= field_error($errors, 'full_name') ?></div>
                <div class="field"><label for="p-email">Электронная почта</label>
                    <input id="p-email" name="email" type="email" value="<?= e($user['email']) ?>" maxlength="120" required<?= field_aria($errors, 'email') ?>><?= field_error($errors, 'email') ?></div>
                <div class="field"><label for="p-phone">Телефон</label>
                    <input id="p-phone" name="phone" type="tel" value="<?= e($user['phone']) ?>" maxlength="30"<?= field_aria($errors, 'phone') ?>><?= field_error($errors, 'phone') ?></div>
                <button class="btn" type="submit">Сохранить профиль</button>
            </form>
        </section>
        <section class="card form-card" aria-labelledby="pw">
            <h2 id="pw"><?= $mustChange ? 'Задайте свой пароль' : 'Смена пароля' ?></h2>
            <form method="post" action="<?= e(url('cabinet.php')) ?>#pw" novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <div class="field"><label for="pw-old"><?= $mustChange ? 'Временный пароль' : 'Текущий пароль' ?></label>
                    <input id="pw-old" name="old_password" type="password" autocomplete="current-password" required<?= field_aria($perr, 'old_password') ?>><?= field_error($perr, 'old_password') ?></div>
                <div class="field"><label for="pw-new">Новый пароль</label>
                    <input id="pw-new" name="new_password" type="password" autocomplete="new-password" required<?= field_aria($perr, 'new_password') ?>>
                    <span class="hint">Не короче 8 знаков, с буквами и цифрами.</span><?= field_error($perr, 'new_password') ?></div>
                <div class="field"><label for="pw-new2">Повторите новый пароль</label>
                    <input id="pw-new2" name="new_password2" type="password" autocomplete="new-password" required<?= field_aria($perr, 'new_password2') ?>><?= field_error($perr, 'new_password2') ?></div>
                <button class="btn" type="submit">Сменить пароль</button>
            </form>
        </section>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
