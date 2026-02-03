<?php
// pages/topics_list.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// --- 1. ПАРАМЕТРЫ ФИЛЬТРАЦИИ ДЛЯ ПЕРВОЙ ЗАГРУЗКИ ---
$whereClause = "WHERE t.is_approved = 1";
$params = [];

// Фильтр по категории (ID)
$catFilterId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$currentCatTitle = '';

if ($catFilterId > 0) {
    $whereClause .= " AND t.category_id = ?";
    $params[] = $catFilterId;
    
    // Получаем название выбранной категории для заголовка
    $stmtCat = $pdo->prepare("SELECT title FROM categories WHERE id = ?");
    $stmtCat->execute([$catFilterId]);
    $currentCatTitle = $stmtCat->fetchColumn();
}

// Поиск (Search) - обрабатывается и при GET-запросе для сохранения состояния
$search = trim($_GET['q'] ?? '');
if (!empty($search)) {
    $whereClause .= " AND t.title LIKE ?";
    $params[] = "%$search%";
}

// --- 2. ПОЛУЧЕНИЕ ТЕМ ---
$sqlAll = "
    SELECT t.*, u.username, u.avatar, c.title as cat_title,
    (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) as replies_count
    FROM topics t
    JOIN users u ON t.user_id = u.id
    JOIN categories c ON t.category_id = c.id
    $whereClause
    ORDER BY t.created_at DESC
    LIMIT 60
";
$stmt = $pdo->prepare($sqlAll);
$stmt->execute($params);
$allTopics = $stmt->fetchAll();

// --- 3. ПОЛУЧЕНИЕ ВСЕХ КАТЕГОРИЙ (ДЛЯ МОДАЛКИ) ---
$allCats = $pdo->query("SELECT * FROM categories WHERE is_approved = 1 ORDER BY title ASC")->fetchAll();

// --- 4. ПОСЛЕДНИЕ ТЕМЫ (Сайдбар) ---
$sqlRecent = "
    SELECT t.id, t.title, t.created_at, u.username 
    FROM topics t
    JOIN users u ON t.user_id = u.id
    WHERE t.is_approved = 1
    ORDER BY t.created_at DESC
    LIMIT 7
