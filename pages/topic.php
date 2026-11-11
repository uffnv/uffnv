<?php
// pages/topic.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($topicId <= 0) {
    header("Location: /pages/topics_list.php");
    exit;
}

// Обработка быстрого бана/разбана пользователя В ЭТОЙ ТЕМЕ (только для админов)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic_ban_action']) && isAdmin()) {
    $backSelf = '/pages/topic.php?id=' . $topicId;
    requireCsrfOrRedirect($backSelf);

    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $actionType   = $_POST['topic_ban_action'] ?? '';
    $actorId      = (int)$_SESSION['user_id'];

    if ($targetUserId > 0 && $targetUserId !== $actorId) {
        $uStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$targetUserId]);
        $targetRole = (string)($uStmt->fetchColumn() ?: '');

        if ($targetRole !== '' && canManageTargetUser($_SESSION['role'] ?? 'admin', $targetRole)) {
            if (!hasUserBansTopicColumn($pdo)) {
                redirectWithFlash($backSelf, 'Для темового бана сначала выполните SQL-миграцию (колонка topic_id в user_bans).', 'warning');
            }

            if ($actionType === 'ban_topic') {
                $hours  = (int)($_POST['duration_hours'] ?? 24);
                $reason = trim($_POST['reason'] ?? 'Нарушение правил в теме');
                $expiresAt = $hours > 0 ? date('Y-m-d H:i:s', time() + $hours * 3600) : null;

                $pdo->prepare("INSERT INTO user_bans (user_id, topic_id, reason, banned_by_id, expires_at) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$targetUserId, $topicId, $reason, $actorId, $expiresAt]);

                redirectWithFlash($backSelf, 'Пользователю ограничен доступ к ответам в этой теме.', 'warning');
            } elseif ($actionType === 'unban_topic') {
                $pdo->prepare("UPDATE user_bans SET expires_at = NOW() WHERE user_id = ? AND topic_id = ? AND (expires_at IS NULL OR expires_at > NOW())")
                    ->execute([$targetUserId, $topicId]);
                redirectWithFlash($backSelf, 'Ограничение в этой теме снято.', 'success');
            }
        }
    }
    redirectWithFlash($backSelf, 'Недостаточно прав для модерации этого пользователя.', 'danger');
}

// 1. Получаем тему
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.avatar as author_avatar, u.role as author_role, u.created_at as author_since, c.title as cat_title
    FROM topics t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$topicId]);
$topic = $stmt->fetch();

if (!$topic) {
    header("Location: /pages/topics_list.php");
    exit;
}

