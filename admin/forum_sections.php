<?php
// admin/forum_sections.php
require_once __DIR__ . '/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect('/admin/forum_sections.php');

    if (isset($_POST['add_section'])) {
        $title = trim($_POST['title'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        if ($title !== '') {
            $pdo->prepare("INSERT INTO sections (title, sort_order) VALUES (?, ?)")->execute([$title, $sortOrder]);
            redirectWithFlash('/admin/forum_sections.php', 'Раздел создан.', 'success');
        }
    }

    if (isset($_POST['delete_section'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE categories SET section_id = NULL WHERE section_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM sections WHERE id = ?")->execute([$id]);
            redirectWithFlash('/admin/forum_sections.php', 'Раздел удалён.', 'warning');
        }
    }

    if (isset($_POST['edit_section'])) {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        if ($id > 0 && $title !== '') {
            $pdo->prepare("UPDATE sections SET title = ?, sort_order = ? WHERE id = ?")->execute([$title, $sortOrder, $id]);
            redirectWithFlash('/admin/forum_sections.php', 'Раздел обновлён.', 'success');
        }
    }

    redirectWithFlash('/admin/forum_sections.php', 'Проверьте название раздела.', 'danger');
}

require_once __DIR__ . '/layout_header.php';
$sections = $pdo->query("SELECT * FROM sections ORDER BY sort_order ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">Разделы Форума</h2>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-header">Создать раздел</div>
            <div class="p-3">
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label for="secTitle" class="fw-bold small">Название</label>
                        <input id="secTitle" type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="secSort" class="fw-bold small">Порядок (сортировка)</label>
                        <input id="secSort" type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <button type="submit" name="add_section" value="1" class="btn btn-admin btn-admin-primary w-100">Добавить</button>
                </form>
            </div>
        </div>
    </div>

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
                        <?php foreach ($sections as $s):
                            $fid = 'secForm' . (int)$s['id'];
                        ?>
                        <tr>
                            <td>#<?= (int)$s['id'] ?></td>
                            <td><input form="<?= $fid ?>" type="text" name="title" class="form-control form-control-sm" value="<?= e($s['title']) ?>" required aria-label="Название раздела"></td>
                            <td><input form="<?= $fid ?>" type="number" name="sort_order" class="form-control form-control-sm" value="<?= (int)$s['sort_order'] ?>" style="width: 70px;" aria-label="Порядок сортировки"></td>
                            <td>
                                <button form="<?= $fid ?>" type="submit" name="edit_section" value="1" class="btn btn-sm btn-admin btn-admin-dark" title="Сохранить" aria-label="Сохранить"><i class="bi bi-save" aria-hidden="true"></i></button>
                                <button form="<?= $fid ?>" type="submit" name="delete_section" value="1" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить раздел?')" title="Удалить" aria-label="Удалить"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach ($sections as $s): ?>
    <form method="POST" id="secForm<?= (int)$s['id'] ?>" class="d-none">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
