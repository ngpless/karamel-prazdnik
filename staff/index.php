<?php
/** Панель сотрудника: сводка и быстрые ссылки. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Панель сотрудника';
$active = '';
$noIndex = true;
$showBanner = false;

$stat = [
    'requests' => (int)db_val("SELECT COUNT(*) FROM requests WHERE status = 'new'"),
    'messages' => (int)db_val('SELECT COUNT(*) FROM messages WHERE from_staff = 0 AND is_read = 0'),
    'feedback' => (int)db_val('SELECT COUNT(*) FROM feedback WHERE is_done = 0'),
    'clients'  => (int)db_val("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'articles' => (int)db_val('SELECT COUNT(*) FROM articles'),
    'news'     => (int)db_val('SELECT COUNT(*) FROM news'),
];
$latest = db_all('SELECT r.id, r.event_date, r.guests, r.status, u.full_name, s.title AS service_title
                  FROM requests r JOIN users u ON u.id = r.client_id JOIN services s ON s.id = r.service_id
                  ORDER BY r.created_at DESC, r.id DESC LIMIT 5');
$statuses = request_statuses();

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника']]);
?>
<div class="container">
    <header class="page-head"><div>
        <h1>Панель сотрудника</h1>
        <p class="lead">Рабочее место менеджера: заявки, переписка, клиенты и контент сайта.</p>
    </div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <div class="grid grid-3 dash">
        <a class="card dash-tile" href="<?= e(url('staff/requests.php')) ?>"><b>Заявки: <?= $stat['requests'] ?></b><span>новых, ждут подтверждения</span></a>
        <a class="card dash-tile" href="<?= e(url('staff/messages.php')) ?>"><b>Сообщения: <?= $stat['messages'] ?></b><span>непрочитанных от клиентов</span></a>
        <a class="card dash-tile" href="<?= e(url('staff/feedback.php')) ?>"><b>Обращения с сайта: <?= $stat['feedback'] ?></b><span>из формы на странице «Контакты»</span></a>
        <a class="card dash-tile" href="<?= e(url('staff/clients.php')) ?>"><b>Клиенты: <?= $stat['clients'] ?></b><span>список и создание учётной записи</span></a>
        <a class="card dash-tile" href="<?= e(url('staff/articles.php')) ?>"><b>Статьи: <?= $stat['articles'] ?></b><span>добавить и изменить</span></a>
        <a class="card dash-tile" href="<?= e(url('staff/news.php')) ?>"><b>Новости: <?= $stat['news'] ?></b><span>добавить и изменить</span></a>
    </div>

    <section class="card block-card">
        <h2>Последние заявки</h2>
        <?php if (!$latest): ?>
            <p class="muted">Заявок пока нет.</p>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>№</th><th>Клиент</th><th>Услуга</th><th>Дата</th><th>Гостей</th><th>Статус</th></tr></thead>
                <tbody>
                <?php foreach ($latest as $r): ?>
                    <tr><td><a href="<?= e(url('staff/requests.php', ['id' => $r['id']])) ?>"><?= (int)$r['id'] ?></a></td><td><?= e($r['full_name']) ?></td><td><?= e($r['service_title']) ?></td>
                        <td><?= e(ru_date($r['event_date'])) ?></td><td><?= (int)$r['guests'] ?></td>
                        <td><span class="status st-<?= e($r['status']) ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
