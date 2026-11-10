<?php
/**
 * Сценарий 2 создания пользователя: сотрудник заводит учётную запись клиента
 * из-под своей учётной записи. Клиент получает временный пароль и обязан сменить его при первом входе.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Клиенты';
$active = '';
$noIndex = true;
$showBanner = false;
$errors = [];
$data = ['full_name' => '', 'login' => '', 'email' => '', 'phone' => '', 'password' => ''];
$created = $_SESSION['created_client'] ?? null;
unset($_SESSION['created_client']);

if (is_post()) {
    csrf_check();
    foreach (array_keys($data) as $k) {
        $data[$k] = $k === 'password' ? ($_POST['password'] ?? '') : post($k);
    }
    $generated = false;
    if ($data['password'] === '') {
        $data['password'] = generate_password();
        $generated = true;
    }
    $errors = validate_user_fields($data);
    if (!$errors) {
        $newId = create_user($data, 'client', (int)$user['id'], true);
        // Пароль показываем один раз, в базе он хранится только в виде хеша
        $_SESSION['created_client'] = ['id' => $newId, 'login' => $data['login'], 'password' => $data['password'], 'name' => $data['full_name']];
        flash('ok', 'Учётная запись клиента создана.');
        redirect('staff/clients.php');
    }
    if ($generated) {
        $data['password'] = '';
    }
}

$clients = db_all("SELECT u.id, u.login, u.full_name, u.email, u.phone, u.created_at, u.must_change_password, u.is_active, c.full_name AS creator
                   FROM users u LEFT JOIN users c ON c.id = u.created_by
                   WHERE u.role = 'client' ORDER BY u.created_at DESC, u.id DESC");

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника', url('staff/index.php')], ['Клиенты']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Клиенты</h1><p class="lead">Создайте учётную запись клиента, если он не может зарегистрироваться сам, например по телефону или в офисе.</p></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <?php if ($created): ?>
        <div class="card created-box" role="status">
            <h2>Учётная запись создана</h2>
            <p>Передайте клиенту <b><?= e($created['name']) ?></b> данные для входа. Пароль показан один раз и нигде не хранится в открытом виде. При первом входе клиент должен будет сменить его на свой.</p>
            <dl class="dl"><dt>Логин</dt><dd><code><?= e($created['login']) ?></code></dd><dt>Временный пароль</dt><dd><code><?= e($created['password']) ?></code></dd></dl>
        </div>
    <?php endif; ?>

    <section class="card form-card" aria-labelledby="new-client">
        <h2 id="new-client">Новый клиент</h2>
        <?php if ($errors): ?><div class="flash flash-error" role="alert">Проверьте поля формы: есть ошибки.</div><?php endif; ?>
        <form method="post" action="<?= e(url('staff/clients.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field"><label for="n-name">Фамилия и имя</label>
                    <input id="n-name" name="full_name" type="text" value="<?= old($data, 'full_name') ?>" maxlength="120" required<?= field_aria($errors, 'full_name') ?>><?= field_error($errors, 'full_name') ?></div>
                <div class="field"><label for="n-login">Логин</label>
                    <input id="n-login" name="login" type="text" value="<?= old($data, 'login') ?>" maxlength="30" required<?= field_aria($errors, 'login') ?>><?= field_error($errors, 'login') ?></div>
                <div class="field"><label for="n-email">Электронная почта</label>
                    <input id="n-email" name="email" type="email" value="<?= old($data, 'email') ?>" maxlength="120" required<?= field_aria($errors, 'email') ?>><?= field_error($errors, 'email') ?></div>
                <div class="field"><label for="n-phone">Телефон</label>
                    <input id="n-phone" name="phone" type="tel" value="<?= old($data, 'phone') ?>" maxlength="30"<?= field_aria($errors, 'phone') ?>><?= field_error($errors, 'phone') ?></div>
                <div class="field"><label for="n-pass">Временный пароль</label>
                    <input id="n-pass" name="password" type="text" value="<?= old($data, 'password') ?>" maxlength="72" autocomplete="off"<?= field_aria($errors, 'password') ?>>
                    <span class="hint">Оставьте пустым, и сайт придумает пароль сам.</span><?= field_error($errors, 'password') ?></div>
            </div>
            <button class="btn" type="submit">Создать учётную запись</button>
        </form>
    </section>

    <section class="card block-card">
        <h2>Все клиенты (<?= count($clients) ?>)</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Имя</th><th>Логин</th><th>Почта</th><th>Создан</th><th>Кем создан</th><th>Состояние</th></tr></thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
                <tr><td><?= e($c['full_name']) ?></td><td><?= e($c['login']) ?></td><td><?= e($c['email']) ?></td><td><?= e(ru_date($c['created_at'])) ?></td>
                    <td><?= $c['creator'] ? e($c['creator']) : 'сам зарегистрировался' ?></td>
                    <td><?= !$c['is_active'] ? 'отключён' : ($c['must_change_password'] ? 'ждёт смены пароля' : 'активен') ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
