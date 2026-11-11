<?php
// pages/notifications.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

$perPage = 30;
$page = max(1, (int)($_GET['page'] ?? 1));

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmtCount->execute([(int)$_SESSION['user_id']]);
$totalNotifs = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalNotifs / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Получаем уведомления
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([(int)$_SESSION['user_id']]);
$notifs = $stmt->fetchAll();

// Подсчет непрочитанных
$stmtUnread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmtUnread->execute([(int)$_SESSION['user_id']]);
$unreadCount = (int)$stmtUnread->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* === ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }

    /* === СПИСОК УВЕДОМЛЕНИЙ === */
    .notif-container {
        background: #fff;
        border: 3px solid #000;
        box-shadow: 10px 10px 0 #000;
        transition: box-shadow 0.3s;
    }
    .notif-container:hover {
        box-shadow: 12px 12px 0 #bc13fe;
    }

    .notif-item {
        border-bottom: 2px solid #000;
        transition: background 0.2s, padding-left 0.2s;
        position: relative;
        text-decoration: none;
        color: #000;
        display: block;
        background: #fff;
        border-left: 6px solid transparent; /* Для маркера */
    }
    .notif-item:last-child { border-bottom: none; }

    /* Непрочитанные */
    .notif-unread {
        background: #fffbe6; /* Очень светло-желтый */
        border-left-color: #FCE300; /* Маркер */
    }
    .notif-unread .new-badge { display: inline-block; }
    .notif-read .new-badge { display: none; }

    /* Эффект наведения */
    .notif-item:hover {
        background: #f0f0f0;
        padding-left: 2rem !important; /* Сдвиг контента */
        border-left-color: #000;
    }
    .notif-item:hover .arrow-indicator {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }

    /* Стрелка при наведении */
    .arrow-indicator {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%) translateX(-10px);
        font-size: 1.5rem;
        color: #000;
        opacity: 0;
        transition: all 0.2s;
    }

    /* Иконки */
    .icon-box {
        width: 45px; height: 45px;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #000;
        font-size: 1.3rem;
    }
    
    /* Кнопки */
    .btn-black { 
        background: #000; color: #FCE300; border: 2px solid #000; 
        font-family: 'Arial Black', sans-serif;
    }
    .btn-black:hover { 
        background: #FCE300; color: #000; box-shadow: 4px 4px 0 #fff; 
    }
    
    .btn-outline-street {
        border: 2px solid #fff; color: #fff; font-family: 'Arial Black', sans-serif;
    }
    .btn-outline-street:hover {
        background: #fff; color: #000;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <!-- ЗАГОЛОВОК -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 border-bottom border-4 border-light pb-3">
        <h1 class="display-4 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
            ТВОИ <span class="text-street-yellow">СИГНАЛЫ</span>
        </h1>
        <div class="d-flex gap-3 mt-3 mt-md-0">
            <?php if ($unreadCount > 0): ?>
                <form action="/actions/read_all.php" method="POST">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-black rounded-0 text-uppercase px-4 shadow-sm">
                        <i class="bi bi-check2-all me-2"></i> Прочитать все
                    </button>
                </form>
            <?php endif; ?>
            <a href="/pages/profile.php" class="btn btn-outline-street rounded-0 text-uppercase px-4">
                <i class="bi bi-arrow-left me-2"></i> Профиль
            </a>
        </div>
    </div>

    <!-- СПИСОК -->
    <div class="notif-container">
        <?php if (empty($notifs)): ?>
            <div class="p-5 text-center">
                <i class="bi bi-bell-slash display-1 text-muted mb-3 d-block"></i>
                <h3 class="street-font text-dark">Тишина в эфире</h3>
                <p class="text-muted fw-bold">Новых сообщений пока нет.</p>
                <a href="/pages/topics_list.php" class="btn btn-outline-dark rounded-0 fw-bold text-uppercase px-4 py-2 mt-2 border-2 street-font">
                    Перейти на форум
                </a>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush rounded-0">
                <?php foreach ($notifs as $n): ?>
                    <?php
                        $isReadClass = $n['is_read'] ? 'notif-read' : 'notif-unread';

                        $iconClass = 'bi-info-circle-fill';
                        $iconBg = 'bg-white text-dark';
                        $typeLabel = 'СИСТЕМА';

                        if ($n['type'] === 'reply') {
                            $iconClass = 'bi-chat-quote-fill';
                            $iconBg = 'bg-black text-white';
                            $typeLabel = 'ОТВЕТ В ТЕМЕ';
                        } elseif ($n['type'] === 'order') {
                            $iconClass = 'bi-box-seam-fill';
                            $iconBg = 'bg-warning text-dark';
                            $typeLabel = 'ЗАКАЗ';
                        }
                    ?>

                    <a href="/actions/read_notification.php?id=<?= (int)$n['id'] ?>" class="notif-item p-4 <?= e($isReadClass) ?>">

                        <i class="bi bi-caret-right-fill arrow-indicator"></i>

                        <div class="d-flex align-items-start">
                            <div class="icon-box rounded-0 me-3 <?= e($iconBg) ?>">
                                <i class="bi <?= e($iconClass) ?>"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-uppercase small street-font" style="letter-spacing: 1px;">
                                        <?= e($typeLabel) ?>
                                        <span class="badge bg-danger rounded-0 border border-dark text-uppercase ms-2 new-badge" style="font-size: 0.6rem;">NEW</span>
                                    </strong>
                                    <small class="fw-bold text-muted">
                                        <?= e(date('d.m H:i', strtotime((string)$n['created_at']))) ?>
                                    </small>
                                </div>

                                <p class="mb-0 fw-bold fs-5 lh-sm">
                                    <?= e($n['message']) ?>
                                </p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-4 d-flex justify-content-between align-items-center bg-black border border-2 border-white p-3">
            <?php if ($page > 1): ?>
                <a href="/pages/notifications.php?page=<?= $page - 1 ?>" class="btn btn-outline-light rounded-0 btn-sm street-font">Назад</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <span class="street-font text-street-yellow small">Стр. <?= $page ?> из <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="/pages/notifications.php?page=<?= $page + 1 ?>" class="btn btn-warning text-dark rounded-0 btn-sm street-font fw-bold">Далее</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
