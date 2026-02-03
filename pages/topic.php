<?php
// pages/topic.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Получаем тему
$stmt = $pdo->prepare("SELECT t.*, u.username, c.title as cat_title FROM topics t JOIN users u ON t.user_id = u.id JOIN categories c ON t.category_id = c.id WHERE t.id = ?");
$stmt->execute([$topicId]);
$topic = $stmt->fetch();

if (!$topic) {
    echo "<script>window.location='/pages/topics_list.php';</script>";
    exit;
}

// 2. Проверка доступа
if ($topic['is_approved'] == 0) {
    $role = strtolower(trim($_SESSION['role'] ?? 'user'));
    $isAdmin = ($role === 'admin' || $role === 'super_admin');
    $isAuthor = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $topic['user_id']);
    
    if (!$isAdmin && !$isAuthor) {
        echo '<div class="container py-5"><div class="alert alert-danger border-4 border-dark fw-bold text-center street-font">ДОСТУП ЗАПРЕЩЕН: ТЕМА НА МОДЕРАЦИИ</div></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// 3. Получаем посты (Оптимизированный запрос с JOIN уже есть, он корректен)
$postsStmt = $pdo->prepare("SELECT p.*, u.username, u.avatar, u.role, u.created_at as user_since, u.id as user_id_auth FROM posts p JOIN users u ON p.user_id = u.id WHERE p.topic_id = ? ORDER BY p.created_at ASC");
$postsStmt->execute([$topicId]);
$posts = $postsStmt->fetchAll();
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
    .text-street-yellow { color: #FCE300; text-shadow: 2px 2px 0 #000; }

    /* === ФОН NEON GRID === */
    .bg-main-anim {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px);
        background-size: 80px 80px; perspective: 500px; transform-style: preserve-3d; animation: grid-move 6s linear infinite; box-shadow: inset 0 0 150px rgba(0,0,0,0.9); z-index: -1;
    }
    @keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }
    .bg-main-anim::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 40px 40px; }

    /* === СТИЛИ POST === */
    .post-card {
        background: #fff; color: #000;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 rgba(0,0,0,0.5);
    }
    .author-col {
        background: #f0f0f0;
        border-right: 3px solid #000;
    }
    .avatar-img {
        width: 100px; height: 100px; object-fit: cover;
        border: 3px solid #000;
        background: #fff;
    }
    
    .author-link {
        text-decoration: none; color: inherit;
        transition: color 0.2s;
    }
    .author-link:hover { color: #bc13fe; }

    /* === HEADER & FORM === */
    .btn-reply {
        background: #FCE300; border: 3px solid #000; color: #000;
        font-family: 'Arial Black', sans-serif;
    }
    .btn-reply:hover { background: #ffe600; box-shadow: 4px 4px 0 #000; transform: translateY(-2px); }

    .hover-opacity:hover { opacity: 0.7; color: #FCE300 !important; }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <!-- HEADER -->
    <div class="mb-5 pb-3 border-bottom border-white border-2">
        <a href="/pages/topics_list.php" class="text-decoration-none fw-bold text-light mb-2 d-inline-block hover-opacity small text-uppercase street-font">
            <i class="bi bi-arrow-left"></i> Назад в форум
        </a>
        
        <h1 class="display-4 street-font m-0 text-white text-break" style="letter-spacing: 1px;">
            <?= htmlspecialchars($topic['title']) ?>
        </h1>
        
        <div class="mt-3">
            <span class="badge bg-black text-white border border-white rounded-0 fs-6 text-uppercase street-font">
                <?= htmlspecialchars($topic['cat_title']) ?>
            </span>
            <?php if($topic['is_approved'] == 0): ?>
                <span class="badge bg-danger border border-white rounded-0 fs-6 ms-2 text-uppercase street-font">
                    <i class="bi bi-eye-slash-fill"></i> На проверке
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ЛЕНТА СООБЩЕНИЙ -->
    <div class="d-flex flex-column gap-4">
        <?php foreach($posts as $post): ?>
            <?php 
                $avatar = $post['avatar'] ? '/'.$post['avatar'] : 'https://via.placeholder.com/150';
                $roleBadge = in_array($post['role'], ['admin', 'super_admin']) 
                    ? '<span class="badge bg-danger rounded-0 border border-dark mt-2 text-uppercase street-font">ADMIN</span>' 
                    : '<span class="badge bg-white text-dark rounded-0 border border-dark mt-2 text-uppercase street-font">USER</span>';
            ?>
            
            <div class="card post-card rounded-0" id="post-<?= $post['id'] ?>">
                <div class="row g-0">
                    
                    <!-- Автор -->
                    <div class="col-md-3 col-lg-2 author-col p-4 text-center d-flex flex-column align-items-center">
                        <a href="/pages/user.php?id=<?= $post['user_id_auth'] ?>" title="Перейти в профиль">
                            <img src="<?= $avatar ?>" class="img-fluid rounded-circle avatar-img mb-3 shadow-sm">
                        </a>
                        
                        <div class="street-font text-break lh-1 mb-1 fs-5">
                            <a href="/pages/user.php?id=<?= $post['user_id_auth'] ?>" class="author-link">
                                <?= htmlspecialchars($post['username']) ?>
                            </a>
                        </div>
                        
                        <?= $roleBadge ?>
                        
                        <div class="mt-auto pt-3 small text-muted fw-bold font-monospace text-uppercase" style="font-size: 0.65rem;">
                            REG: <?= date('d.m.y', strtotime($post['user_since'])) ?>
                        </div>
                    </div>

                    <!-- Контент -->
                    <div class="col-md-9 col-lg-10 d-flex flex-column bg-white">
                        <div class="p-2 px-3 border-bottom border-2 border-dark bg-light d-flex justify-content-between align-items-center">
                            <small class="fw-bold text-muted text-uppercase street-font" style="font-size: 0.75rem;">
                                <i class="bi bi-clock me-1"></i> <?= date('d.m.Y в H:i', strtotime($post['created_at'])) ?>
                            </small>
                            
                            <!-- Кнопка ответа -->
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-sm btn-link text-dark fw-bold text-decoration-none p-0 text-uppercase street-font" onclick="replyTo('<?= htmlspecialchars($post['username']) ?>')">
                                    <i class="bi bi-reply-fill text-warning"></i> Ответить
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4 flex-grow-1 fw-medium fs-6 text-break" style="min-height: 120px; color: #333;">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ФОРМА ОТВЕТА -->
    <div class="mt-5 pt-4" id="reply-form">
        <?php if($topic['is_approved'] == 1 && isset($_SESSION['user_id'])): ?>
            
            <div class="card rounded-0 border-4 border-white shadow-lg">
                <div class="card-header bg-black text-white rounded-0 py-3 border-bottom border-white">
                    <h4 class="street-font m-0 text-street-yellow">
                        <i class="bi bi-chat-text-fill me-2"></i> Написать ответ
                    </h4>
                </div>
                <div class="card-body p-4 bg-light">
                    <form action="/actions/add_reply.php" method="POST">
                        <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                        
                        <div class="mb-3">
                            <textarea name="content" id="replyTextarea" class="form-control rounded-0 border-3 border-dark fw-bold p-3 fs-5" rows="5" placeholder="Текст сообщения..." required></textarea>
                            <div class="form-text fw-bold text-dark street-font" style="font-size: 0.7rem;">Используйте @username, чтобы упомянуть пользователя.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-reply rounded-0 text-uppercase px-5 py-3 fs-5 w-100 w-md-auto">
                            ОТПРАВИТЬ СООБЩЕНИЕ
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif($topic['is_approved'] == 0): ?>
            <div class="alert alert-warning border-3 border-dark rounded-0 fw-bold text-center py-4 street-font">
                <i class="bi bi-lock-fill fs-3 d-block mb-2"></i>
                ТЕМА НА ПРОВЕРКЕ. ОТВЕЧАТЬ НЕЛЬЗЯ.
            </div>
        <?php elseif(!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-light border-3 border-dark rounded-0 fw-bold text-center py-4 shadow-sm street-font">
                Чтобы ответить, нужно <a href="/pages/login.php" class="text-dark text-decoration-underline">войти</a>.
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function replyTo(username) {
    const textarea = document.getElementById('replyTextarea');
    if (textarea) {
        textarea.value += '@' + username + ' ';
        textarea.focus();
        document.getElementById('reply-form').scrollIntoView({behavior: 'smooth'});
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
