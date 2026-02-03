<?php
// pages/user.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Получаем ID пользователя из URL
$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($viewUserId === 0) {
    echo "<div class='container py-5 text-center text-white'><h3 class='street-font'>Пользователь не найден.</h3></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 1. ПОЛУЧАЕМ ДАННЫЕ ПОЛЬЗОВАТЕЛЯ
$stmt = $pdo->prepare("SELECT id, username, avatar, role, created_at, full_name, city FROM users WHERE id = ?");
$stmt->execute([$viewUserId]);
$viewUser = $stmt->fetch();

if (!$viewUser) {
    echo "<div class='container py-5 text-center text-white'><h3 class='street-font'>Пользователь удален или не существует.</h3></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. СТАТИСТИКА
$stmtTopics = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE user_id = ?");
$stmtTopics->execute([$viewUserId]);
$topicCount = $stmtTopics->fetchColumn();

$stmtPostsCount = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmtPostsCount->execute([$viewUserId]);
$postCount = $stmtPostsCount->fetchColumn();

// 3. ПОСЛЕДНИЕ СООБЩЕНИЯ
$lastPosts = [];
try {
    $stmtLP = $pdo->prepare("
        SELECT p.*, t.title as topic_title 
        FROM posts p 
        LEFT JOIN topics t ON p.topic_id = t.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmtLP->execute([$viewUserId]);
    $lastPosts = $stmtLP->fetchAll();
} catch (PDOException $e) {}

$avatarUrl = (!empty($viewUser['avatar']) && file_exists(__DIR__ . '/../' . $viewUser['avatar'])) ? '/' . $viewUser['avatar'] : null;
?>

<style>
    /* === ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }
    
    .street-font {
        font-family: 'Arial Black', 'Impact', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* === ФОН NEON GRID === */
    .bg-main-anim {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px);
        background-size: 80px 80px; perspective: 500px; transform-style: preserve-3d; animation: grid-move 6s linear infinite; box-shadow: inset 0 0 150px rgba(0,0,0,0.9); z-index: -1;
    }
    @keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }
    .bg-main-anim::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 40px 40px; }

    /* === СТИЛИ ПРОФИЛЯ === */
    .profile-card {
        background: #fff; color: #000;
        border: 3px solid #000;
        box-shadow: 10px 10px 0 #000;
    }
    
    .list-hover-effect { transition: all 0.2s; border-left: 0px solid transparent; }
    .list-hover-effect:hover {
        background: #f8f9fa;
        border-left: 5px solid #000;
        padding-left: 1.5rem !important;
    }
    
    .avatar-box {
        width: 140px; height: 140px;
        border: 3px solid #000;
        border-radius: 50%;
        background: #f8f9fa;
        overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 4px 4px 0 rgba(0,0,0,0.2);
    }
    
    .stat-card {
        border: 3px solid #000;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); }

    .hover-opacity:hover { opacity: 0.7; color: #FCE300 !important; }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <!-- КНОПКА НАЗАД -->
    <div class="mb-4">
        <a href="javascript:history.back()" class="text-decoration-none fw-bold text-light mb-2 d-inline-block hover-opacity small text-uppercase street-font">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>

    <div class="row g-4">
        
        <!-- ЛЕВАЯ КОЛОНКА: ИНФО -->
        <div class="col-12 col-md-4 col-lg-3">
            <div class="profile-card h-100 mb-0 d-flex flex-column">
                <div class="card-header bg-black text-white text-center py-3 street-font border-bottom border-dark">
                    ПРОФИЛЬ
                </div>
                <div class="card-body text-center p-4">
                    
                    <!-- АВАТАР -->
                    <div class="avatar-box">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span class="display-3 street-font text-muted"><?= strtoupper(substr($viewUser['username'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="street-font mb-2 text-break"><?= htmlspecialchars($viewUser['username']) ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge bg-warning text-dark border border-dark rounded-0 px-3 py-1 text-uppercase street-font">
                            <?= strtoupper($viewUser['role']) ?>
                        </span>
                    </div>

                    <div class="text-start border-top border-dark pt-3 mt-3">
                        <?php if (!empty($viewUser['full_name'])): ?>
                            <div class="small text-muted fw-bold text-uppercase street-font">ИМЯ</div>
                            <div class="mb-3 fw-bold fs-5"><?= htmlspecialchars($viewUser['full_name']) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($viewUser['city'])): ?>
                            <div class="small text-muted fw-bold text-uppercase street-font">ГОРОД</div>
                            <div class="mb-3 fw-bold fs-5"><?= htmlspecialchars($viewUser['city']) ?></div>
                        <?php endif; ?>

                        <div class="small text-muted fw-bold text-uppercase street-font">НА ФОРУМЕ С</div>
                        <div class="fw-bold fs-5 font-monospace"><?= date('d.m.Y', strtotime($viewUser['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА: АКТИВНОСТЬ -->
        <div class="col-12 col-md-8 col-lg-9">
            
            <!-- СТАТИСТИКА -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-sm-4">
                    <div class="card stat-card text-center py-4 bg-warning h-100 rounded-0">
                        <div class="display-4 street-font mb-0 lh-1"><?= $topicCount ?></div>
                        <div class="small street-font mt-2">СОЗДАЛ ТЕМ</div>
                    </div>
                </div>
                <div class="col-6 col-sm-4">
                    <div class="card stat-card text-center py-4 bg-white h-100 rounded-0">
                        <div class="display-4 street-font mb-0 lh-1"><?= $postCount ?></div>
                        <div class="small street-font mt-2">НАПИСАЛ ПОСТОВ</div>
                    </div>
                </div>
            </div>

            <!-- ПОСЛЕДНИЕ СООБЩЕНИЯ -->
            <div class="card border-3 border-dark mb-0 profile-card">
                <div class="card-header bg-white text-dark py-3 street-font border-bottom border-dark">
                    ПОСЛЕДНЯЯ АКТИВНОСТЬ
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($lastPosts)): ?>
                        <div class="list-group-item text-center text-muted py-5 border-0 bg-light">
                            <i class="bi bi-chat-square-dots fs-1 d-block mb-2 opacity-25"></i>
                            <span class="street-font small">Пользователь пока молчит...</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($lastPosts as $post): ?>
                        <a href="/pages/topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>" class="list-group-item list-group-item-action border-bottom py-3 list-hover-effect">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="text-truncate small text-uppercase street-font text-dark" style="max-width: 70%;">
                                    <?= htmlspecialchars($post['topic_title'] ?? 'Тема удалена') ?>
                                </strong>
                                <small class="text-muted fw-bold font-monospace" style="font-size: 0.7rem;">
                                    <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>
                                </small>
                            </div>
                            <div class="text-secondary text-truncate small fst-italic">
                                "<?= htmlspecialchars($post['content']) ?>"
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
