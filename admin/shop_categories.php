<?php require_once 'layout_header.php'; ?>

<?php
// Функция генерации SLUG (транслитерация)
function createSlug($str) {
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
    // Убираем двойные дефисы
    $str = preg_replace('/-+/', '-', $str);
    // Убираем дефисы по краям
    return trim($str, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ДОБАВЛЕНИЕ
    if (isset($_POST['add_cat'])) {
        $title = trim($_POST['title']);
        if (!empty($title)) {
            $slug = createSlug($title);
            // Если slug пустой (например, только спецсимволы), генерируем рандом
            if (empty($slug)) $slug = 'cat-' . time();

            // Проверяем уникальность slug (опционально, но желательно)
            // Для простоты, если дубль - добавим время
            $check = $pdo->prepare("SELECT id FROM shop_categories WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                $slug .= '-' . time();
            }

            // Запрос с полем SLUG
            $stmt = $pdo->prepare("INSERT INTO shop_categories (title, slug) VALUES (?, ?)");
            try {
                $stmt->execute([$title, $slug]);
            } catch (PDOException $e) {
                echo "<script>alert('Ошибка БД: " . $e->getMessage() . "');</script>";
            }
        }
    }

    // УДАЛЕНИЕ
    if (isset($_POST['delete_cat'])) {
        $pdo->prepare("DELETE FROM shop_categories WHERE id = ?")->execute([(int)$_POST['id']]);
    }

    // РЕДАКТИРОВАНИЕ
    if (isset($_POST['edit_cat'])) {
        $title = trim($_POST['title']);
        $id = (int)$_POST['id'];
        if (!empty($title)) {
            $slug = createSlug($title); // Обновляем и слаг тоже
             // Проверка на дубликат слага исключая текущую категорию
            $check = $pdo->prepare("SELECT id FROM shop_categories WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                $slug .= '-' . time();
            }

            $stmt = $pdo->prepare("UPDATE shop_categories SET title = ?, slug = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $id]);
        }
    }
}

$cats = $pdo->query("SELECT * FROM shop_categories ORDER BY id DESC")->fetchAll();
?>

<h2 class="street-font mb-4">Категории Магазина</h2>

<div class="row">
    <!-- ФОРМА ДОБАВЛЕНИЯ -->
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-header bg-success">Добавить</div>
            <div class="p-3">
                <form method="POST">
                    <div class="mb-3">
                        <label class="fw-bold small">Название</label>
                        <input type="text" name="title" class="form-control" required placeholder="Например: Футболки">
                    </div>
                    <button type="submit" name="add_cat" class="btn btn-admin btn-admin-dark w-100">Добавить</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- СПИСОК -->
    <div class="col-md-8">
        <div class="admin-card">
            <div class="admin-header">Список</div>
            <div class="table-responsive">
                <table class="table table-admin m-0">
                    <thead><tr><th>ID</th><th>Название</th><th>Slug (URL)</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php foreach($cats as $c): ?>
                        <tr>
                            <form method="POST">
                                <td>#<?= $c['id'] ?><input type="hidden" name="id" value="<?= $c['id'] ?>"></td>
                                <td><input type="text" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($c['title']) ?>"></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['slug']) ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="submit" name="edit_cat" class="btn btn-sm btn-admin btn-admin-dark"><i class="bi bi-save"></i></button>
                                        <button type="submit" name="delete_cat" class="btn btn-sm btn-admin btn-admin-danger" onclick="return confirm('Удалить?')"><i class="bi bi-trash"></i></button>
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
