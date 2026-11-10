<?php
/** Система обмена сообщениями, сторона сотрудника: список переписок и ответ клиенту. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Сообщения клиентов';
$active = '';
$noIndex = true;
$showBanner = false;
$clientId = (int)get('client', '0');
$error = '';
$text = '';

$client = $clientId ? db_one("SELECT id, full_name, login, email, phone FROM users WHERE id = ? AND role = 'client'", [$clientId]) : null;

if ($client && is_post()) {
    csrf_check();
    $text = post('body');
    $len = mb_strlen($text);
    if ($len < 2) {
        $error = 'Напишите ответ (не короче двух знаков).';
    } elseif ($len > 2000) {
        $error = 'Ответ слишком длинный: не более 2000 знаков.';
    } else {
        db_insert('INSERT INTO messages (client_id, sender_id, from_staff, body, is_read, created_at) VALUES (?, ?, 1, ?, 0, ?)',
            [$client['id'], $user['id'], $text, date('Y-m-d H:i:s')]);
        flash('ok', 'Ответ отправлен клиенту ' . $client['full_name'] . '.');
        redirect('staff/messages.php', ['client' => $client['id']]);
    }
}

$threads = db_all("SELECT u.id, u.full_name,
        (SELECT body FROM messages m2 WHERE m2.client_id = u.id ORDER BY m2.created_at DESC, m2.id DESC LIMIT 1) AS last_body,
        (SELECT MAX(created_at) FROM messages m3 WHERE m3.client_id = u.id) AS last_at,
        (SELECT COUNT(*) FROM messages m4 WHERE m4.client_id = u.id AND m4.from_staff = 0 AND m4.is_read = 0) AS unread
    FROM users u WHERE u.role = 'client' AND EXISTS (SELECT 1 FROM messages m WHERE m.client_id = u.id)
    ORDER BY last_at DESC");

$thread = [];
if ($client) {
    db_exec('UPDATE messages SET is_read = 1 WHERE client_id = ? AND from_staff = 0 AND is_read = 0', [$client['id']]);
    $thread = db_all('SELECT m.*, u.full_name AS sender_name FROM messages m JOIN users u ON u.id = m.sender_id
                      WHERE m.client_id = ? ORDER BY m.created_at, m.id', [$client['id']]);
}

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника', url('staff/index.php')], ['Сообщения']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Сообщения клиентов</h1></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>
    <div class="split">
        <aside class="thread-list card" aria-label="Переписки">
            <h2>Переписки</h2>
            <?php if (!$threads): ?>
                <p class="muted">Клиенты пока не писали.</p>
            <?php endif; ?>
            <ul class="plain">
                <?php foreach ($threads as $t): ?>
                    <li class="<?= $client && (int)$t['id'] === (int)$client['id'] ? 'is-active' : '' ?>">
                        <a href="<?= e(url('staff/messages.php', ['client' => $t['id']])) ?>">
                            <b><?= e($t['full_name']) ?></b><?= (int)$t['unread'] ? ' <span class="badge">' . (int)$t['unread'] . '</span>' : '' ?><br>
                            <small><?= e(mb_substr((string)$t['last_body'], 0, 60)) ?><?= mb_strlen((string)$t['last_body']) > 60 ? '…' : '' ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <section class="thread-view">
            <?php if (!$client): ?>
                <div class="card"><p class="muted">Выберите переписку слева.</p></div>
            <?php else: ?>
                <div class="card thread-head">
                    <h2><?= e($client['full_name']) ?></h2>
                    <p class="muted">Логин: <?= e($client['login']) ?> · <?= e($client['email']) ?><?= $client['phone'] !== '' ? ' · ' . e($client['phone']) : '' ?></p>
                </div>
                <div class="chat" aria-label="История сообщений">
                    <?php foreach ($thread as $m): ?>
                        <article class="msg <?= $m['from_staff'] ? 'msg-me' : 'msg-staff' ?>">
                            <header><b><?= $m['from_staff'] ? e($m['sender_name']) . ' (сотрудник)' : e($client['full_name']) ?></b>
                                <time datetime="<?= e($m['created_at']) ?>"><?= e(ru_date($m['created_at'], true)) ?></time></header>
                            <p><?= nl2br(e($m['body'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$thread): ?><p class="muted chat-empty">Сообщений нет. Можно написать первым.</p><?php endif; ?>
                </div>
                <form class="card form-card" method="post" action="<?= e(url('staff/messages.php', ['client' => $client['id']])) ?>#reply" id="reply" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="s-body">Ответ клиенту</label>
                        <textarea id="s-body" name="body" rows="4" maxlength="2000" required<?= $error ? ' aria-invalid="true" aria-describedby="err-body"' : '' ?>><?= e($text) ?></textarea>
                        <?php if ($error): ?><span class="field-error" id="err-body" role="alert"><?= e($error) ?></span><?php endif; ?>
                    </div>
                    <button class="btn" type="submit">Отправить ответ</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
