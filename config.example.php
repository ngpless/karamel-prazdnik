<?php
/**
 * Пример файла конфигурации.
 * Скопируйте его в config.php и поменяйте значения под своё окружение.
 * Если config.php отсутствует, сайт запустится в режиме SQLite (значения ниже),
 * чтобы его можно было проверить без MySQL.
 */
return [
    'db' => [
        // 'sqlite' — файловая БД, ничего устанавливать не нужно (проверка и разработка)
        // 'mysql'  — MySQL / MariaDB (XAMPP, InfinityFree)
        'driver'      => 'sqlite',

        // Параметры для SQLite
        'sqlite_path' => __DIR__ . '/data/karamel.sqlite',

        // Параметры для MySQL (XAMPP: host=localhost, user=root, пароль пустой;
        // InfinityFree: значения из панели «MySQL Databases», хост вида sqlXXX.infinityfree.com)
        'host'        => 'localhost',
        'port'        => 3306,
        'name'        => 'karamel',
        'user'        => 'root',
        'pass'        => '',
        'charset'     => 'utf8mb4',
    ],
    'site' => [
        'name'  => 'Карамель',
        // true — показывать ошибки PHP на странице (только для разработки)
        'debug' => false,
    ],
];
