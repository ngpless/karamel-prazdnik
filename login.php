<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Вход';
$pageDesc = 'Вход в личный кабинет сайта «Карамель».';
$active = '';
$showBanner = false;
$next = get('next') !== '' ? get('next') : post('next');

if (current_user()) {
    redirect('cabinet.php');
}

$error = '';
$login = '';
if (is_post()) {
    csrf_check();
    $login = post('login');
    [$u, $error] = attempt_login($login, $_POST['password'] ?? '');
    if ($u) {
        login_user($u);
        flash('ok', 'Добро пожаловать, ' . $u['full_name'] . '!');
        if ($next !== '') {
            header('Location: ' . safe_local($next));
            exit;
        }
        redirect($u['role'] === 'admin' ? 'admin/index.php' : ($u['role'] === 'manager' ? 'staff/index.php' : 'cabinet.php'));
    }
}

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Вход']]);
?>
<section class="container narrow">
    <div class="card form-card auth-card">
        <h1>Вход в личный кабинет</h1>
        <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('login.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="field">
                <label for="l-login">Логин или электронная почта</label>
                <input id="l-login" name="login" type="text" value="<?= e($login) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="l-pass">Пароль</label>
                <input id="l-pass" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn big" type="submit">Войти</button>
        </form>
        <p class="muted">Нет учётной записи? <a href="<?= e(url('register.php')) ?>">Зарегистрируйтесь</a>. Если вам выдали временный пароль в агентстве, введите его здесь: сайт предложит выбрать свой.</p>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
