<?php require_once 'layout_header.php'; ?>

<?php
// ЛОГИКА
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cat'])) {
        $stmt = $pdo->prepare("INSERT INTO categories (title, description, section_id, is_approved) VALUES (?, ?, ?, 1)");
        $secId = !empty($_POST['section_id']) ? $_POST['section_id'] : NULL;
        $stmt->execute([$_POST['title'], $_POST['description'], $secId]);
    }
    if (isset($_POST['delete_cat'])) {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_POST['id']]);
    }
    if (isset($_POST['toggle_approve'])) {
        $curr = (int)$_POST['current_val'];
        $new = $curr ? 0 : 1;
        $pdo->prepare("UPDATE categories SET is_approved = ? WHERE id = ?")->execute([$new, (int)$_POST['id']]);
    }
    if (isset($_POST['update_cat'])) {
        $stmt = $pdo->prepare("UPDATE categories SET title = ?, description = ?, section_id = ? WHERE id = ?");
        $secId = !empty($_POST['section_id']) ? $_POST['section_id'] : NULL;
        $stmt->execute([$_POST['title'], $_POST['description'], $secId, (int)$_POST['id']]);
    }
}

// Данные
$categories = $pdo->query("SELECT c.*, s.title as section_title FROM categories c LEFT JOIN sections s ON c.section_id = s.id ORDER BY c.id DESC")->fetchAll();
$sections = $pdo->query("SELECT * FROM sections ORDER BY sort_order ASC")->fetchAll();
?>

<h2 class="street-font mb-4">Категории Форума</h2>

<div class="row">
    <!-- ДОБАВЛЕНИЕ -->
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-header bg-warning text-dark">Новая категория</div>
            <div class="p-3">
                <form method="POST">
                    <div class="mb-3">
                        <label class="fw-bold small">Раздел (Группа)</label>
                        <select name="section_id" class="form-select">
                            <option value="">-- Без раздела --</option>
                            <?php foreach($sections as $sec): ?>
                                <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Название</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Описание</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_cat" class="btn btn-admin btn-admin-dark w-100">Создать</button>
                </form>
            </div>
        </div>
    </div>

    <!-- СПИСОК -->
    <div class="col-md-8">
        <div class="admin-card">
            <div class="admin-header">Все категории</div>
            <div class="table-responsive">
                <table class="table table-admin m-0">
                    <thead>
                        <tr>
                            <th>Раздел</th>
                            <th>Категория / Описание</th>
                            <th>Статус</th>
                            <th style="width: 150px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $c): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <td>
                                    <select name="section_id" class="form-select form-select-sm" style="width: 120px;">
                                        <option value="">-</option>
                                        <?php foreach($sections as $s): ?>
                                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $c['section_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="title" class="form-control form-control-sm fw-bold mb-1" value="<?= htmlspecialchars($c['title']) ?>">
                                    <textarea name="description" class="form-control form-control-sm text-muted" rows="1"><?= htmlspecialchars($c['description']) ?></textarea>
                                </td>
                                <td>
                                    <?php if($c['is_approved']): ?>
                                        <span class="badge bg-success rounded-0">Активна</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-0">Скрыта</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="submit" name="update_cat" class="btn btn-sm btn-admin btn-admin-dark" title="Сохранить"><i class="bi bi-save"></i></button>
                                        
                                        <button type="submit" name="toggle_approve" value="1" class="btn btn-sm btn-admin <?= $c['is_approved'] ? 'btn-warning' : 'btn-success' ?>" title="Вкл/Выкл">
                                            <input type="hidden" name="current_val" value="<?= $c['is_approved'] ?>">
                                            <i class="bi bi-power"></i>
                                        </button>
                                        
                                        <button type="submit" name="delete_cat" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить? Все темы в категории тоже пропадут!')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
