<?php
// pages/create_topic.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/pages/login.php';</script>";
    exit;
}

// 1. Получаем категории, СГРУППИРОВАННЫЕ по Разделам (для "Обычной темы")
// Используем LEFT JOIN с таблицей sections, чтобы получить название раздела
$sqlCats = "
    SELECT c.id, c.title, c.description, s.title as section_title
    FROM categories c
    LEFT JOIN sections s ON c.section_id = s.id
    WHERE c.is_approved = 1
    ORDER BY s.sort_order ASC, c.title ASC
";
$rawCats = $pdo->query($sqlCats)->fetchAll();

// Группируем массив для удобного вывода в модалке/селекте
$groupedCats = [];
foreach ($rawCats as $cat) {
    $secTitle = $cat['section_title'] ?? 'Без раздела';
    $groupedCats[$secTitle][] = $cat;
}

// 2. Ищем ID категории "Общее" (или "Флудилка") для второй вкладки
$genCatStmt = $pdo->prepare("SELECT id, title FROM categories WHERE (title LIKE ? OR title LIKE ? OR title LIKE ?) AND is_approved = 1 LIMIT 1");
$genCatStmt->execute(['%Общее%', '%Флудилка%', '%General%']);
$generalCat = $genCatStmt->fetch(PDO::FETCH_ASSOC);

$generalCatId = $generalCat ? $generalCat['id'] : null;
?>

