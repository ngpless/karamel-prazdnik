<?php
/** Заявки на праздники: список, просмотр, смена статуса и заметка менеджера. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

$user = require_role('manager', 'admin');
$pageTitle = 'Заявки на праздники';
$active = '';
$noIndex = true;
$showBanner = false;
$statuses = request_statuses();
$id = (int)get('id', '0');

if (is_post()) {
    csrf_check();
    $rid = (int)post('id', '0');
    $status = post('status');
    if (!isset($statuses[$status]) || !db_val('SELECT id FROM requests WHERE id = ?', [$rid])) {
        flash('error', 'Не удалось изменить заявку: неверные данные.');
    } else {
        db_exec('UPDATE requests SET status = ?, manager_note = ? WHERE id = ?', [$status, mb_substr(post('manager_note'), 0, 1000), $rid]);
        flash('ok', 'Заявка № ' . $rid . ' обновлена.');
    }
    redirect('staff/requests.php', ['id' => $rid]);
}

$filter = get('status');
$where = isset($statuses[$filter]) ? 'WHERE r.status = ?' : '';
$list = db_all("SELECT r.id, r.event_date, r.guests, r.status, r.created_at, u.full_name, s.title AS service_title
                FROM requests r JOIN users u ON u.id = r.client_id JOIN services s ON s.id = r.service_id
                $where ORDER BY r.created_at DESC, r.id DESC", $where ? [$filter] : []);
$one = $id ? db_one('SELECT r.*, u.full_name, u.email, u.phone, u.id AS uid, s.title AS service_title
                     FROM requests r JOIN users u ON u.id = r.client_id JOIN services s ON s.id = r.service_id WHERE r.id = ?', [$id]) : null;

require dirname(__DIR__) . '/inc/header.php';
echo breadcrumbs([['Панель сотрудника', url('staff/index.php')], ['Заявки']]);
?>
<div class="container">
    <header class="page-head"><div><h1>Заявки на праздники</h1></div></header>
    <?php require __DIR__ . '/_menu.php'; ?>

    <form class="filter" method="get" action="<?= e(url('staff/requests.php')) ?>">
        <label for="f-status">Показать:</label>
        <select id="f-status" name="status">
            <option value="">все заявки</option>
            <?php foreach ($statuses as $k => $v): ?><option value="<?= e($k) ?>"<?= $filter === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
        </select>
        <button class="btn small" type="submit">Применить</button>
    </form>

    <?php if ($one): ?>
        <section class="card block-card">
            <h2>Заявка № <?= (int)$one['id'] ?></h2>
            <dl class="dl">
                <dt>Клиент</dt><dd><?= e($one['full_name']) ?> · <?= e($one['email']) ?><?= $one['phone'] !== '' ? ' · ' . e($one['phone']) : '' ?>
                    · <a href="<?= e(url('staff/messages.php', ['client' => $one['uid']])) ?>">написать в чат</a></dd>
                <dt>Услуга</dt><dd><?= e($one['service_title']) ?></dd>
                <dt>Дата праздника</dt><dd><?= e(ru_date($one['event_date'])) ?>, гостей: <?= (int)$one['guests'] ?><?= $one['child_age'] !== '' ? ', возраст: ' . e($one['child_age']) : '' ?></dd>
                <dt>Место</dt><dd><?= e($one['place'] !== '' ? $one['place'] : 'не указано') ?></dd>
                <dt>Бюджет</dt><dd><?= $one['budget'] !== null ? e(money((int)$one['budget'])) : 'не указан' ?></dd>
                <dt>Пожелания</dt><dd><?= $one['comment'] !== '' ? nl2br(e($one['comment'])) : 'нет' ?></dd>
                <dt>Создана</dt><dd><?= e(ru_date($one['created_at'], true)) ?></dd>
            </dl>
            <form method="post" action="<?= e(url('staff/requests.php')) ?>" class="form-inline-grid">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$one['id'] ?>">
                <div class="field"><label for="r-status">Статус</label>
                    <select id="r-status" name="status">
                        <?php foreach ($statuses as $k => $v): ?><option value="<?= e($k) ?>"<?= $one['status'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field wide"><label for="r-note">Заметка менеджера (клиент её не видит)</label>
                    <textarea id="r-note" name="manager_note" rows="3" maxlength="1000"><?= e($one['manager_note']) ?></textarea></div>
                <button class="btn" type="submit">Сохранить</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="card block-card">
        <h2>Список заявок</h2>
        <?php if (!$list): ?>
            <p class="muted">Заявок нет.</p>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>№</th><th>Клиент</th><th>Услуга</th><th>Дата</th><th>Гостей</th><th>Статус</th></tr></thead>
                <tbody>
                <?php foreach ($list as $r): ?>
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
