<?php
/** Система обмена сообщениями, сторона клиента: переписка с менеджером. */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$user = require_role('client');
$pageTitle = 'Переписка с менеджером';
$pageDesc = 'Сообщения между клиентом и менеджером агентства «Карамель».';
$active = '';
$showBanner = false;
$error = '';
$text = '';

if (is_post()) {
    csrf_check();
    $text = post('body');
    $len = mb_strlen($text);
    if ($len < 2) {
        $error = 'Напишите сообщение (не короче двух знаков).';
    } elseif ($len > 2000) {
        $error = 'Сообщение слишком длинное: не более 2000 знаков.';
    } else {
        db_insert('INSERT INTO messages (client_id, sender_id, from_staff, body, is_read, created_at) VALUES (?, ?, 0, ?, 0, ?)',
            [$user['id'], $user['id'], $text, date('Y-m-d H:i:s')]);
        flash('ok', 'Сообщение отправлено. Менеджер ответит здесь же.');
        redirect('messages.php');
    }
}

// Открыв переписку, клиент прочитал все ответы менеджера
db_exec('UPDATE messages SET is_read = 1 WHERE client_id = ? AND from_staff = 1 AND is_read = 0', [$user['id']]);
$thread = db_all('SELECT m.*, u.full_name AS sender_name FROM messages m JOIN users u ON u.id = m.sender_id
                  WHERE m.client_id = ? ORDER BY m.created_at, m.id', [$user['id']]);

require __DIR__ . '/inc/header.php';
echo breadcrumbs([['Личный кабинет', url('cabinet.php')], ['Переписка']]);
?>
<div class="container narrow-wide">
    <header class="page-head"><div>
        <h1>Переписка с менеджером</h1>
        <p class="lead">Задайте вопрос о празднике или смете. Ответ появится в этом окне, а на значке «Личный кабинет» вы увидите число новых сообщений.</p>
    </div></header>

    <section class="chat" aria-label="История сообщений">
        <?php if (!$thread): ?>
            <p class="muted chat-empty">Сообщений пока нет. Напишите первое.</p>
        <?php endif; ?>
        <?php foreach ($thread as $m): ?>
            <article class="msg <?= $m['from_staff'] ? 'msg-staff' : 'msg-me' ?>">
                <header><b><?= $m['from_staff'] ? e($m['sender_name']) . ' (менеджер)' : 'Вы' ?></b>
                    <time datetime="<?= e($m['created_at']) ?>"><?= e(ru_date($m['created_at'], true)) ?></time></header>
                <p><?= nl2br(e($m['body'])) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <form class="card form-card" method="post" action="<?= e(url('messages.php')) ?>#new" id="new" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="m-body">Новое сообщение</label>
            <textarea id="m-body" name="body" rows="4" maxlength="2000" required<?= $error ? ' aria-invalid="true" aria-describedby="err-body"' : '' ?>><?= e($text) ?></textarea>
            <?php if ($error): ?><span class="field-error" id="err-body" role="alert"><?= e($error) ?></span><?php endif; ?>
        </div>
        <button class="btn" type="submit">Отправить</button>
    </form>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
