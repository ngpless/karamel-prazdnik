<?php
/**
 * Учётные записи: вход, роли, проверка прав, политика паролей.
 * Пароли хранятся только в виде хеша (password_hash).
 */
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;     // неудачных попыток подряд
const LOGIN_LOCK_MINUTES = 10;    // блокировка входа

function current_user(): ?array
{
    static $loaded = false, $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    if (!empty($_SESSION['uid'])) {
        $row = db_one('SELECT id, login, email, full_name, phone, role, is_active, must_change_password FROM users WHERE id = ?', [(int)$_SESSION['uid']]);
        if ($row && (int)$row['is_active'] === 1) {
            $user = $row;
        } else {
            unset($_SESSION['uid']);
        }
    }
    return $user;
}

function is_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

/** Сотрудник сайта: менеджер или администратор. */
function is_staff(): bool
{
    return is_role('manager', 'admin');
}

/**
 * Требует одну из ролей. Гость отправляется на страницу входа, вошедший
 * с неподходящей ролью получает страницу «Доступ запрещён» (403).
 */
function require_role(string ...$roles): array
{
    $u = current_user();
    if ($u === null) {
        flash('warn', 'Для этой страницы нужно войти в свою учётную запись.');
        redirect('login.php', ['next' => $_SERVER['REQUEST_URI'] ?? '']);
    }
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        $pageTitle = 'Доступ запрещён';
        $noIndex = true;
        require ROOT . '/inc/header.php';
        echo '<section class="container narrow"><div class="card notice-page"><h1>Доступ запрещён</h1>'
            . '<p>У вашей учётной записи («' . e(roles_info()[$u['role']]['title'] ?? $u['role']) . '») нет прав для просмотра этой страницы.</p>'
            . '<p><a class="btn" href="' . e(url('cabinet.php')) . '">Перейти в личный кабинет</a></p></div></section>';
        require ROOT . '/inc/footer.php';
        exit;
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}

/**
 * Попытка входа. Возвращает [пользователь|null, сообщение об ошибке].
 * Учитывает блокировку после серии неверных паролей.
 */
function attempt_login(string $login, string $password): array
{
    $u = db_one('SELECT * FROM users WHERE login = ? OR email = ?', [$login, $login]);
    if (!$u) {
        // Время ответа выравниваем, чтобы не выдавать, существует ли логин
        password_verify($password, '$2y$10$abcdefghijklmnopqrstuuOQ4Zq8o0F1m2m5K0v5mYQnXhHk4w8bC');
        return [null, 'Неверный логин или пароль.'];
    }
    if (!empty($u['locked_until']) && strtotime((string)$u['locked_until']) > time()) {
        $min = (int)ceil((strtotime((string)$u['locked_until']) - time()) / 60);
        return [null, 'Слишком много неудачных попыток. Вход временно заблокирован, повторите через ' . $min . ' мин.'];
    }
    if (!password_verify($password, (string)$u['password_hash'])) {
        $n = (int)$u['failed_attempts'] + 1;
        if ($n >= LOGIN_MAX_ATTEMPTS) {
            db_exec('UPDATE users SET failed_attempts = 0, locked_until = ? WHERE id = ?',
                [date('Y-m-d H:i:s', time() + LOGIN_LOCK_MINUTES * 60), $u['id']]);
            return [null, 'Слишком много неудачных попыток. Вход заблокирован на ' . LOGIN_LOCK_MINUTES . ' минут.'];
        }
        db_exec('UPDATE users SET failed_attempts = ? WHERE id = ?', [$n, $u['id']]);
        return [null, 'Неверный логин или пароль.'];
    }
    if ((int)$u['is_active'] !== 1) {
        return [null, 'Эта учётная запись отключена администратором.'];
    }
    db_exec('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?', [$u['id']]);
    return [$u, ''];
}

/** Политика паролей: от 8 знаков, есть буква и цифра. Возвращает текст ошибки или ''. */
function password_problem(string $pass): string
{
    if (mb_strlen($pass) < 8) {
        return 'Пароль должен быть не короче 8 знаков.';
    }
    if (!preg_match('/\p{L}/u', $pass) || !preg_match('/\d/', $pass)) {
        return 'В пароле должны быть и буквы, и цифры.';
    }
    if (mb_strlen($pass) > 72) {
        return 'Пароль слишком длинный (не более 72 знаков).';
    }
    return '';
}

/** Случайный пароль, удовлетворяющий политике (без похожих знаков). */
function generate_password(int $len = 10): string
{
    $letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $digits = '23456789';
    $all = $letters . $digits;
    $p = $letters[random_int(0, strlen($letters) - 1)] . $digits[random_int(0, strlen($digits) - 1)];
    for ($i = 2; $i < $len; $i++) {
        $p .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($p);
}

/**
 * Проверка полей учётной записи (регистрация, создание сотрудником, админка).
 * Возвращает массив ошибок по полям.
 */
function validate_user_fields(array $d, bool $needPassword = true, ?int $exceptId = null): array
{
    $err = [];
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_.-]{2,29}$/', $d['login'] ?? '')) {
        $err['login'] = 'Логин: 3–30 знаков, латинские буквы, цифры, «_», «.» или «-»; первая — буква.';
    } elseif (db_val('SELECT COUNT(*) FROM users WHERE login = ? AND id <> ?', [$d['login'], $exceptId ?? 0])) {
        $err['login'] = 'Такой логин уже занят.';
    }
    $name = $d['full_name'] ?? '';
    if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
        $err['full_name'] = 'Укажите фамилию и имя (от 3 до 120 знаков).';
    }
    $email = $d['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        $err['email'] = 'Введите адрес электронной почты в формате name@example.ru.';
    } elseif (db_val('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $exceptId ?? 0])) {
        $err['email'] = 'Этот адрес уже использован.';
    }
    $phone = $d['phone'] ?? '';
    if ($phone !== '' && !preg_match('/^[0-9+()\s-]{6,30}$/', $phone)) {
        $err['phone'] = 'Телефон: только цифры, пробелы, скобки, «+» и «-».';
    }
    if ($needPassword) {
        $p = password_problem($d['password'] ?? '');
        if ($p !== '') {
            $err['password'] = $p;
        }
    }
    return $err;
}

function create_user(array $d, string $role, ?int $createdBy, bool $mustChange): int
{
    return db_insert(
        'INSERT INTO users (login, email, password_hash, full_name, phone, role, is_active, must_change_password, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)',
        [$d['login'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), $d['full_name'], $d['phone'] ?? '',
            $role, $mustChange ? 1 : 0, $createdBy, date('Y-m-d H:i:s')]
    );
}
