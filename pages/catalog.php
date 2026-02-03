<?php
// pages/catalog.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// --- 1. НАСТРОЙКИ ---
$limit = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Фильтры
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$isSale = isset($_GET['sale']) ? 1 : 0;

// --- 2. КАТЕГОРИИ ---
$cats = $pdo->query("SELECT sc.*, COUNT(p.id) as count FROM shop_categories sc LEFT JOIN products p ON sc.id = p.category_id AND p.is_active = 1 GROUP BY sc.id")->fetchAll();

// --- 3. SQL ЗАПРОС ---
$whereConditions = ["is_active = 1"];
$params = [];

if ($catId) { $whereConditions[] = "category_id = ?"; $params[] = $catId; }
if ($search) { $whereConditions[] = "(title LIKE ? OR description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($isSale) { $whereConditions[] = "is_sale = 1"; }

$sqlWhere = "WHERE " . implode(' AND ', $whereConditions);

switch ($sort) {
    case 'price_asc':  $orderBy = "price ASC"; break;
    case 'price_desc': $orderBy = "price DESC"; break;
    default:           $orderBy = "created_at DESC"; break; 
}

// --- 4. ВЫПОЛНЕНИЕ ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $sqlWhere");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

$sql = "SELECT * FROM products $sqlWhere ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

function buildUrl($newPage, $currentParams) {
    $currentParams['page'] = $newPage;
    return 'catalog.php?' . http_build_query($currentParams);
}
?>

<style>
    /* === ШРИФТЫ И ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }
    
    .street-font {
        font-family: 'Arial Black', 'Impact', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

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

    /* === САЙДБАР (ФИЛЬТРЫ) === */
    .catalog-sidebar {
        background: rgba(255, 255, 255, 0.95);
        border: 4px solid #000;
        box-shadow: 8px 8px 0 #bc13fe;
        padding: 20px;
        color: #000;
        transition: box-shadow 0.3s;
    }
    .catalog-sidebar:hover { box-shadow: 10px 10px 0 #FCE300; }

    /* Кнопка поиска */
    .btn-black { background: #000; color: #FCE300; transition: all 0.2s; }
    .btn-black:hover { background: #FCE300; color: #000; }

    /* === КАРТОЧКА ТОВАРА === */
    .product-card { 
        transition: transform 0.2s, box-shadow 0.2s; 
        color: #000; 
        border: 3px solid #000;
        box-shadow: 6px 6px 0 #000 !important;
    }
    .product-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 10px 10px 0 #FCE300 !important; 
        border-color: #000;
    }

    /* Анимация картинки */
    .image-hover { transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
    .product-card:hover .image-hover { transform: scale(1.1) rotate(1deg); }

    /* Оверлей "В корзину" */
    .add-cart-overlay { transform: translateY(100%); transition: transform 0.3s ease; }
    .product-card:hover .add-cart-overlay { transform: translateY(0); }
    
    /* Ссылки категорий */
    .list-group-item { background: transparent; color: #000; font-weight: 700; border-color: #000; }
    .list-group-item:hover { background: #f0f0f0; padding-left: 20px; transition: padding 0.2s; }
    .list-group-item.bg-warning { background: #FCE300 !important; color: #000 !important; font-weight: 900; border-color: #000; }
    
    /* Пагинация */
    .page-link { background: #000; color: #fff; border: 2px solid #fff; margin: 0 5px; font-weight: bold; }
    .page-link:hover { background: #FCE300; color: #000; border-color: #000; transform: translateY(-2px); box-shadow: 4px 4px 0 #000; }
    .page-item.active .page-link { background: #FCE300; border-color: #000; color: #000; box-shadow: 4px 4px 0 #fff; }
    .page-item.disabled .page-link { opacity: 0.5; background: #333; }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container-fluid p-0 mb-5 position-relative" style="z-index: 2;">
    <!-- БАННЕР -->
    <div class="bg-black text-white py-5 text-center position-relative overflow-hidden border-bottom border-4 border-warning">
        <div class="position-absolute top-0 start-0 w-100 h-100" 
             style="background: repeating-linear-gradient(45deg, #111, #111 10px, #000 10px, #000 20px); opacity: 0.5; z-index: 0;"></div>
        
        <div class="position-relative" style="z-index: 2;">
            <h1 class="display-3 street-font fst-italic mb-0" style="text-shadow: 4px 4px 0 #bc13fe;">
                <?= $isSale ? 'TOTAL SALE %' : 'STREET WEAR' ?>
            </h1>
            <p class="lead fw-bold text-uppercase text-warning mb-0" style="letter-spacing: 2px;">
                <?= $isSale ? 'СКИДКИ ДО 50% НА ВСЁ' : 'НОВАЯ КОЛЛЕКЦИЯ 2024/2025' ?>
            </p>
        </div>
    </div>
</div>

<div class="container pb-5 position-relative" style="z-index: 2;">
    <div class="row g-5">

        <!-- === САЙДБАР === -->
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 100px;">
                
                <button class="btn btn-warning w-100 d-lg-none mb-4 rounded-0 fw-bold border-3 border-dark text-dark street-font shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="bi bi-funnel-fill"></i> ФИЛЬТРЫ
                </button>

                <div class="collapse d-lg-block catalog-sidebar" id="filterCollapse">
                    
                    <?php if($isSale): ?>
                        <div class="mb-4">
                            <a href="catalog.php" class="btn btn-danger w-100 rounded-0 fw-black border-2 border-dark shadow-sm street-font">
                                <i class="bi bi-x-lg"></i> ВЕСЬ КАТАЛОГ
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Поиск -->
                    <div class="mb-4">
                        <h5 class="street-font border-bottom border-4 border-dark pb-2 mb-3">Поиск</h5>
                        <form action="" method="GET" class="d-flex">
                            <?php if($isSale): ?><input type="hidden" name="sale" value="1"><?php endif; ?>
                            <?php if($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
                            
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control rounded-0 border-2 border-dark fw-bold" placeholder="Найти шмот...">
                            <button class="btn btn-black rounded-0 border-2 border-dark border-start-0"><i class="bi bi-search"></i></button>
                        </form>
                    </div>

                    <!-- Категории -->
                    <div class="mb-5">
                        <h5 class="street-font border-bottom border-4 border-dark pb-2 mb-3">Категории</h5>
                        <div class="list-group list-group-flush border-top border-dark">
                            <a href="catalog.php<?= $isSale ? '?sale=1' : '' ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-bottom border-dark <?= !$catId ? 'bg-warning text-dark' : 'bg-transparent' ?>">
                                ВСЕ ТОВАРЫ <i class="bi bi-arrow-right"></i>
                            </a>
                            
                            <?php foreach($cats as $c): ?>
                                <?php 
                                    $link = "catalog.php?cat=" . $c['id'];
                                    if ($isSale) $link .= "&sale=1";
                                    if ($search) $link .= "&q=" . urlencode($search);
                                    $isActive = ($catId == $c['id']); 
                                ?>
                                <a href="<?= $link ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-bottom border-dark <?= $isActive ? 'bg-warning text-dark' : 'bg-transparent' ?>">
                                    <?= htmlspecialchars($c['title']) ?>
                                    <span class="badge bg-black rounded-0 text-white border border-dark"><?= $c['count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Сортировка -->
                    <div class="mb-4">
                        <h5 class="street-font border-bottom border-4 border-dark pb-2 mb-3">Сортировка</h5>
                        <form action="" method="GET">
                            <?php if($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
                            <?php if($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                            <?php if($isSale): ?><input type="hidden" name="sale" value="1"><?php endif; ?>

                            <select name="sort" class="form-select rounded-0 border-2 border-dark fw-bold cursor-pointer" onchange="this.form.submit()">
                                <option value="new" <?= $sort == 'new' ? 'selected' : '' ?>>Сначала новые</option>
                                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Сначала дешевые</option>
                                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Сначала дорогие</option>
                            </select>
                        </form>
                    </div>

                    <?php if(!$isSale): ?>
                    <div class="card bg-black text-white rounded-0 border-0 p-4 text-center mt-5 shadow-lg d-none d-lg-block border border-warning">
                        <h3 class="street-font text-warning">SALE -50%</h3>
                        <p class="small fw-bold mb-3">УСПЕЙ ЗАБРАТЬ СВОЁ</p>
                        <a href="catalog.php?sale=1" class="btn btn-outline-light rounded-0 fw-black border-2 w-100 street-font">СМОТРЕТЬ</a>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- === ТОВАРЫ === -->
        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-2 border-white">
                <div class="fw-bold text-white text-uppercase small">Найдено: <span class="text-warning"><?= $totalItems ?></span> шт.</div>
                <div class="fw-bold text-white text-uppercase small">Стр. <?= $page ?> из <?= $totalPages ?></div>
            </div>

            <div class="row g-4">
                <?php if(empty($products)): ?>
                    <div class="col-12 text-center py-5 border-4 border-dark bg-white rounded-0 shadow-lg">
                        <i class="bi bi-emoji-dizzy display-1 text-muted mb-3 d-block"></i>
                        <h2 class="street-font text-dark">ПУСТО</h2>
                        <p class="fw-bold text-muted mb-4">По заданным критериям ничего не найдено.</p>
                        <a href="catalog.php" class="btn btn-black rounded-0 fw-black px-4 py-3 text-uppercase street-font border-2 border-dark">Сбросить фильтры</a>
                    </div>
                <?php else: ?>
                    <?php foreach($products as $p): ?>
                        <div class="col-md-6 col-lg-4">
                            <!-- КАРТОЧКА -->
                            <div class="card h-100 rounded-0 product-card bg-white position-relative">
                                
                                <!-- Бейджи -->
                                <div class="position-absolute top-0 start-0 p-2" style="z-index: 10;">
                                    <?php if($p['is_new']): ?>
                                        <span class="badge bg-danger rounded-0 border border-dark text-uppercase mb-1 d-table fw-bold px-2 py-1">NEW</span>
                                    <?php endif; ?>
                                    <?php if($p['is_sale']): ?>
                                        <span class="badge bg-warning text-dark rounded-0 border border-dark text-uppercase d-table fw-bold px-2 py-1">SALE</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Картинка -->
                                <div class="overflow-hidden border-bottom border-3 border-dark position-relative group">
                                    <a href="/pages/product.php?id=<?= $p['id'] ?>">
                                        <img src="<?= $p['image'] ?>" class="card-img-top rounded-0 image-hover" alt="<?= htmlspecialchars($p['title']) ?>" style="height: 320px; object-fit: cover;">
                                    </a>
                                    
                                    <div class="position-absolute bottom-0 start-0 w-100 p-2 add-cart-overlay d-none d-md-block">
                                        <form action="/actions/add_to_cart.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-black w-100 rounded-0 fw-black border-2 border-dark text-warning text-uppercase street-font">
                                                В КОРЗИНУ
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Контент -->
                                <div class="card-body d-flex flex-column p-3">
                                    <h5 class="street-font lh-sm mb-2" style="font-size: 1.1rem;">
                                        <a href="/pages/product.php?id=<?= $p['id'] ?>" class="text-dark text-decoration-none stretched-link-mobile">
                                            <?= htmlspecialchars($p['title']) ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="mt-auto pt-3 border-top border-2 border-light d-flex justify-content-between align-items-end">
                                        <div>
                                            <?php if($p['old_price'] > 0): ?>
                                                <div class="text-decoration-line-through text-muted fw-bold small"><?= number_format($p['old_price'], 0, '', ' ') ?> ₽</div>
                                                <div class="street-font fs-4 text-danger"><?= number_format($p['price'], 0, '', ' ') ?> ₽</div>
                                            <?php else: ?>
                                                <div class="street-font fs-4"><?= number_format($p['price'], 0, '', ' ') ?> ₽</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-md-none z-index-10">
                                            <form action="/actions/add_to_cart.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-warning rounded-0 border-2 border-dark px-3 shadow-sm">
                                                    <i class="bi bi-cart-plus-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ПАГИНАЦИЯ -->
            <?php if($totalPages > 1): ?>
            <div class="mt-5 border-top border-4 border-white pt-4 d-flex justify-content-center">
                <nav>
                    <ul class="pagination flex-wrap justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-0" href="<?= buildUrl($page - 1, $_GET) ?>">←</a>
                        </li>

                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link rounded-0" href="<?= buildUrl($i, $_GET) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-0" href="<?= buildUrl($page + 1, $_GET) ?>">→</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