// 2. Проверка доступа к неодобренной теме
if ((int)$topic['is_approved'] === 0) {
    $isAuthor = !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$topic['user_id'];
    if (!isAdmin() && !$isAuthor) {
        $pageTitle = 'Тема на модерации';
        require_once __DIR__ . '/../includes/header.php';
        echo '<div class="bg-main-anim"></div><div class="py-5 position-relative" style="z-index: 2;"><div class="container py-5"><div class="alert alert-danger border-4 border-dark rounded-0 fw-bold text-center text-uppercase">ДОСТУП ЗАПРЕЩЕН: ТЕМА НА МОДЕРАЦИИ</div></div></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// 3. Получаем ответы
$postsStmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, u.role, u.created_at as user_since, u.id as user_id_auth
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.topic_id = ?
    ORDER BY p.created_at ASC
");
$postsStmt->execute([$topicId]);
$posts = $postsStmt->fetchAll();

// Проверка активного бана текущего пользователя (общий форумный или локальный в этой теме)
$currentForumBan = !empty($_SESSION['user_id'])
    ? getActiveForumBan($pdo, (int)$_SESSION['user_id'], $topicId)
    : null;

$canBanInTopic = isAdmin() && hasUserBansTopicColumn($pdo);

$pageTitle = $topic['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .street-font {
        font-family: 'Arial Black', 'Impact', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .text-street-yellow { color: #FCE300; text-shadow: 2px 2px 0 #000; }
    .post-card {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 rgba(0, 0, 0, 0.5);
    }
    .author-col {
        background: #f0f0f0;
        border-right: 3px solid #000;
    }
    .avatar-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 3px solid #000;
        background: #fff;
    }
    .author-link { text-decoration: none; color: inherit; transition: color 0.2s; }
    .author-link:hover { color: #bc13fe; }
    .btn-reply { background: #FCE300; border: 3px solid #000; color: #000; font-family: 'Arial Black', sans-serif; }
    .btn-reply:hover { background: #ffe600; box-shadow: 4px 4px 0 #000; transform: translateY(-2px); }
    .hover-opacity:hover { opacity: 0.7; color: #FCE300 !important; }
</style>

<div class="bg-main-anim"></div>

<div class="py-5 position-relative" style="z-index: 2;">
    <div class="container">

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-<?= e($_SESSION['flash_type'] ?? 'info') ?> border-3 border-dark rounded-0 fw-bold mb-4">
                <?= e($_SESSION['flash']) ?>
            </div>
            <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- HEADER -->
        <div class="mb-5 pb-3 border-bottom border-white border-2">
            <a href="/pages/topics_list.php" class="text-decoration-none fw-bold text-light mb-2 d-inline-block hover-opacity small text-uppercase street-font">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Назад в форум
            </a>

            <h1 class="display-4 street-font m-0 text-white text-break" style="letter-spacing: 1px;">
                <?= e($topic['title']) ?>
            </h1>

            <div class="mt-3">
                <span class="badge bg-black text-white border border-white rounded-0 fs-6 text-uppercase street-font">
                    <?= e($topic['cat_title'] ?? 'Без категории') ?>
                </span>
                <?php if ((int)$topic['is_approved'] === 0): ?>
                    <span class="badge bg-danger border border-white rounded-0 fs-6 ms-2 text-uppercase street-font">
                        <i class="bi bi-eye-slash-fill" aria-hidden="true"></i> На проверке
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ЗАГЛАВНЫЙ ПОСТ ТЕМЫ (topics.content) -->
        <?php if (!empty($topic['content'])):
            $opName = $topic['username'] ?? 'Удалён';
            $opAvatar = getAvatarUrl($topic['author_avatar'] ?? '');
            $opIsAdmin = in_array($topic['author_role'] ?? 'user', ['admin', 'super_admin'], true);
        ?>
        <div class="card post-card rounded-0 mb-4 border-warning" style="border-width: 4px;" id="topic-op">
            <div class="row g-0">
                <div class="col-md-3 col-lg-2 author-col p-4 text-center d-flex flex-column align-items-center">
                    <?php if (!empty($topic['user_id'])): ?>
                        <a href="/pages/user.php?id=<?= (int)$topic['user_id'] ?>" title="Профиль автора">
                    <?php endif; ?>
                    <?php if ($opAvatar): ?>
                        <img src="<?= e($opAvatar) ?>" alt="<?= e($opName) ?>" class="img-fluid rounded-circle avatar-img mb-3 shadow-sm">
                    <?php else: ?>
                        <div class="rounded-circle avatar-img mb-3 shadow-sm d-flex align-items-center justify-content-center bg-black text-warning fs-1 fw-black"><?= e(safeFirstChar($opName)) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($topic['user_id'])): ?>
                        </a>
                    <?php endif; ?>

                    <div class="street-font text-break lh-1 mb-1 fs-5">
                        <?php if (!empty($topic['user_id'])): ?>
                            <a href="/pages/user.php?id=<?= (int)$topic['user_id'] ?>" class="author-link"><?= e($opName) ?></a>
                        <?php else: ?>
                            <?= e($opName) ?>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?= $opIsAdmin ? 'bg-danger' : 'bg-warning text-dark' ?> rounded-0 border border-dark mt-2 text-uppercase street-font">АВТОР ТЕМЫ</span>
                </div>

                <div class="col-md-9 col-lg-10 d-flex flex-column bg-white">
                    <div class="p-2 px-3 border-bottom border-2 border-dark bg-warning bg-opacity-25 d-flex justify-content-between align-items-center">
                        <small class="fw-bold text-dark text-uppercase street-font" style="font-size: 0.75rem;">
                            <i class="bi bi-clock me-1" aria-hidden="true"></i> <?= date('d.m.Y в H:i', strtotime($topic['created_at'])) ?>
                        </small>
                        <?php if (!empty($_SESSION['user_id']) && !$currentForumBan && (int)$topic['is_approved'] === 1): ?>
                            <button type="button"
                                    class="btn btn-sm btn-link text-dark fw-bold text-decoration-none p-0 text-uppercase street-font js-reply-btn"
                                    data-username="<?= e($opName) ?>">
                                <i class="bi bi-reply-fill text-warning" aria-hidden="true"></i> Ответить
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 flex-grow-1 fw-medium fs-5 text-break" style="min-height: 120px; color: #111;">
                        <?= nl2br(e($topic['content'])) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ЛЕНТА СООБЩЕНИЙ -->
        <div class="d-flex flex-column gap-4">
            <?php foreach ($posts as $post):
                $postName = $post['username'] ?? 'Удалён';
                $avatar = getAvatarUrl($post['avatar'] ?? '');
                $postRole = $post['role'] ?? 'user';
                $roleBadge = in_array($postRole, ['admin', 'super_admin'], true)
                    ? '<span class="badge bg-danger rounded-0 border border-dark mt-2 text-uppercase street-font">ADMIN</span>'
                    : '<span class="badge bg-white text-dark rounded-0 border border-dark mt-2 text-uppercase street-font">USER</span>';
                $postUserId = (int)($post['user_id_auth'] ?? 0);
            ?>
                <div class="card post-card rounded-0" id="post-<?= (int)$post['id'] ?>">
                    <div class="row g-0">
                        <div class="col-md-3 col-lg-2 author-col p-4 text-center d-flex flex-column align-items-center">
                            <?php if ($postUserId > 0): ?>
                                <a href="/pages/user.php?id=<?= $postUserId ?>" title="Перейти в профиль">
                            <?php endif; ?>
                            <?php if ($avatar): ?>
                                <img src="<?= e($avatar) ?>" alt="<?= e($postName) ?>" class="img-fluid rounded-circle avatar-img mb-3 shadow-sm">
                            <?php else: ?>
                                <div class="rounded-circle avatar-img mb-3 shadow-sm d-flex align-items-center justify-content-center bg-black text-warning fs-1 fw-black"><?= e(safeFirstChar($postName)) ?></div>
                            <?php endif; ?>
                            <?php if ($postUserId > 0): ?>
                                </a>
                            <?php endif; ?>

                            <div class="street-font text-break lh-1 mb-1 fs-5">
                                <?php if ($postUserId > 0): ?>
                                    <a href="/pages/user.php?id=<?= $postUserId ?>" class="author-link"><?= e($postName) ?></a>
                                <?php else: ?>
                                    <?= e($postName) ?>
                                <?php endif; ?>
                            </div>

                            <?= $roleBadge ?>

                            <?php if (!empty($post['user_since'])): ?>
                                <div class="mt-auto pt-3 small text-muted fw-bold font-monospace text-uppercase" style="font-size: 0.65rem;">
                                    REG: <?= date('d.m.y', strtotime($post['user_since'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-9 col-lg-10 d-flex flex-column bg-white">
                            <div class="p-2 px-3 border-bottom border-2 border-dark bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <small class="fw-bold text-muted text-uppercase street-font" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i> <?= date('d.m.Y в H:i', strtotime($post['created_at'])) ?>
                                </small>

                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($canBanInTopic && $postUserId > 0 && $postUserId !== (int)$_SESSION['user_id'] && canManageTargetUser($_SESSION['role'] ?? 'admin', $postRole)): ?>
                                        <form method="POST" class="d-inline-flex align-items-center gap-1">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="target_user_id" value="<?= $postUserId ?>">
                                            <select name="duration_hours" class="form-select form-select-sm rounded-0 border-dark" style="width: auto; font-size: 0.72rem;" aria-label="Срок бана в теме">
                                                <option value="24">Бан в теме 24ч</option>
                                                <option value="168">Бан в теме 7д</option>
                                                <option value="0">Бан в теме навсегда</option>
                                            </select>
                                            <button type="submit" name="topic_ban_action" value="ban_topic" class="btn btn-sm btn-danger rounded-0 py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="Заблокировать ответы в этой теме">БАН</button>
                                            <button type="submit" name="topic_ban_action" value="unban_topic" class="btn btn-sm btn-outline-success rounded-0 py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="Снять бан в этой теме">СНЯТЬ</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($_SESSION['user_id']) && !$currentForumBan && (int)$topic['is_approved'] === 1): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-link text-dark fw-bold text-decoration-none p-0 text-uppercase street-font js-reply-btn"
                                                data-username="<?= e($postName) ?>">
                                            <i class="bi bi-reply-fill text-warning" aria-hidden="true"></i> Ответить
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="p-4 flex-grow-1 fw-medium fs-6 text-break" style="min-height: 120px; color: #333;">
                                <?= nl2br(e($post['content'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ФОРМА ОТВЕТА -->
        <div class="mt-5 pt-4" id="reply-form">
            <?php if ((int)$topic['is_approved'] === 0): ?>
                <div class="alert alert-warning border-3 border-dark rounded-0 fw-bold text-center py-4 street-font">
                    <i class="bi bi-lock-fill fs-3 d-block mb-2" aria-hidden="true"></i>
                    ТЕМА НА ПРОВЕРКЕ. ОТВЕЧАТЬ НЕЛЬЗЯ.
                </div>
            <?php elseif (empty($_SESSION['user_id'])): ?>
                <div class="alert alert-light border-3 border-dark rounded-0 fw-bold text-center py-4 shadow-sm street-font">
                    Чтобы ответить, нужно <a href="/pages/login.php" class="text-dark text-decoration-underline">войти</a>.
                </div>
            <?php elseif ($currentForumBan): ?>
                <div class="alert alert-danger border-3 border-dark rounded-0 fw-bold text-center py-4 shadow-sm">
                    <i class="bi bi-slash-circle-fill fs-3 d-block mb-2" aria-hidden="true"></i>
                    <div class="street-font mb-1">
                        Вам ограничен доступ к ответам <?= !empty($currentForumBan['topic_id']) ? 'в этой теме' : 'на форуме' ?>
                        <?= !empty($currentForumBan['expires_at']) ? 'до ' . e(date('d.m.Y H:i', strtotime($currentForumBan['expires_at']))) : 'бессрочно' ?>
                    </div>
                    <?php if (!empty($currentForumBan['reason'])): ?>
                        <div class="small">Причина: <?= e($currentForumBan['reason']) ?></div>
                    <?php endif; ?>
                    <div class="small mt-2 text-dark">Заказы и покупки в магазине остаются полностью доступны.</div>
                </div>
            <?php else: ?>
                <div class="card rounded-0 border-4 border-white shadow-lg">
                    <div class="card-header bg-black text-white rounded-0 py-3 border-bottom border-white">
                        <h4 class="street-font m-0 text-street-yellow">
                            <i class="bi bi-chat-text-fill me-2" aria-hidden="true"></i> Написать ответ
                        </h4>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <form action="/actions/add_reply.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="topic_id" value="<?= $topicId ?>">

                            <div class="mb-3">
                                <label for="replyTextarea" class="visually-hidden">Текст сообщения</label>
                                <textarea name="content" id="replyTextarea" class="form-control rounded-0 border-3 border-dark fw-bold p-3 fs-5" rows="5" placeholder="Текст сообщения..." required></textarea>
                                <div class="form-text fw-bold text-dark street-font" style="font-size: 0.7rem;">Используйте @username, чтобы упомянуть пользователя.</div>
                            </div>

                            <button type="submit" class="btn btn-reply rounded-0 text-uppercase px-5 py-3 fs-5 w-100 w-md-auto">
                                ОТПРАВИТЬ СООБЩЕНИЕ
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-reply-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const username = this.dataset.username || '';
            const textarea = document.getElementById('replyTextarea');
            if (textarea && username) {
                textarea.value += '@' + username + ' ';
                textarea.focus();
                document.getElementById('reply-form').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
