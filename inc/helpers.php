<?php
/**
 * Вспомогательные функции: вывод, адреса, сообщения, CSRF, оформление текста.
 */
declare(strict_types=1);

/** Экранирование для вывода в HTML. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Адрес внутри сайта: url('news.php', ['page' => 2]). */
function url(string $path = '', array $query = []): string
{
    $u = BASE . '/' . ltrim($path, '/');
    if ($query) {
        $u .= '?' . http_build_query($query);
    }
    return $u;
}

function asset(string $path): string
{
    return BASE . '/assets/' . ltrim($path, '/');
}

function redirect(string $path, array $query = []): void
{
    header('Location: ' . (preg_match('~^https?://~', $path) ? $path : url($path, $query)));
    exit;
}

/** Безопасный внутренний адрес для параметров next/back: только путь на этом же сайте. */
function safe_local(string $target, string $fallback = 'index.php'): string
{
    $target = trim($target);
    if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//') || str_contains($target, '\\')
        || preg_match('~[\r\n]~', $target)) {
        return url($fallback);
    }
    if (BASE !== '' && !str_starts_with($target, BASE . '/')) {
        return url($fallback);
    }
    return $target;
}

// ---------- Одноразовые сообщения ----------
function flash(string $type, string $text): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

function flash_take(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Проверка токена в POST-запросе; при ошибке прерывает выполнение с кодом 400. */
function csrf_check(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        fatal_page('Форма устарела или отправлена с другого сайта. Вернитесь назад, обновите страницу и повторите отправку.', 400);
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Значение из POST с обрезкой пробелов. */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Старое значение поля формы после ошибки. */
function old(array $data, string $key): string
{
    return e($data[$key] ?? '');
}

// ---------- Даты и числа ----------
function ru_date(string $datetime, bool $withTime = false): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $months = ['', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $s = (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) {
        $s .= ', ' . date('H:i', $ts);
    }
    return $s;
}

function plural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 === 1) {
        return $one;
    }
    return $many;
}

function money(int $n): string
{
    return number_format($n, 0, ',', ' ') . ' ₽';
}

// ---------- Текст ----------
/** Количество слов в тексте (для проверки объёма статей). */
function word_count(string $text): int
{
    return preg_match_all('/[\p{L}\p{N}]+(?:[-\'][\p{L}\p{N}]+)*/u', $text);
}

/** Транслитерация названия в адрес (slug). */
function slugify(string $s): string
{
    $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'];
    $s = strtr(mb_strtolower($s), $map);
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim((string)$s, '-');
    return mb_substr($s !== '' ? $s : 'item', 0, 80);
}

/**
 * Простое оформление текста статьи: пустая строка — новый абзац,
 * «## » — подзаголовок, строки с «- » — список, «> » — цитата.
 * Весь текст экранируется, HTML из базы не выводится как есть.
 */
function format_body(string $text): string
{
    $blocks = preg_split('/\R\s*\R/u', trim($text)) ?: [];
    $out = '';
    foreach ($blocks as $b) {
        $b = trim($b);
        if ($b === '') {
            continue;
        }
        if (str_starts_with($b, '## ')) {
            $out .= '<h2>' . e(mb_substr($b, 3)) . "</h2>\n";
            continue;
        }
        if (str_starts_with($b, '> ')) {
            $out .= '<blockquote><p>' . e(mb_substr($b, 2)) . "</p></blockquote>\n";
            continue;
        }
        $lines = preg_split('/\R/u', $b) ?: [];
        $isList = true;
        foreach ($lines as $l) {
            if (!str_starts_with(ltrim($l), '- ')) {
                $isList = false;
                break;
            }
        }
        if ($isList) {
            $out .= "<ul>\n";
            foreach ($lines as $l) {
                $out .= '<li>' . e(mb_substr(ltrim($l), 2)) . "</li>\n";
            }
            $out .= "</ul>\n";
        } else {
            $out .= '<p>' . implode('<br>', array_map('e', $lines)) . "</p>\n";
        }
    }
    return $out;
}

/** Подсветка искомых слов в уже безопасном фрагменте текста. */
function highlight(string $text, array $words): string
{
    $safe = e($text);
    foreach ($words as $w) {
        if ($w === '') {
            continue;
        }
        $re = '~(' . preg_quote(e($w), '~') . ')~iu';
        $safe = preg_replace($re, '<mark>$1</mark>', $safe) ?? $safe;
    }
    return $safe;
}

/** Фрагмент текста вокруг первого найденного слова. */
function snippet(string $body, array $words, int $len = 220): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', preg_replace('/^(## |> |- )/mu', '', $body)) ?? $body);
    $pos = null;
    $low = mb_strtolower($plain);
    foreach ($words as $w) {
        $p = mb_strpos($low, mb_strtolower($w));
        if ($p !== false && ($pos === null || $p < $pos)) {
            $pos = $p;
        }
    }
    $start = $pos === null ? 0 : max(0, $pos - 70);
    $part = mb_substr($plain, $start, $len);
    return ($start > 0 ? '… ' : '') . $part . (mb_strlen($plain) > $start + $len ? ' …' : '');
}

// ---------- Версия для слабовидящих (cookie) ----------
function ui_settings(): array
{
    static $ui = null;
    if ($ui !== null) {
        return $ui;
    }
    $mode = ($_COOKIE['ui_mode'] ?? '') === 'lv' ? 'lv' : 'normal';
    $font = (int)($_COOKIE['ui_font'] ?? 2);
    $font = ($font >= 1 && $font <= 3) ? $font : 2;
    $scheme = (string)($_COOKIE['ui_scheme'] ?? 'bw');
    if (!in_array($scheme, ['bw', 'wb', 'bl', 'br'], true)) {
        $scheme = 'bw';
    }
    $img = ($_COOKIE['ui_img'] ?? '0') === '1';
    $ui = ['mode' => $mode, 'font' => $font, 'scheme' => $scheme, 'img' => $img];
    return $ui;
}

function ui_is_lv(): bool
{
    return ui_settings()['mode'] === 'lv';
}

// ---------- Аварийная страница ----------
function fatal_page(string $message, int $code = 500): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Ошибка</title>'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<style>body{font-family:Verdana,sans-serif;background:#fff6e9;color:#3a2e39;display:grid;place-items:center;min-height:100vh;margin:0}'
        . 'div{max-width:520px;padding:32px;background:#fff;border-radius:24px;box-shadow:0 6px 24px #0002}a{color:#b83222}</style></head>'
        . '<body><div><h1>Что-то пошло не так</h1><p>' . e($message) . '</p><p><a href="' . e(url('index.php')) . '">Вернуться на главную</a></p></div></body></html>';
    exit;
}

/** Статусы заявок. */
function request_statuses(): array
{
    return [
        'new'       => 'Новая',
        'in_work'   => 'В работе',
        'confirmed' => 'Подтверждена',
        'done'      => 'Проведена',
        'cancelled' => 'Отменена',
    ];
}

/** Названия ролей и их права (для админ-панели и документации). */
function roles_info(): array
{
    return [
        'client'  => ['title' => 'Клиент', 'rights' => 'Личный кабинет, заявка на праздник, переписка с менеджером'],
        'manager' => ['title' => 'Сотрудник (менеджер)', 'rights' => 'Ответы на сообщения, заявки, создание учётных записей клиентов, статьи и новости'],
        'admin'   => ['title' => 'Администратор', 'rights' => 'Всё, что у менеджера, плюс пользователи и роли, баннеры'],
    ];
}
