<?php
// admin/shop_categories.php
require_once __DIR__ . '/admin_auth.php';

function createSlug(string $str): string {
    $tr = [
        "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g","Д"=>"d","Е"=>"e","Ё"=>"yo","Ж"=>"zh","З"=>"z","И"=>"i","Й"=>"j",
        "К"=>"k","Л"=>"l","М"=>"m","Н"=>"n","О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t","У"=>"u","Ф"=>"f",
        "Х"=>"kh","Ц"=>"ts","Ч"=>"ch","Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"y","Ь"=>"","Э"=>"e","Ю"=>"yu","Я"=>"ya",
        "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"j",
        "к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f",
        "х"=>"kh","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"","ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
        " "=>"-", "."=>"", ","=>"", "/"=>"-", ":"=>"", ";"=>"", "—"=>"-", "–"=>"-"
    ];
    $str = strtr($str, $tr);
    $str = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $str));
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect('/admin/shop_categories.php');

    if (isset($_POST['add_cat'])) {
        $title = trim($_POST['title'] ?? '');
        $len = mb_strlen($title, 'UTF-8');
        if ($len >= 2 && $len <= 120) {
            $slug = createSlug($title) ?: ('cat-' . time());
            $check = $pdo->prepare("SELECT id FROM shop_categories WHERE slug = ? LIMIT 1");
            $check->execute([$slug]);
            if ($check->fetch()) {
                $slug .= '-' . time();
            }
            try {
                $pdo->prepare("INSERT INTO shop_categories (title, slug) VALUES (?, ?)")->execute([$title, $slug]);
                redirectWithFlash('/admin/shop_categories.php', 'Категория добавлена.', 'success');
            } catch (\PDOException $e) {
                error_log("shop_categories add error: " . $e->getMessage());
                redirectWithFlash('/admin/shop_categories.php', 'Ошибка сохранения категории.', 'danger');
            }
        }
        redirectWithFlash('/admin/shop_categories.php', 'Название категории должно содержать 2–120 символов.', 'danger');
    }

    if (isset($_POST['delete_cat'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE products SET category_id = 0 WHERE category_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM shop_categories WHERE id = ?")->execute([$id]);
            redirectWithFlash('/admin/shop_categories.php', 'Категория удалена.', 'warning');
        }
    }

    if (isset($_POST['edit_cat'])) {
        $title = trim($_POST['title'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        $len = mb_strlen($title, 'UTF-8');
        if ($id > 0 && $len >= 2 && $len <= 120) {
            $slug = createSlug($title) ?: ('cat-' . time());
            $check = $pdo->prepare("SELECT id FROM shop_categories WHERE slug = ? AND id != ? LIMIT 1");
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                $slug .= '-' . time();
            }
            $pdo->prepare("UPDATE shop_categories SET title = ?, slug = ? WHERE id = ?")->execute([$title, $slug, $id]);
            redirectWithFlash('/admin/shop_categories.php', 'Категория обновлена.', 'success');
        }
        redirectWithFlash('/admin/shop_categories.php', 'Название категории должно содержать 2–120 символов.', 'danger');
    }

    redirectWithFlash('/admin/shop_categories.php', 'Проверьте корректность полей.', 'danger');
}

require_once __DIR__ . '/layout_header.php';
$cats = $pdo->query("
    SELECT sc.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = sc.id) AS products_count
    FROM shop_categories sc
    ORDER BY sc.id DESC
")->fetchAll();
?>

<h2 class="street-font mb-4">Категории Магазина</h2>

<div class="row">
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-header bg-success">Добавить</div>
            <div class="p-3">
                <form method="POST" data-validate-form data-prevent-double-submit="1" novalidate>
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label for="newShopCatTitle" class="fw-bold small">Название</label>
                        <input id="newShopCatTitle" type="text" name="title" class="form-control" required maxlength="120" data-rule="required-text" data-min="2" data-max="120" placeholder="Например: Баллоны">
                        <div class="auth-field-error"></div>
                    </div>
                    <button type="submit" name="add_cat" value="1" class="btn btn-admin btn-admin-dark w-100">Добавить</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="admin-card">
            <div class="admin-header">Список</div>
            <div class="table-responsive">
                <table class="table table-admin m-0">
                    <thead><tr><th>ID</th><th>Название</th><th>Slug (URL)</th><th>Товаров</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php foreach ($cats as $c):
                            $fid = 'shopCatForm' . (int)$c['id'];
                        ?>
                        <tr>
                            <td>#<?= (int)$c['id'] ?></td>
                            <td>
                                <input form="<?= $fid ?>" type="text" name="title" class="form-control form-control-sm" value="<?= e($c['title']) ?>" required maxlength="120" data-rule="required-text" data-min="2" data-max="120" aria-label="Название категории">
                                <div class="auth-field-error"></div>
                            </td>
                            <td class="small text-muted"><?= e($c['slug']) ?></td>
                            <td><span class="badge bg-dark rounded-0"><?= (int)$c['products_count'] ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button form="<?= $fid ?>" type="submit" name="edit_cat" value="1" class="btn btn-sm btn-admin btn-admin-dark" title="Сохранить" aria-label="Сохранить"><i class="bi bi-save" aria-hidden="true"></i></button>
                                    <button form="<?= $fid ?>" type="submit" name="delete_cat" value="1" data-skip-validate="1" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить категорию?')" title="Удалить" aria-label="Удалить"><i class="bi bi-trash" aria-hidden="true"></i></button>
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

<?php foreach ($cats as $c): ?>
    <form method="POST" id="shopCatForm<?= (int)$c['id'] ?>" class="d-none" data-validate-form novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
