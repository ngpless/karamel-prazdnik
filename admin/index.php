<?php
/** Панель администратора: пользователи и роли. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('admin');
$pageTitle = 'Администратор: пользователи и роли';
$active = '';
$noIndex = true;
$showBanner = false;
$roles = roles_info();
$errors = [];
$data = ['full_name' => '', 'login' => '', 'email' => '', 'phone' => '', 'password' => '', 'role' => 'manager'];

if (is_post()) {
    csrf_check();
    $act = post('action');
    $uid = (int)post('id', '0');
    $target = $uid ? db_one('SELECT id, role, is_active, full_name FROM users WHERE id = ?', [$uid]) : null;

    if ($act === 'role' && $target) {
        $role = post('role');
        if (!isset($roles[$role])) {
            flash('error', 'Неизвестная роль.');
        } elseif ($target['id'] === $user['id'] || (int)$target['id'] === (int)$user['id']) {
            flash('error', 'Свою роль менять нельзя: так администратор не потеряет доступ.');
        } else {
            db_exec('UPDATE users SET role = ? WHERE id = ?', [$role, $uid]);
            flash('ok', 'Роль пользователя «' . $target['full_name'] . '» изменена на «' . $roles[$role]['title'] . '».');
        }
        redirect('admin/index.php');
    }
    if ($act === 'active' && $target) {
        if ((int)$target['id'] === (int)$user['id']) {
            flash('error', 'Нельзя отключить свою учётную запись.');
        } else {
            $new = (int)$target['is_active'] === 1 ? 0 : 1;
            db_exec('UPDATE users SET is_active = ? WHERE id = ?', [$new, $uid]);
            flash('ok', $new ? 'Учётная запись включена.' : 'Учётная запись отключена, вход закрыт.');
        }
        redirect('admin/index.php');
    }
    if ($act === 'create') {
        foreach (array_keys($data) as $k) {
            $data[$k] = $k === 'password' ? ($_POST['password'] ?? '') : post($k);
        }
        if (!isset($roles[$data['role']])) {
            $errors['role'] = 'Выберите роль.';
        }
        $errors += validate_user_fields($data);
        if (!$errors) {
            create_user($data, $data['role'], (int)$user['id'], true);
            flash('ok', 'Пользователь «' . $data['login'] . '» создан с ролью «' . $roles[$data['role']]['title'] . '». При первом входе он сменит пароль.');
            redirect('admin/index.php');
        }
    }
}

$users = db_all('SELECT u.id, u.login, u.full_name, u.email, u.role, u.is_active, u.created_at, c.full_name AS creator
                 FROM users u LEFT JOIN users c ON c.id = u.created_by ORDER BY u.id');
$counts = [];
foreach ($users as $u) {
    $counts[$u['role']] = ($counts[$u['role']] ?? 0) + 1;
}

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель администратора']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Панель администратора</h1><p class="lead">Пользователи, роли и права доступа.</p></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <section class="card block-card" aria-labelledby="roles-h">
        <h2 id="roles-h">Роли и права</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Роль</th><th>Код</th><th>Права</th><th>Пользователей</th></tr></thead>
            <tbody>
            <tr><td>Посетитель</td><td>—</td><td>Читает статьи и новости, ищет по сайту, пишет через форму «Контакты», может зарегистрироваться</td><td>без учётной записи</td></tr>
            <?php foreach ($roles as $code => $r): ?>
                <tr><td><b><?= e($r['title']) ?></b></td><td><code><?= e($code) ?></code></td><td><?= e($r['rights']) ?></td><td><?= (int)($counts[$code] ?? 0) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>

    <section class="card block-card" aria-labelledby="users-h">
        <h2 id="users-h">Пользователи (<?= count($users) ?>)</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Имя</th><th>Логин</th><th>Роль</th><th>Создан</th><th>Состояние</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): $self = (int)$u['id'] === (int)$user['id']; ?>
                <tr>
                    <td><?= e($u['full_name']) ?><br><small class="muted"><?= e($u['email']) ?></small></td>
                    <td><?= e($u['login']) ?></td>
                    <td>
                        <form class="inline-role" method="post" action="<?= e(url('admin/index.php')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <label class="sr-only" for="role-<?= (int)$u['id'] ?>">Роль пользователя <?= e($u['login']) ?></label>
                            <select id="role-<?= (int)$u['id'] ?>" name="role"<?= $self ? ' disabled' : '' ?>>
                                <?php foreach ($roles as $code => $r): ?><option value="<?= e($code) ?>"<?= $u['role'] === $code ? ' selected' : '' ?>><?= e($r['title']) ?></option><?php endforeach; ?>
                            </select>
                            <?php if (!$self): ?><button class="btn small ghost" type="submit">Назначить</button><?php endif; ?>
                        </form>
                    </td>
                    <td><?= e(ru_date($u['created_at'])) ?><br><small class="muted"><?= $u['creator'] ? 'создал: ' . e($u['creator']) : 'регистрация' ?></small></td>
                    <td><?= $u['is_active'] ? 'активен' : 'отключён' ?></td>
                    <td>
                        <?php if (!$self): ?>
                        <form class="inline" method="post" action="<?= e(url('admin/index.php')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="action" value="active"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn small <?= $u['is_active'] ? 'danger' : '' ?>" type="submit"><?= $u['is_active'] ? 'Отключить' : 'Включить' ?></button>
                        </form>
                        <?php else: ?><span class="muted">это вы</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>

    <section class="card form-card" aria-labelledby="mk-h">
        <h2 id="mk-h">Создать пользователя</h2>
        <?php if ($errors): ?><div class="flash flash-error" role="alert">Проверьте поля формы: есть ошибки.</div><?php endif; ?>
        <form method="post" action="<?= e(url('admin/index.php')) ?>#mk-h" novalidate>
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <div class="form-grid">
                <div class="field"><label for="u-name">Фамилия и имя</label>
                    <input id="u-name" name="full_name" type="text" value="<?= old($data, 'full_name') ?>" required<?= field_aria($errors, 'full_name') ?>><?= field_error($errors, 'full_name') ?></div>
                <div class="field"><label for="u-login">Логин</label>
                    <input id="u-login" name="login" type="text" value="<?= old($data, 'login') ?>" required<?= field_aria($errors, 'login') ?>><?= field_error($errors, 'login') ?></div>
                <div class="field"><label for="u-email">Электронная почта</label>
                    <input id="u-email" name="email" type="email" value="<?= old($data, 'email') ?>" required<?= field_aria($errors, 'email') ?>><?= field_error($errors, 'email') ?></div>
                <div class="field"><label for="u-phone">Телефон</label>
                    <input id="u-phone" name="phone" type="tel" value="<?= old($data, 'phone') ?>"<?= field_aria($errors, 'phone') ?>><?= field_error($errors, 'phone') ?></div>
                <div class="field"><label for="u-pass">Временный пароль</label>
                    <input id="u-pass" name="password" type="text" value="<?= old($data, 'password') ?>" autocomplete="off" required<?= field_aria($errors, 'password') ?>>
                    <span class="hint">Не короче 8 знаков, буквы и цифры.</span><?= field_error($errors, 'password') ?></div>
                <div class="field"><label for="u-role">Роль</label>
                    <select id="u-role" name="role"<?= field_aria($errors, 'role') ?>>
                        <?php foreach ($roles as $code => $r): ?><option value="<?= e($code) ?>"<?= $data['role'] === $code ? ' selected' : '' ?>><?= e($r['title']) ?></option><?php endforeach; ?>
                    </select><?= field_error($errors, 'role') ?></div>
            </div>
            <button class="btn" type="submit">Создать</button>
        </form>
    </section>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