";
$recentTopics = $pdo->query($sqlRecent)->fetchAll();

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
    if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
    return date('d.m.Y', strtotime($datetime));
}
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

    /* Фон */
    .bg-main-anim {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px);
        background-size: 80px 80px; perspective: 500px; transform-style: preserve-3d; animation: grid-move 6s linear infinite; box-shadow: inset 0 0 150px rgba(0,0,0,0.9); z-index: -1;
    }
    @keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }
    .bg-main-anim::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 40px 40px; }

    /* Анимация появления карточек */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .topic-item-anim { animation: fadeInUp 0.3s ease-out forwards; }

    /* Карточки */
    .topic-card {
        background: #fff; color: #000;
        border: 3px solid #000;
        box-shadow: 6px 6px 0 #000;
        transition: all 0.2s;
        height: 100%;
    }
    .topic-card:hover {
        transform: translateY(-5px);
        box-shadow: 10px 10px 0 #bc13fe;
        border-color: #000;
    }
    .topic-card .stretched-link::after { position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 1; content: ""; }

    /* Сайдбар */
    .sidebar-card {
        background: #fff; color: #000;
        border: 3px solid #000;
        box-shadow: 10px 10px 0 #FCE300;
    }

    /* Элементы */
    .btn-search { background: #FCE300; border: 3px solid #000; color: #000; }
    .btn-search:hover { background: #ffe600; }
    
    .btn-filter { background: #000; border: 3px solid #000; color: #FCE300; font-family: 'Arial Black', sans-serif; text-transform: uppercase; }
    .btn-filter:hover { background: #333; color: #fff; border-color: #333; }

    .btn-sidebar-create { background: #FCE300; color: #000; border: 2px solid #000; font-family: 'Arial Black', sans-serif; }
    .btn-sidebar-create:hover { background: #ffe600; transform: translateY(-2px); box-shadow: 3px 3px 0 #000; }

    .btn-sidebar-profile { background: #000; color: #fff; border: 2px solid #000; font-family: 'Arial Black', sans-serif; }
    .btn-sidebar-profile:hover { background: #333; color: #FCE300; transform: translateY(-2px); box-shadow: 3px 3px 0 #fff; }

    /* Модалка категорий */
    .modal-content { border: 4px solid #000; box-shadow: 0 0 50px rgba(0,0,0,0.8); }
    .cat-modal-item { 
        border: none; border-bottom: 1px solid #eee; transition: all 0.1s; 
        font-weight: bold; text-transform: uppercase; color: #000;
    }
    .cat-modal-item:hover { 
        background-color: #FCE300 !important; 
        border-left: 10px solid #000; 
        padding-left: 2rem !important; 
        color: #000;
    }
</style>

<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <!-- ЗАГОЛОВОК СТРАНИЦЫ -->
    <div class="mb-5 border-bottom border-white border-2 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end">
        <div>
            <a href="/index.php" class="text-decoration-none fw-bold text-light mb-2 d-inline-block small text-uppercase street-font">
                <i class="bi bi-arrow-left"></i> На главную
            </a>
            <h1 class="display-3 street-font m-0 text-white" style="letter-spacing: 2px;">
                <?php if($currentCatTitle): ?>
                    РАЗДЕЛ: <span class="text-street-yellow"><?= htmlspecialchars($currentCatTitle) ?></span>
                <?php else: ?>
                    АРХИВ <span class="text-street-yellow">ТЕМ</span>
                <?php endif; ?>
            </h1>
        </div>
        
        <?php if($catFilterId > 0): ?>
            <a href="topics_list.php" class="btn btn-outline-light rounded-0 border-2 street-font mt-3 mt-md-0">
                <i class="bi bi-x-lg"></i> Сбросить фильтр
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-5">
        
        <!-- ЛЕВАЯ КОЛОНКА (2/3) -->
        <div class="col-lg-8">
            
            <!-- Панель поиска и фильтра -->
            <div class="mb-5 sticky-top pt-2" style="top: 0; z-index: 10;">
                <div class="d-flex shadow-lg">
                    <!-- Кнопка фильтра (Модалка) -->
                    <button type="button" class="btn btn-filter rounded-0 px-4 py-3 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#categoriesModal">
                        <i class="bi bi-funnel-fill fs-5 me-2"></i> 
                        <span class="d-none d-sm-inline">Фильтр</span>
                    </button>

                    <!-- ИНПУТ ПОИСКА -->
                    <div class="flex-grow-1 position-relative">
                        <input type="text" id="searchInput" 
                               class="form-control form-control-lg rounded-0 border-3 border-dark fw-bold px-4 street-font h-100" 
                               placeholder="Найти тему..." 
                               value="<?= htmlspecialchars($search) ?>"
                               autocomplete="off"
                               style="border-right: 0; border-left: 0;">
                        
                        <!-- Индикатор загрузки -->
                        <div id="searchSpinner" class="spinner-border text-dark position-absolute top-50 end-0 translate-middle-y me-3" style="display:none; width: 1.5rem; height: 1.5rem;" role="status"></div>
                    </div>
                        
                    <button type="button" class="btn btn-search rounded-0 fw-black px-4 border-start-0">
                        <i class="bi bi-search fs-4"></i>
                    </button>
                </div>
            </div>

            <!-- КОНТЕЙНЕР ТЕМ (Обновляется через AJAX) -->
            <div id="topicsContainer" class="row g-4">
                <?php if(empty($allTopics)): ?>
                    <div class="alert alert-light border-3 border-dark rounded-0 text-center py-5 shadow-sm col-12">
                        <i class="bi bi-search fs-1 mb-3 d-block"></i>
                        <h4 class="street-font">Темы не найдены</h4>
                        <p class="text-muted fw-bold">Попробуйте изменить параметры фильтра.</p>
                        <a href="topics_list.php" class="btn btn-black rounded-0 text-uppercase fw-bold mt-2">Смотреть всё</a>
                    </div>
                <?php else: ?>
                    <?php foreach($allTopics as $t): ?>
                        <div class="col-md-6 col-xl-4 d-flex align-items-stretch topic-item-anim">
                            <div class="card w-100 rounded-0 topic-card position-relative">
                                <div class="card-body d-flex flex-column p-4">
                                    
                                    <!-- Верх: Категория и дата -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge bg-black text-white rounded-0 border border-dark text-uppercase street-font" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($t['cat_title']) ?>
                                        </span>
                                        <small class="text-muted fw-bold font-monospace" style="font-size: 0.7rem;">
                                            <?= date('d.m', strtotime($t['created_at'])) ?>
                                        </small>
                                    </div>

                                    <!-- Заголовок -->
                                    <h6 class="street-font lh-sm mb-3">
                                        <a href="/pages/topic.php?id=<?= $t['id'] ?>" class="text-dark text-decoration-none stretched-link">
                                            <?= mb_strimwidth(htmlspecialchars($t['title']), 0, 50, '...') ?>
                                        </a>
                                    </h6>

                                    <!-- Низ: Автор и ответы -->
                                    <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top border-2 border-light">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $t['avatar'] ? '/'.$t['avatar'] : 'https://via.placeholder.com/30' ?>" 
                                                 class="rounded-circle border border-dark me-2" width="24" height="24" style="object-fit:cover;">
                                            <span class="small fw-bold text-muted text-truncate street-font" style="max-width: 80px;">
                                                @<?= htmlspecialchars($t['username']) ?>
                                            </span>
                                        </div>
                                        <div class="fw-bold small text-dark">
                                            <i class="bi bi-chat-fill text-warning me-1" style="text-shadow: 1px 1px 0 #000;"></i> <?= $t['replies_count'] ?>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- ПРАВАЯ КОЛОНКА (Сайдбар) -->
        <div class="col-lg-4">
            <div class="card rounded-0 sidebar-card sticky-top" style="top: 100px;">
                <div class="card-header bg-black text-white rounded-0 py-3 border-bottom border-dark">
                    <h5 class="street-font m-0">
                        <i class="bi bi-lightning-charge-fill text-street-yellow"></i> Свежак
                    </h5>
                </div>
                <div class="list-group list-group-flush rounded-0">
                    <?php foreach($recentTopics as $rt): ?>
                        <a href="/pages/topic.php?id=<?= $rt['id'] ?>" class="list-group-item list-group-item-action p-3 border-bottom border-dark bg-transparent">
                            <div class="d-flex w-100 justify-content-between mb-1">
                                <small class="fw-bold text-muted street-font">@<?= htmlspecialchars($rt['username']) ?></small>
                                <small class="text-danger fw-bold" style="font-size: 0.7rem;">
                                    <?= time_ago($rt['created_at']) ?>
                                </small>
                            </div>
                            <div class="street-font text-dark lh-sm" style="font-size: 0.9rem;">
                                <?= mb_strimwidth(htmlspecialchars($rt['title']), 0, 45, '...') ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-light p-3 border-top-0 d-grid gap-2">
                    <a href="/pages/create_topic.php" class="btn btn-sidebar-create w-100 rounded-0 text-uppercase py-2">
                        + СОЗДАТЬ ТЕМУ
                    </a>
                    <a href="/pages/profile.php" class="btn btn-sidebar-profile w-100 rounded-0 text-uppercase py-2">
                        МОЙ ПРОФИЛЬ
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО ФИЛЬТРАЦИИ -->
<div class="modal fade" id="categoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-black text-white rounded-0 py-3 border-bottom border-3 border-dark">
                <h5 class="modal-title street-font">ВЫБЕРИ РАЗДЕЛ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-white">
                <div class="list-group list-group-flush">
                    <a href="topics_list.php" class="list-group-item list-group-item-action p-3 cat-modal-item bg-light text-center border-bottom-2 border-dark">
                        <i class="bi bi-grid-fill me-2"></i> ВСЕ ТЕМЫ
                    </a>
                    <?php foreach($allCats as $c): ?>
                        <a href="topics_list.php?cat=<?= $c['id'] ?>" class="list-group-item list-group-item-action p-3 cat-modal-item d-flex justify-content-between align-items-center">
                            <span><?= htmlspecialchars($c['title']) ?></span>
                            <i class="bi bi-chevron-right opacity-50"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT ДЛЯ LIVE SEARCH -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const topicsContainer = document.getElementById('topicsContainer');
    const spinner = document.getElementById('searchSpinner');
    const currentCatId = <?= $catFilterId ?>; // Передаем PHP переменную в JS
    
    let debounceTimer;

    // Функция поиска
    function performSearch(query) {
        spinner.style.display = 'block';
        
        // Формируем URL. Файл search_topics.php должен лежать в той же папке /pages/
        let url = `search_topics.php?q=${encodeURIComponent(query)}`;
        if (currentCatId > 0) {
            url += `&cat=${currentCatId}`;
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Сеть ответила с ошибкой: ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                topicsContainer.innerHTML = html;
                spinner.style.display = 'none';
            })
            .catch(err => {
                console.error('Ошибка поиска:', err);
                spinner.style.display = 'none';
            });
    }

    // Слушатель ввода с задержкой (Debounce)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            // Ждем 300мс после окончания ввода перед отправкой запроса
            debounceTimer = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
