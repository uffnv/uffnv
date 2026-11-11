<?php
// admin/topic_edit.php — Полное редактирование темы и модерация её сообщений
require_once __DIR__ . '/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /admin/topics.php");
    exit;
}

$backSelf = '/admin/topic_edit.php?id=' . $id;

$stmt = $pdo->prepare("
    SELECT t.*, u.username
    FROM topics t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    redirectWithFlash('/admin/topics.php', 'Тема #' . $id . ' не найдена.', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect($backSelf);

    // 1. Удаление конкретного сообщения в теме
    if (isset($_POST['delete_post_id'])) {
        $postId = (int)$_POST['delete_post_id'];
        if ($postId > 0) {
            $pdo->prepare("DELETE FROM posts WHERE id = ? AND topic_id = ?")->execute([$postId, $id]);
            redirectWithFlash($backSelf, 'Сообщение #' . $postId . ' удалено.', 'warning');
        }
    }

    // 2. Сохранение темы
    if (isset($_POST['save_topic'])) {
        $title      = trim((string)($_POST['title'] ?? ''));
        $content    = trim((string)($_POST['content'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $isApproved = isset($_POST['is_approved']) ? 1 : 0;

        if (mb_strlen($title, 'UTF-8') < 3 || mb_strlen($title, 'UTF-8') > 200) {
            redirectWithFlash($backSelf, 'Заголовок темы должен быть от 3 до 200 символов.', 'danger');
        }
        if (mb_strlen($content, 'UTF-8') > 20000) {
            redirectWithFlash($backSelf, 'Текст темы не должен превышать 20 000 символов.', 'danger');
        }

        $catCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
        $catCheck->execute([$categoryId]);
        if (!$catCheck->fetch()) {
            redirectWithFlash($backSelf, 'Выберите существующую категорию форума.', 'danger');
        }

        $pdo->prepare("UPDATE topics SET title = ?, content = ?, category_id = ?, is_approved = ? WHERE id = ?")
            ->execute([$title, $content, $categoryId, $isApproved, $id]);

        if ((int)$topic['is_approved'] === 0 && $isApproved === 1 && !empty($topic['user_id'])) {
            $msg = "Ваша тема '" . mb_substr($title, 0, 50, 'UTF-8') . "' одобрена!";
            $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'topic_approved', ?, ?)")
                ->execute([(int)$topic['user_id'], $msg, '/pages/topic.php?id=' . $id]);
        }

        redirectWithFlash($backSelf, 'Тема обновлена.', 'success');
    }

    // 3. Удаление всей темы
    if (isset($_POST['delete_topic'])) {
        $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$id]);
        redirectWithFlash('/admin/topics.php', 'Тема #' . $id . ' удалена.', 'warning');
    }
}

$cats = $pdo->query("SELECT id, title FROM categories ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$postsStmt = $pdo->prepare("
    SELECT p.*, u.username, u.role
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.topic_id = ?
    ORDER BY p.created_at ASC
");
$postsStmt->execute([$id]);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/layout_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div class="d-flex align-items-center">
        <a href="/admin/topics.php" class="btn btn-outline-dark rounded-0 me-3" aria-label="Назад к темам">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <div>
            <h2 class="fw-black text-uppercase m-0">Тема #<?= (int)$topic['id'] ?></h2>
            <small class="text-muted fw-bold">Автор: @<?= e($topic['username'] ?? 'Удалён') ?> · <?= e(date('d.m.Y H:i', strtotime((string)$topic['created_at']))) ?></small>
        </div>
    </div>
    <a href="/pages/topic.php?id=<?= (int)$topic['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-admin btn-admin-dark">
        <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> Открыть на сайте
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card admin-card p-4 mb-4">
            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3">Параметры темы</h5>
            <form method="POST" data-validate-form data-prevent-double-submit="1" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label for="topicTitle" class="fw-bold small text-muted mb-1">ЗАГОЛОВОК ТЕМЫ *</label>
                    <input id="topicTitle" type="text" name="title" class="form-control"
                           value="<?= e($topic['title']) ?>"
                           data-rule="required-text" data-min="3" data-max="200"
                           minlength="3" maxlength="200" required>
                    <div class="auth-field-error"></div>
                </div>

                <div class="mb-3">
                    <label for="topicCategory" class="fw-bold small text-muted mb-1">КАТЕГОРИЯ ФОРУМА *</label>
                    <select id="topicCategory" name="category_id" class="form-select" required>
                        <?php foreach ($cats as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= (int)$topic['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="topicContent" class="fw-bold small text-muted mb-1">ЗАГЛАВНЫЙ ТЕКСТ ТЕМЫ</label>
                    <textarea id="topicContent" name="content" class="form-control" rows="6" maxlength="20000"><?= e($topic['content'] ?? '') ?></textarea>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_approved" id="topicApprovedSwitch" <?= !empty($topic['is_approved']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="topicApprovedSwitch">Опубликована (одобрена модератором)</label>
                </div>

                <button type="submit" name="save_topic" value="1" class="btn btn-warning btn-admin w-100 py-2">
                    СОХРАНИТЬ ТЕМУ
                </button>
            </form>

            <form method="POST" class="mt-3 pt-3 border-top border-dark" onsubmit="return confirm('Удалить всю тему вместе со всеми ответами навсегда?');">
                <?= csrfField() ?>
                <button type="submit" name="delete_topic" value="1" class="btn btn-admin btn-admin-danger w-100 py-2">
                    <i class="bi bi-trash-fill me-1" aria-hidden="true"></i> УДАЛИТЬ ТЕМУ НАВСЕГДА
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card admin-card p-0">
            <div class="card-header bg-black text-white fw-bold py-3 px-4">
                СООБЩЕНИЯ В ТЕМЕ (<?= count($posts) ?>)
            </div>
            <div class="table-responsive">
                <table class="table table-admin table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Автор</th>
                            <th>Сообщение</th>
                            <th>Дата</th>
                            <th class="text-end">Удалить</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted fw-bold">Ответов в теме пока нет.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td class="fw-bold">#<?= (int)$p['id'] ?></td>
                                    <td>
                                        <?php if (!empty($p['user_id'])): ?>
                                            <a href="/admin/user_edit.php?id=<?= (int)$p['user_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                @<?= e($p['username'] ?? 'Удалён') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Удалён</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 340px;">
                                        <div class="text-break small fw-normal"><?= e(mb_strimwidth((string)$p['content'], 0, 220, '...', 'UTF-8')) ?></div>
                                    </td>
                                    <td class="small text-muted text-nowrap"><?= e(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Удалить сообщение #<?= (int)$p['id'] ?>?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="delete_post_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger rounded-0" aria-label="Удалить сообщение #<?= (int)$p['id'] ?>">
                                                <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
