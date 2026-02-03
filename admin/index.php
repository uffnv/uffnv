<?php require_once 'layout_header.php'; 

// Статистика
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'topics' => $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn(),
    'posts' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_topics' => $pdo->query("SELECT COUNT(*) FROM topics WHERE is_approved = 0")->fetchColumn()
];
?>

<h1 class="street-font mb-4">DASHBOARD</h1>

<!-- КАРТОЧКИ СТАТИСТИКИ -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="admin-card text-center p-4 bg-white">
            <h1 class="display-4 fw-black m-0"><?= $stats['users'] ?></h1>
            <div class="text-uppercase fw-bold text-muted street-font">Юзеров</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card text-center p-4 bg-warning">
            <h1 class="display-4 fw-black m-0"><?= $stats['topics'] ?></h1>
            <div class="text-uppercase fw-bold text-dark street-font">Всего тем</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card text-center p-4 bg-white">
            <h1 class="display-4 fw-black m-0"><?= $stats['posts'] ?></h1>
            <div class="text-uppercase fw-bold text-muted street-font">Сообщений</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card text-center p-4 bg-dark text-white border-warning">
            <h1 class="display-4 fw-black m-0 text-warning"><?= $stats['orders'] ?></h1>
            <div class="text-uppercase fw-bold text-white street-font">Заказов</div>
        </div>
    </div>
</div>

<?php if($stats['pending_topics'] > 0): ?>
<div class="alert alert-danger border-3 border-dark rounded-0 d-flex align-items-center justify-content-between p-4">
    <div>
        <h4 class="street-font m-0"><i class="bi bi-exclamation-triangle-fill"></i> ВНИМАНИЕ</h4>
        <p class="m-0 fw-bold">Новых тем на модерации: <?= $stats['pending_topics'] ?></p>
    </div>
    <a href="/admin/topics.php?filter=pending" class="btn btn-admin btn-admin-dark">Перейти</a>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-header">Быстрые действия</div>
            <div class="list-group list-group-flush">
                <a href="/admin/forum_sections.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-plus-circle me-2 text-warning"></i> Добавить раздел форума
                </a>
                <a href="/admin/forum_categories.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-folder-plus me-2 text-primary"></i> Создать категорию
                </a>
                <a href="/admin/products.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-box-seam me-2 text-success"></i> Добавить товар
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
