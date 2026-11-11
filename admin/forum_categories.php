<?php
// admin/forum_categories.php
require_once __DIR__ . '/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect('/admin/forum_categories.php');

    $resolveSectionId = static function (\PDO $pdo, $raw): ?int {
        $sid = !empty($raw) ? (int)$raw : 0;
        if ($sid <= 0) {
            return null;
        }
        $chk = $pdo->prepare("SELECT id FROM sections WHERE id = ?");
        $chk->execute([$sid]);
        return $chk->fetchColumn() ? $sid : null;
    };

    if (isset($_POST['add_cat'])) {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $secId = $resolveSectionId($pdo, $_POST['section_id'] ?? null);
        $len   = mb_strlen($title, 'UTF-8');
        if ($len >= 2 && $len <= 120 && mb_strlen($desc, 'UTF-8') <= 500) {
            $stmt = $pdo->prepare("INSERT INTO categories (title, description, section_id, is_approved) VALUES (?, ?, ?, 1)");
            $stmt->execute([$title, $desc, $secId]);
            redirectWithFlash('/admin/forum_categories.php', 'Категория форума создана.', 'success');
        }
        redirectWithFlash('/admin/forum_categories.php', 'Название должно содержать 2–120 символов, описание — до 500.', 'danger');
    }

    if (isset($_POST['delete_cat'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            redirectWithFlash('/admin/forum_categories.php', 'Категория удалена.', 'warning');
        }
    }

    if (isset($_POST['toggle_approve'])) {
        $id = (int)($_POST['id'] ?? 0);
        $curr = (int)($_POST['current_val'] ?? 0);
        if ($id > 0) {
            $new = $curr ? 0 : 1;
            $pdo->prepare("UPDATE categories SET is_approved = ? WHERE id = ?")->execute([$new, $id]);
            redirectWithFlash('/admin/forum_categories.php', 'Статус категории обновлён.', 'success');
        }
    }

    if (isset($_POST['update_cat'])) {
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $secId = $resolveSectionId($pdo, $_POST['section_id'] ?? null);
        $len   = mb_strlen($title, 'UTF-8');
        if ($id > 0 && $len >= 2 && $len <= 120 && mb_strlen($desc, 'UTF-8') <= 500) {
            $stmt = $pdo->prepare("UPDATE categories SET title = ?, description = ?, section_id = ? WHERE id = ?");
            $stmt->execute([$title, $desc, $secId, $id]);
            redirectWithFlash('/admin/forum_categories.php', 'Категория обновлена.', 'success');
        }
        redirectWithFlash('/admin/forum_categories.php', 'Название должно содержать 2–120 символов, описание — до 500.', 'danger');
    }

    redirectWithFlash('/admin/forum_categories.php', 'Проверьте корректность данных.', 'danger');
}

require_once __DIR__ . '/layout_header.php';

$categories = $pdo->query("
    SELECT c.*, s.title as section_title,
           (SELECT COUNT(*) FROM topics t WHERE t.category_id = c.id) AS topics_count
    FROM categories c
    LEFT JOIN sections s ON c.section_id = s.id
    ORDER BY c.id DESC
")->fetchAll();
$sections = $pdo->query("SELECT * FROM sections ORDER BY sort_order ASC")->fetchAll();
?>

<h2 class="street-font mb-4">Категории Форума</h2>

<div class="row">
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-header bg-warning text-dark">Новая категория</div>
            <div class="p-3">
                <form method="POST" data-validate-form data-prevent-double-submit="1" novalidate>
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label for="newCatSection" class="fw-bold small">Раздел (Группа)</label>
                        <select id="newCatSection" name="section_id" class="form-select">
                            <option value="">-- Без раздела --</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= (int)$sec['id'] ?>"><?= e($sec['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="newCatTitle" class="fw-bold small">Название</label>
                        <input id="newCatTitle" type="text" name="title" class="form-control" required maxlength="120" data-rule="required-text" data-min="2" data-max="120">
                        <div class="auth-field-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newCatDesc" class="fw-bold small">Описание</label>
                        <textarea id="newCatDesc" name="description" class="form-control" rows="3" maxlength="500" data-max="500"></textarea>
                        <div class="auth-field-error"></div>
                    </div>
                    <button type="submit" name="add_cat" value="1" class="btn btn-admin btn-admin-dark w-100">Создать</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="admin-card">
            <div class="admin-header">Все категории</div>
            <div class="table-responsive">
                <table class="table table-admin m-0">
                    <thead>
                        <tr>
                            <th>Раздел</th>
                            <th>Категория / Описание</th>
                            <th>Тем</th>
                            <th>Статус</th>
                            <th style="width: 150px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c):
                            $fid = 'forumCatForm' . (int)$c['id'];
                        ?>
                        <tr>
                            <td>
                                <select form="<?= $fid ?>" name="section_id" class="form-select form-select-sm" style="width: 120px;" aria-label="Раздел">
                                    <option value="">-</option>
                                    <?php foreach ($sections as $s): ?>
                                        <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === (int)$c['section_id'] ? 'selected' : '' ?>>
                                            <?= e($s['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input form="<?= $fid ?>" type="text" name="title" class="form-control form-control-sm fw-bold mb-1" value="<?= e($c['title']) ?>" required maxlength="120" data-rule="required-text" data-min="2" data-max="120" aria-label="Название категории">
                                <div class="auth-field-error"></div>
                                <textarea form="<?= $fid ?>" name="description" class="form-control form-control-sm text-muted" rows="1" maxlength="500" aria-label="Описание категории"><?= e($c['description'] ?? '') ?></textarea>
                            </td>
                            <td><span class="badge bg-dark rounded-0"><?= (int)$c['topics_count'] ?></span></td>
                            <td>
                                <?php if (!empty($c['is_approved'])): ?>
                                    <span class="badge bg-success rounded-0">Активна</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-0">Скрыта</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button form="<?= $fid ?>" type="submit" name="update_cat" value="1" class="btn btn-sm btn-admin btn-admin-dark" title="Сохранить" aria-label="Сохранить"><i class="bi bi-save" aria-hidden="true"></i></button>
                                    <button form="<?= $fid ?>" type="submit" name="toggle_approve" value="1" data-skip-validate="1" class="btn btn-sm btn-admin <?= !empty($c['is_approved']) ? 'btn-warning' : 'btn-success' ?>" title="Вкл/Выкл" aria-label="Переключить статус"><i class="bi bi-power" aria-hidden="true"></i></button>
                                    <button form="<?= $fid ?>" type="submit" name="delete_cat" value="1" data-skip-validate="1" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить? Все темы в категории тоже пропадут!')" title="Удалить" aria-label="Удалить"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach ($categories as $c): ?>
    <form method="POST" id="forumCatForm<?= (int)$c['id'] ?>" class="d-none" data-validate-form novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <input type="hidden" name="current_val" value="<?= (int)$c['is_approved'] ?>">
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
