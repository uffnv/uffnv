<?php
// admin/orders.php
require_once 'layout_header.php';

// Обработка изменения статуса (если отправлена форма)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], (int)$_POST['id']]);
    // Перезагружаем страницу, чтобы увидеть изменения
    echo "<script>window.location.href='orders.php';</script>";
    exit;
}

// Получение списка заказов с именами пользователей
$orders = $pdo->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">ЗАКАЗЫ</h2>
</div>

<!-- Таблица заказов -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin table-hover m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th class="text-end">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php 
                        // Безопасное определение цвета статуса (работает на всех версиях PHP)
                        $statusColors = [
                            'new'        => 'primary',   // Новый - Синий
                            'processing' => 'info',      // В обработке - Голубой
                            'shipped'    => 'warning',   // Отправлен - Желтый
                            'completed'  => 'success',   // Выполнен - Зеленый
                            'cancelled'  => 'danger'     // Отменен - Красный
                        ];
                        // Если статус неизвестен, будет серый (secondary)
                        $badgeColor = isset($statusColors[$o['status']]) ? $statusColors[$o['status']] : 'secondary';
                        
                        // Русские названия статусов
                        $statusNames = [
                            'new'        => 'Новый',
                            'processing' => 'В обработке',
                            'shipped'    => 'Отправлен',
                            'completed'  => 'Выполнен',
                            'cancelled'  => 'Отменен'
                        ];
                        $statusText = isset($statusNames[$o['status']]) ? $statusNames[$o['status']] : $o['status'];
                    ?>
                    <tr>
                        <td class="fw-bold">#<?= $o['id'] ?></td>
                        <td>
                            <i class="bi bi-person-fill text-muted me-1"></i>
                            <?= htmlspecialchars($o['username']) ?>
                        </td>
                        <td class="fw-bold">
                            <?= number_format($o['total_price'] ?? 0, 0, '', ' ') ?> ₽
                        </td>
                        <td class="small text-muted">
                            <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badgeColor ?> rounded-0 text-uppercase">
                                <?= $statusText ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="order_view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-admin btn-admin-dark">
                                <i class="bi bi-eye-fill me-1"></i> Подробнее
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted fw-bold">
                            Заказов пока нет
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
