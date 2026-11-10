<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (is_post()) {
    csrf_check();
    logout_user();
    session_start();
    flash('ok', 'Вы вышли из учётной записи.');
}
redirect('index.php');
