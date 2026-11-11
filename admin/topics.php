<?php
// admin/topics.php
require_once __DIR__ . '/admin_auth.php';

$filter = (($_GET['filter'] ?? 'all') === 'pending') ? 'pending' : 'all';
$catId  = max(0, (int)($_GET['cat'] ?? 0));
$q      = trim($_GET['q'] ?? '');

$queryParts = ['filter' => $filter];
if ($catId > 0) $queryParts['cat'] = $catId;
if ($q !== '') $queryParts['q'] = $q;
$backUrl = '/admin/topics.php?' . http_build_query($queryParts);

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

$cats = $pdo->query("SELECT id, title FROM categories ORDER BY title ASC")->fetchAll();

$whereParts = [];
$params = [];
if ($filter === 'pending') {
    $whereParts[] = 't.is_approved = 0';
}
if ($catId > 0) {
    $whereParts[] = 't.category_id = ?';
    $params[] = $catId;
}
if ($q !== '') {
    $whereParts[] = '(t.title LIKE ? OR u.username LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM topics t LEFT JOIN users u ON t.user_id = u.id $whereSql");
$cntStmt->execute($params);
$totalTopics = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalTopics / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT t.*, u.username, c.title as cat_title,
               (SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.id) AS replies_count
        FROM topics t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN categories c ON t.category_id = c.id
        $whereSql
        ORDER BY t.created_at DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topics = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="street-font m-0">Управление Темами (<?= $totalTopics ?>)</h2>
    <div class="d-flex gap-1">
        <a href="?<?= http_build_query(array_filter(['cat' => $catId ?: null, 'q' => $q ?: null, 'filter' => 'all'])) ?>" class="btn btn-sm btn-admin <?= $filter === 'all' ? 'btn-admin-dark' : 'btn-light' ?>">Все</a>
        <a href="?<?= http_build_query(array_filter(['cat' => $catId ?: null, 'q' => $q ?: null, 'filter' => 'pending'])) ?>" class="btn btn-sm btn-admin <?= $filter === 'pending' ? 'btn-admin-primary' : 'btn-light' ?>">На модерации</a>
    </div>
</div>

<form method="GET" class="admin-card p-3 mb-4 bg-white">
    <input type="hidden" name="filter" value="<?= e($filter) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="topicSearch" class="fw-bold small">Поиск по названию или автору</label>
            <input id="topicSearch" type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Заголовок или @ник...">
        </div>
        <div class="col-md-4">
            <label for="topicCatFilter" class="fw-bold small">Категория</label>
            <select id="topicCatFilter" name="cat" class="form-select form-select-sm">
                <option value="0">Все категории</option>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= $catId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-admin btn-admin-dark flex-grow-1">Найти</button>
            <?php if ($q !== '' || $catId > 0): ?>
                <a href="/admin/topics.php?filter=<?= e($filter) ?>" class="btn btn-sm btn-outline-dark rounded-0">Сброс</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin table-striped m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Заголовок</th>
                    <th>Автор</th>
                    <th>Категория</th>
                    <th>Ответы</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topics)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Темы по заданным фильтрам не найдены.</td></tr>
                <?php else: foreach ($topics as $t): ?>
                <tr>
                    <td>#<?= (int)$t['id'] ?></td>
                    <td>
                        <a href="/pages/topic.php?id=<?= (int)$t['id'] ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-dark text-decoration-none">
                            <?= e($t['title']) ?> <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>
                        </a>
                    </td>
                    <td>@<?= e($t['username'] ?? 'Удалён') ?></td>
                    <td><span class="badge bg-secondary rounded-0"><?= e($t['cat_title'] ?? '-') ?></span></td>
                    <td><span class="badge bg-dark rounded-0"><?= (int)$t['replies_count'] ?></span></td>
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
                            <a href="/admin/topic_edit.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0" title="Редактировать тему и ответы" aria-label="Редактировать тему и ответы"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a>
                            <?php if (empty($t['is_approved'])): ?>
                                <button type="submit" name="approve_topic" value="1" class="btn btn-sm btn-admin btn-admin-primary">Одобрить</button>
                            <?php else: ?>
                                <button type="submit" name="hide_topic" value="1" class="btn btn-sm btn-admin btn-secondary" aria-label="Скрыть тему"><i class="bi bi-eye-slash" aria-hidden="true"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="delete_topic" value="1" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить тему навсегда?')" aria-label="Удалить тему"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav aria-label="Страницы тем" class="mt-3">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link rounded-0 border-dark fw-bold" href="?<?= http_build_query(array_merge($queryParts, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
