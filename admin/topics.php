<?php
// admin/topics.php
require_once __DIR__ . '/admin_auth.php';

$filter = (($_GET['filter'] ?? 'all') === 'pending') ? 'pending' : 'all';
$backUrl = '/admin/topics.php?filter=' . $filter;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect($backUrl);
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if (isset($_POST['delete_topic'])) {
            $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$id]);
            redirectWithFlash($backUrl, 'Тема удалена.', 'warning');
        }
        if (isset($_POST['approve_topic'])) {
            $tStmt = $pdo->prepare("SELECT user_id, title FROM topics WHERE id = ? LIMIT 1");
            $tStmt->execute([$id]);
            $topicRow = $tStmt->fetch();
            $pdo->prepare("UPDATE topics SET is_approved = 1 WHERE id = ?")->execute([$id]);

            if ($topicRow) {
                $msg = "Ваша тема '" . mb_substr($topicRow['title'], 0, 50, 'UTF-8') . "' одобрена!";
                $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'topic_approved', ?, ?)")
                    ->execute([$topicRow['user_id'], $msg, '/pages/topic.php?id=' . $id]);
            }
            redirectWithFlash($backUrl, 'Тема одобрена.', 'success');
        }
        if (isset($_POST['hide_topic'])) {
            $pdo->prepare("UPDATE topics SET is_approved = 0 WHERE id = ?")->execute([$id]);
            redirectWithFlash($backUrl, 'Тема скрыта на модерацию.', 'info');
        }
    }
}

require_once __DIR__ . '/layout_header.php';

$where = ($filter === 'pending') ? "WHERE t.is_approved = 0 " : "";
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$totalTopics = (int)$pdo->query("SELECT COUNT(*) FROM topics t $where")->fetchColumn();
$totalPages = max(1, (int)ceil($totalTopics / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT t.*, u.username, c.title as cat_title
        FROM topics t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN categories c ON t.category_id = c.id
        $where
        ORDER BY t.created_at DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$topics = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">Управление Темами (<?= $totalTopics ?>)</h2>
    <div>
        <a href="?filter=all" class="btn btn-sm btn-admin <?= $filter === 'all' ? 'btn-admin-dark' : 'btn-light' ?>">Все</a>
        <a href="?filter=pending" class="btn btn-sm btn-admin <?= $filter === 'pending' ? 'btn-admin-primary' : 'btn-light' ?>">На модерации</a>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin table-striped m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Заголовок</th>
                    <th>Автор</th>
                    <th>Категория</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $t): ?>
                <tr>
                    <td>#<?= (int)$t['id'] ?></td>
                    <td>
                        <a href="/pages/topic.php?id=<?= (int)$t['id'] ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-dark text-decoration-none">
                            <?= e($t['title']) ?> <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>
                        </a>
                    </td>
                    <td>@<?= e($t['username'] ?? 'Удалён') ?></td>
                    <td><span class="badge bg-secondary rounded-0"><?= e($t['cat_title'] ?? '-') ?></span></td>
                    <td class="small"><?= e($t['created_at']) ?></td>
                    <td>
                        <?php if (!empty($t['is_approved'])): ?>
                            <span class="badge bg-success rounded-0">ОК</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-0">WAIT</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <?php if (empty($t['is_approved'])): ?>
                                <button type="submit" name="approve_topic" value="1" class="btn btn-sm btn-admin btn-admin-primary">Одобрить</button>
                            <?php else: ?>
                                <button type="submit" name="hide_topic" value="1" class="btn btn-sm btn-admin btn-secondary" aria-label="Скрыть тему"><i class="bi bi-eye-slash" aria-hidden="true"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="delete_topic" value="1" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить тему навсегда?')" aria-label="Удалить тему"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav aria-label="Страницы тем">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link rounded-0 border-dark fw-bold" href="?filter=<?= e($filter) ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
