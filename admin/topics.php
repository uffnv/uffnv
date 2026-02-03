<?php require_once 'layout_header.php'; ?>

<?php
if (isset($_POST['delete_topic'])) {
    $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([(int)$_POST['id']]);
}
if (isset($_POST['approve_topic'])) {
    $pdo->prepare("UPDATE topics SET is_approved = 1 WHERE id = ?")->execute([(int)$_POST['id']]);
}
if (isset($_POST['hide_topic'])) {
    $pdo->prepare("UPDATE topics SET is_approved = 0 WHERE id = ?")->execute([(int)$_POST['id']]);
}

// Фильтр
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT t.*, u.username, c.title as cat_title 
        FROM topics t 
        JOIN users u ON t.user_id = u.id 
        JOIN categories c ON t.category_id = c.id ";

if ($filter === 'pending') $sql .= "WHERE t.is_approved = 0 ";
$sql .= "ORDER BY t.created_at DESC LIMIT 50";

$topics = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">Управление Темами</h2>
    <div>
        <a href="?filter=all" class="btn btn-sm btn-admin <?= $filter=='all'?'btn-admin-dark':'btn-light' ?>">Все</a>
        <a href="?filter=pending" class="btn btn-sm btn-admin <?= $filter=='pending'?'btn-admin-primary':'btn-light' ?>">На модерации</a>
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
                <?php foreach($topics as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td>
                        <a href="/pages/topic.php?id=<?= $t['id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                            <?= htmlspecialchars($t['title']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </td>
                    <td>@<?= htmlspecialchars($t['username']) ?></td>
                    <td><span class="badge bg-secondary rounded-0"><?= htmlspecialchars($t['cat_title']) ?></span></td>
                    <td class="small"><?= $t['created_at'] ?></td>
                    <td>
                        <?php if($t['is_approved']): ?>
                            <span class="badge bg-success rounded-0">ОК</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-0 blink">WAIT</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <?php if(!$t['is_approved']): ?>
                                <button type="submit" name="approve_topic" class="btn btn-sm btn-admin btn-admin-primary">Одобрить</button>
                            <?php else: ?>
                                <button type="submit" name="hide_topic" class="btn btn-sm btn-admin btn-secondary"><i class="bi bi-eye-slash"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="delete_topic" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить тему навсегда?')"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
