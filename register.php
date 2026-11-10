<?php
/** Сценарий 1 создания пользователя: клиент регистрируется сам. */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Регистрация';
$pageDesc = 'Регистрация клиента на сайте «Карамель»: личный кабинет, заявки и переписка с менеджером.';
$active = '';
$showBanner = false;

if (current_user()) {
    redirect('cabinet.php');
}

$errors = [];
$data = ['login' => '', 'full_name' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    csrf_check();
    foreach (array_keys($data) as $k) {
        $data[$k] = post($k);
    }
    $data['password'] = $_POST['password'] ?? '';
    $errors = validate_user_fields($data);
    if (($errors['password'] ?? '') === '' && $data['password'] !== ($_POST['password2'] ?? '')) {
        $errors['password2'] = 'Пароли не совпадают.';
    }
    if (post('agree') !== '1') {
        $errors['agree'] = 'Нужно согласие на обработку персональных данных.';
    }
    if (!$errors) {
        $id = create_user($data, 'client', null, false);
        login_user(['id' => $id]);
        flash('ok', 'Регистрация прошла успешно. Добро пожаловать в личный кабинет!');
        redirect('cabinet.php');
    }
}

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Регистрация']]);
?>
<section class="container narrow">
    <div class="card form-card auth-card">
        <h1>Регистрация клиента</h1>
        <p class="muted">После регистрации вы сможете оставить заявку на праздник и переписываться с менеджером.</p>
        <?php if ($errors): ?><div class="flash flash-error" role="alert">Проверьте поля формы: есть ошибки.</div><?php endif; ?>
        <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="r-name">Фамилия и имя</label>
                <input id="r-name" name="full_name" type="text" value="<?= old($data, 'full_name') ?>" maxlength="120" autocomplete="name" required<?= field_aria($errors, 'full_name') ?>>
                <?= field_error($errors, 'full_name') ?>
            </div>
            <div class="field">
                <label for="r-login">Логин</label>
                <input id="r-login" name="login" type="text" value="<?= old($data, 'login') ?>" maxlength="30" autocomplete="username" required<?= field_aria($errors, 'login') ?>>
                <span class="hint">Латинские буквы и цифры, от 3 знаков.</span>
                <?= field_error($errors, 'login') ?>
            </div>
            <div class="field">
                <label for="r-email">Электронная почта</label>
                <input id="r-email" name="email" type="email" value="<?= old($data, 'email') ?>" maxlength="120" autocomplete="email" required<?= field_aria($errors, 'email') ?>>
                <?= field_error($errors, 'email') ?>
            </div>
            <div class="field">
                <label for="r-phone">Телефон (необязательно)</label>
                <input id="r-phone" name="phone" type="tel" value="<?= old($data, 'phone') ?>" maxlength="30" autocomplete="tel"<?= field_aria($errors, 'phone') ?>>
                <?= field_error($errors, 'phone') ?>
            </div>
            <div class="field">
                <label for="r-pass">Пароль</label>
                <input id="r-pass" name="password" type="password" autocomplete="new-password" required<?= field_aria($errors, 'password') ?>>
                <span class="hint">Не короче 8 знаков, с буквами и цифрами.</span>
                <?= field_error($errors, 'password') ?>
            </div>
            <div class="field">
                <label for="r-pass2">Повторите пароль</label>
                <input id="r-pass2" name="password2" type="password" autocomplete="new-password" required<?= field_aria($errors, 'password2') ?>>
                <?= field_error($errors, 'password2') ?>
            </div>
            <div class="field check">
                <input id="r-agree" name="agree" type="checkbox" value="1"<?= post('agree') === '1' ? ' checked' : '' ?><?= field_aria($errors, 'agree') ?>>
                <label for="r-agree">Согласен на обработку персональных данных (учебный проект, данные не передаются третьим лицам)</label>
                <?= field_error($errors, 'agree') ?>
            </div>
            <button class="btn big" type="submit">Зарегистрироваться</button>
        </form>
        <p class="muted">Уже есть учётная запись? <a href="<?= e(url('login.php')) ?>">Войти</a></p>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