<style>
    /* === ОБЩИЙ СТИЛЬ (STREET) === */
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
        background-image: 
            linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px),
            linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px);
        background-size: 80px 80px;
        perspective: 500px;
        transform-style: preserve-3d;
        animation: grid-move 6s linear infinite;
        box-shadow: inset 0 0 150px rgba(0,0,0,0.9); 
        z-index: -1;
    }
    @keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }
    .bg-main-anim::after {
        content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    /* === КАРТОЧКА ФОРМЫ === */
    .create-card {
        background: #fff;
        border: 3px solid #000;
        box-shadow: 10px 10px 0 #000;
        color: #000;
        transition: box-shadow 0.3s ease;
    }
    .create-card:hover {
        box-shadow: 12px 12px 0 #bc13fe;
    }

    /* ТАБЫ (КНОПКИ ПЕРЕКЛЮЧЕНИЯ) */
    .nav-pills .nav-link { 
        background: rgba(255,255,255,0.9); 
        color: #000; 
        border: 3px solid #000;
        margin-right: 10px;
        margin-bottom: 10px;
        transition: all 0.2s; 
        font-family: 'Arial Black', sans-serif;
        text-transform: uppercase;
    }
    .nav-pills .nav-link:hover { 
        transform: translateY(-3px);
        box-shadow: 5px 5px 0 #bc13fe;
        z-index: 2;
    }
    .nav-pills .nav-link.active { 
        background: #000; 
        color: #FCE300; 
        border-color: #000; 
        box-shadow: 6px 6px 0 #FCE300;
        transform: scale(1.02);
        z-index: 3; 
    }

    /* ИНПУТЫ */
    .form-control {
        border: 3px solid #000;
        border-radius: 0;
        font-weight: bold;
        padding: 15px;
        background: #fff;
        color: #000;
    }
    .form-control:focus {
        box-shadow: 5px 5px 0 #bc13fe;
        border-color: #000;
        outline: none;
    }

    /* КНОПКИ */
    .btn-black { 
        background: #000; 
        color: #FCE300; 
        border: 3px solid #000;
        transition: all 0.2s;
        font-family: 'Arial Black', sans-serif;
    }
    .btn-black:hover { 
        background: #FCE300; 
        color: #000; 
        border-color: #000;
        transform: translate(-3px, -3px);
        box-shadow: 8px 8px 0 #fff;
    }
    
    /* Кнопка выбора категории */
    .btn-select-cat {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        transition: all 0.2s;
        text-align: left;
    }
    .btn-select-cat:hover, .btn-select-cat.selected {
        background: #f0f0f0;
        box-shadow: 5px 5px 0 #bc13fe;
    }

    /* МОДАЛКА */
    .modal-content {
        border: 4px solid #000;
        box-shadow: 0 0 50px rgba(0,0,0,0.8);
    }
    .cat-item-btn {
        border: none;
        border-bottom: 2px solid #eee;
        transition: all 0.1s;
    }
    .cat-item-btn:hover { 
        background-color: #FCE300 !important; 
        border-left: 10px solid #000; 
        padding-left: 2rem !important;
        color: #000;
    }
    
    .section-header {
        background: #000;
        color: #fff;
        padding: 10px 15px;
        font-family: 'Arial Black', sans-serif;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 1px;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- ЗАГОЛОВОК -->
            <div class="d-flex align-items-center mb-5 border-bottom border-4 border-light pb-3">
                <a href="/pages/topics_list.php" class="btn btn-outline-light rounded-0 border-3 me-3 fw-bold"><i class="bi bi-arrow-left"></i></a>
                <h1 class="display-4 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
                    Создать <span class="text-street-yellow">Тему</span>
                </h1>
            </div>

            <!-- ТАБЫ -->
            <ul class="nav nav-pills nav-fill mb-5" id="topicTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link w-100 py-3 active" id="standard-tab" data-bs-toggle="tab" data-bs-target="#standard-content" type="button">
                        <i class="bi bi-folder-fill me-2"></i> Обычная тема
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link w-100 py-3 text-danger" id="new-cat-tab" data-bs-toggle="tab" data-bs-target="#new-cat-content" type="button">
                        <i class="bi bi-plus-square-fill me-2"></i> Заявка на раздел
                    </button>
                </li>
            </ul>

            <!-- КОНТЕНТ ВКАДОК -->
            <div class="tab-content">
                
                <!-- 1. ОБЫЧНАЯ ТЕМА (Выбор категории) -->
                <div class="tab-pane fade show active" id="standard-content">
                    <form action="/actions/create_topic.php" method="POST" id="standardForm">
                        
                        <div class="card rounded-0 create-card">
                            <div class="card-header bg-black text-white py-3 border-bottom border-3 border-dark">
                                <span class="street-font fs-5"><i class="bi bi-pencil-square text-warning me-2"></i> Тематическое обсуждение</span>
                            </div>
                            <div class="card-body p-4 p-md-5 bg-white">
                                
                                <!-- Выбор категории -->
                                <div class="mb-4">
                                    <label class="street-font small text-muted mb-2">Раздел форума</label>
                                    <input type="hidden" name="category_id" id="selectedCategoryId">
                                    
                                    <button type="button" class="btn btn-select-cat w-100 rounded-0 py-3 px-3 d-flex justify-content-between align-items-center fw-bold fs-5"
                                            data-bs-toggle="modal" data-bs-target="#categoryModal" id="categorySelectBtn">
                                        <span class="text-muted"><i class="bi bi-grid me-2"></i> Выберите раздел...</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>

                                <div class="mb-4">
                                    <label class="street-font small text-muted mb-2">Заголовок</label>
                                    <input type="text" name="title" class="form-control form-control-lg rounded-0" placeholder="О чем речь?" required>
                                </div>

                                <div class="mb-5">
                                    <label class="street-font small text-muted mb-2">Суть вопроса</label>
                                    <textarea name="content" class="form-control rounded-0" rows="6" placeholder="Распиши подробно..." required></textarea>
                                </div>

                                <button type="submit" class="btn btn-black w-100 rounded-0 text-uppercase py-3 fs-4 shadow-sm">
                                    ОПУБЛИКОВАТЬ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- 3. ЗАЯВКА НА РАЗДЕЛ -->
                <div class="tab-pane fade" id="new-cat-content">
                    <form action="/actions/create_category.php" method="POST" id="newCatForm">
                        <div class="card rounded-0 create-card">
                            <div class="card-header bg-danger text-white border-bottom border-3 border-dark py-3">
                                <span class="street-font fs-5"><i class="bi bi-plus-circle-fill me-2 text-warning"></i> Новый раздел</span>
                            </div>
                            <div class="card-body p-4 p-md-5 bg-white">
                                <div class="alert alert-warning border-3 border-dark rounded-0 fw-bold mb-4 text-dark shadow-sm">
                                    <i class="bi bi-shield-lock-fill me-2"></i> Раздел появится после одобрения администратором.
                                </div>
                                <div class="mb-4">
                                    <label class="street-font small text-muted mb-2">Название раздела</label>
                                    <input type="text" name="title" class="form-control form-control-lg rounded-0" required>
                                </div>
                                <div class="mb-5">
                                    <label class="street-font small text-muted mb-2">Описание</label>
                                    <textarea name="description" class="form-control rounded-0" rows="4" placeholder="Для чего этот раздел?" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-black w-100 rounded-0 text-uppercase py-3 fs-4 shadow-sm text-warning">
                                    ОТПРАВИТЬ ЗАЯВКУ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- МОДАЛКА ВЫБОРА КАТЕГОРИИ -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-black text-white rounded-0 py-3 border-bottom border-3 border-dark">
                <h5 class="modal-title street-font">Выберите раздел</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-header bg-light border-bottom border-3 border-dark p-3">
                <input type="text" id="catSearchInput" class="form-control rounded-0 border-2 border-dark fw-bold" placeholder="🔍 Поиск...">
            </div>
            <div class="modal-body p-0 bg-white">
                <div class="list-group list-group-flush" id="categoriesList" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($groupedCats)): ?>
                        <div class="p-4 text-center text-muted fw-bold">Нет доступных категорий</div>
                    <?php else: ?>
                        <?php foreach($groupedCats as $sectionName => $cats): ?>
                            <!-- ЗАГОЛОВОК РАЗДЕЛА (ГРУППИРОВКА) -->
                            <div class="section-header">
                                <?= htmlspecialchars($sectionName) ?>
                            </div>
                            
                            <?php foreach($cats as $c): ?>
                                <button type="button" class="list-group-item list-group-item-action p-4 border-bottom cat-item-btn" 
                                        onclick="selectCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['title']) ?>')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="street-font mb-1 text-dark"><?= htmlspecialchars($c['title']) ?></h5>
                                            <small class="text-muted fw-bold"><?= htmlspecialchars($c['description']) ?></small>
                                        </div>
                                        <i class="bi bi-chevron-right fs-4 opacity-50"></i>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Выбор категории
function selectCategory(id, title) {
    document.getElementById('selectedCategoryId').value = id;
    const btn = document.getElementById('categorySelectBtn');
    
    // Меняем стиль кнопки на "Выбрано"
    btn.innerHTML = `<span class="text-dark"><i class="bi bi-check-circle-fill me-2 text-success"></i> ${title}</span> <span class="badge bg-black text-warning rounded-0 border border-dark">Изменить</span>`;
    btn.classList.add('selected');
    
    // Закрываем модалку
    const modalEl = document.getElementById('categoryModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
}

// Поиск по категориям
document.getElementById('catSearchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    // Ищем только элементы с классом cat-item-btn
    document.querySelectorAll('.cat-item-btn').forEach(item => { 
        item.classList.toggle('d-none', !item.innerText.toLowerCase().includes(filter)); 
    });
    // Можно добавить логику скрытия пустых section-header, но пока оставим как есть для простоты
});

// Валидация перед отправкой формы "Обычная тема"
const standardForm = document.getElementById('standardForm');
if (standardForm) {
    standardForm.addEventListener('submit', function(e) {
        const catId = document.getElementById('selectedCategoryId').value;
        if (!catId) {
            e.preventDefault(); 
            alert('Пожалуйста, выберите раздел для темы!');
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
