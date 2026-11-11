<?php
require_once __DIR__ . '/layout_header.php';

$stats = [
    'users'           => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'topics'          => (int)$pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn(),
    'posts'           => (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'orders'          => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'new_orders'      => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn(),
    'pending_topics'  => (int)$pdo->query("SELECT COUNT(*) FROM topics WHERE is_approved = 0")->fetchColumn(),
    'active_products' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="street-font m-0">DASHBOARD</h1>
    <span class="badge bg-dark rounded-0 px-3 py-2">Активных товаров: <?= $stats['active_products'] ?></span>
</div>

<div class="row g-3 g-md-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="admin-card text-center p-4 bg-white">
            <h1 class="display-4 fw-black m-0"><?= $stats['users'] ?></h1>
            <div class="text-uppercase fw-bold text-muted street-font">Юзеров</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-card text-center p-4 bg-warning">
            <h1 class="display-4 fw-black m-0"><?= $stats['topics'] ?></h1>
            <div class="text-uppercase fw-bold text-dark street-font">Всего тем</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-card text-center p-4 bg-white">
            <h1 class="display-4 fw-black m-0"><?= $stats['posts'] ?></h1>
            <div class="text-uppercase fw-bold text-muted street-font">Сообщений</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-card text-center p-4 bg-dark text-white border-warning">
            <h1 class="display-4 fw-black m-0 text-warning"><?= $stats['orders'] ?></h1>
            <div class="text-uppercase fw-bold text-white street-font">Заказов</div>
        </div>
    </div>
</div>

<?php if ($stats['new_orders'] > 0): ?>
<div class="alert alert-warning border-3 border-dark rounded-0 d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
    <div>
        <h4 class="street-font m-0"><i class="bi bi-bag-check-fill" aria-hidden="true"></i> НОВЫЕ ЗАКАЗЫ</h4>
        <p class="m-0 fw-bold">Ожидают обработки: <?= $stats['new_orders'] ?></p>
    </div>
    <a href="/admin/orders.php?status=new" class="btn btn-admin btn-admin-dark">Обработать</a>
</div>
<?php endif; ?>

<?php if ($stats['pending_topics'] > 0): ?>
<div class="alert alert-danger border-3 border-dark rounded-0 d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
    <div>
        <h4 class="street-font m-0"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> МОДЕРАЦИЯ ФОРУМА</h4>
        <p class="m-0 fw-bold">Новых тем на модерации: <?= $stats['pending_topics'] ?></p>
    </div>
    <a href="/admin/topics.php?filter=pending" class="btn btn-admin btn-admin-dark">Перейти</a>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-header">Быстрые действия</div>
            <div class="list-group list-group-flush">
                <a href="/admin/topics.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-chat-left-text me-2 text-dark" aria-hidden="true"></i> Управление темами и постами форума
                </a>
                <a href="/admin/forum_sections.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-plus-circle me-2 text-warning" aria-hidden="true"></i> Разделы форума
                </a>
                <a href="/admin/forum_categories.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-folder-plus me-2 text-primary" aria-hidden="true"></i> Категории форума
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-header bg-warning text-dark">Магазин и пользователи</div>
            <div class="list-group list-group-flush">
                <a href="/admin/product_edit.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-box-seam me-2 text-success" aria-hidden="true"></i> Добавить новый товар
                </a>
                <a href="/admin/shop_categories.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-tags me-2 text-secondary" aria-hidden="true"></i> Категории магазина
                </a>
                <a href="/admin/orders.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-bag me-2 text-dark" aria-hidden="true"></i> Заказы магазина
                </a>
                <a href="/admin/users.php" class="list-group-item list-group-item-action py-3 fw-bold">
                    <i class="bi bi-people me-2 text-danger" aria-hidden="true"></i> Пользователи и баны форума
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
