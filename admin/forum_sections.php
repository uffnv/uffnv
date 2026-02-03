<?php require_once 'layout_header.php'; ?>

<?php
// ОБРАБОТКА ФОРМЫ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_section'])) {
        $stmt = $pdo->prepare("INSERT INTO sections (title, sort_order) VALUES (?, ?)");
        $stmt->execute([$_POST['title'], (int)$_POST['sort_order']]);
    }
    if (isset($_POST['delete_section'])) {
        // Сбрасываем категории в NULL перед удалением раздела
        $pdo->prepare("UPDATE categories SET section_id = NULL WHERE section_id = ?")->execute([(int)$_POST['id']]);
        $pdo->prepare("DELETE FROM sections WHERE id = ?")->execute([(int)$_POST['id']]);
    }
    if (isset($_POST['edit_section'])) {
        $stmt = $pdo->prepare("UPDATE sections SET title = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$_POST['title'], (int)$_POST['sort_order'], (int)$_POST['id']]);
    }
}

$sections = $pdo->query("SELECT * FROM sections ORDER BY sort_order ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">Разделы Форума</h2>
</div>

<div class="row">
    <!-- ФОРМА ДОБАВЛЕНИЯ -->
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-header">Создать раздел</div>
            <div class="p-3">
                <form method="POST">
                    <div class="mb-3">
                        <label class="fw-bold small">Название</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Порядок (сортировка)</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <button type="submit" name="add_section" class="btn btn-admin btn-admin-primary w-100">Добавить</button>
                </form>
            </div>
        </div>
    </div>

    <!-- СПИСОК -->
    <div class="col-md-8">
        <div class="admin-card">
            <div class="admin-header">Список разделов</div>
            <div class="p-0 table-responsive">
                <table class="table table-admin m-0 table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Порядок</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sections as $s): ?>
                        <tr>
                            <form method="POST">
                                <td>#<?= $s['id'] ?><input type="hidden" name="id" value="<?= $s['id'] ?>"></td>
                                <td><input type="text" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($s['title']) ?>"></td>
                                <td><input type="number" name="sort_order" class="form-control form-control-sm" value="<?= $s['sort_order'] ?>" style="width: 70px;"></td>
                                <td>
                                    <button type="submit" name="edit_section" class="btn btn-sm btn-admin btn-admin-dark" title="Сохранить"><i class="bi bi-save"></i></button>
                                    <button type="submit" name="delete_section" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить?')" title="Удалить"><i class="bi bi-trash"></i></button>
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
