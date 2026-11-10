<?php
/**
 * Слой доступа к данным на PDO. Режим выбирается в config.php:
 * 'mysql' (MySQL/MariaDB) или 'sqlite' (проверка без MySQL).
 * Все запросы приложения идут только через подготовленные выражения.
 */
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = $GLOBALS['CFG']['db'];
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (($c['driver'] ?? 'sqlite') === 'mysql') {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'], (int)($c['port'] ?? 3306), $c['name'], $c['charset'] ?? 'utf8mb4');
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        } catch (PDOException $e) {
            fatal_page('Не удалось подключиться к базе данных. Проверьте параметры в файле config.php.');
        }
        return $pdo;
    }

    // SQLite: файл создаётся и заполняется из sql/schema_sqlite.sql и sql/seed.sql при первом обращении
    $path = $c['sqlite_path'];
    $fresh = !is_file($path) || filesize($path) === 0;
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    $pdo = new PDO('sqlite:' . $path, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys = ON');
    if ($fresh) {
        $pdo->exec(file_get_contents(ROOT . '/sql/schema_sqlite.sql'));
        $pdo->exec(file_get_contents(ROOT . '/sql/seed.sql'));
    }
    return $pdo;
}

/** Все строки запроса. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Одна строка или null. */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Одно значение (первый столбец первой строки). */
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

/** INSERT/UPDATE/DELETE, возвращает число затронутых строк. */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** INSERT, возвращает идентификатор новой записи. */
function db_insert(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int)db()->lastInsertId();
}

/** Текст для столбца search_text: всё в нижнем регистре (в SQLite LIKE не различает регистр только для латиницы). */
function search_text(string ...$parts): string
{
    return mb_strtolower(implode(' ', $parts));
}

/** Экранирование спецсимволов LIKE (символ-экранирование «!»). */
function like_escape(string $s): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
}
