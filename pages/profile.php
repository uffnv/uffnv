<?php
// pages/profile.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// --- 1. ПРОВЕРКА ДОСТУПА ---
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/pages/login.php';</script>";
    exit;
}

// --- 2. ПОЛУЧАЕМ ДАННЫЕ ЮЗЕРА ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    echo "<script>window.location.href='/';</script>";
    exit;
}

// --- 3. ЛОГИКА МОДЕРАТОРА ---
$pendingTopics = [];
$pendingCategories = [];
$modMessage = "";
$modError = "";
$isModerator = ($user['role'] === 'admin' || $user['role'] === 'super_admin');

if ($isModerator) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['approve_cat_id'])) {
            $pdo->prepare("UPDATE shop_categories SET is_approved = 1 WHERE id = ?")->execute([(int)$_POST['approve_cat_id']]); 
            $modMessage = "Категория одобрена!";
        }
        if (isset($_POST['delete_cat_id'])) {
            $pdo->prepare("DELETE FROM shop_categories WHERE id = ?")->execute([(int)$_POST['delete_cat_id']]);
            $modMessage = "Категория удалена.";
        }
        if (isset($_POST['approve_topic_id'])) {
            $pdo->prepare("UPDATE topics SET is_approved = 1 WHERE id = ?")->execute([(int)$_POST['approve_topic_id']]);
            $modMessage = "Тема опубликована!";
        }
        
        // Банхаммер
        if (isset($_POST['ban_user_input'])) {
            $input = trim($_POST['ban_user_input']);
            $reason = trim($_POST['ban_reason'] ?? 'Нарушение правил');
            $banAmount = (int)$_POST['ban_amount']; 
            $banUnit = $_POST['ban_unit']; 

            if (!empty($input)) {
                $findStmt = $pdo->prepare("SELECT id, role, username FROM users WHERE (username = ? OR id = ?) LIMIT 1");
                $findStmt->execute([$input, $input]);
                $victim = $findStmt->fetch();

                if ($victim) {
                    if ($victim['role'] === 'super_admin') { $modError = "Нельзя забанить Супер-Админа!"; } 
                    elseif ($victim['id'] == $user['id']) { $modError = "Нельзя забанить самого себя!"; } 
                    else {
                        $expires_at = null;
                        if ($banUnit !== 'perm') {
                            try { $dt = new DateTime(); $dt->modify("+$banAmount $banUnit"); $expires_at = $dt->format('Y-m-d H:i:s'); } 
                            catch (Exception $e) { $expires_at = date('Y-m-d H:i:s', strtotime('+1 day')); }
                        }
                        $pdo->prepare("UPDATE users SET role = 'banned' WHERE id = ?")->execute([$victim['id']]);
                        try {
                            $pdo->prepare("INSERT INTO user_bans (user_id, reason, banned_by_id, banned_at, expires_at) VALUES (?, ?, ?, NOW(), ?)")
                                ->execute([$victim['id'], $reason, $user['id'], $expires_at]);
                        } catch (PDOException $e) { }
                        $modMessage = "Пользователь {$victim['username']} забанен.";
                    }
                } else { $modError = "Пользователь не найден."; }
            }
        }
        // Разбан
        if (isset($_POST['unban_user_input'])) {
            $input = trim($_POST['unban_user_input']);
            if (!empty($input)) {
                $unbanStmt = $pdo->prepare("SELECT id, username FROM users WHERE (username = ? OR id = ?) AND role = 'banned'");
                $unbanStmt->execute([$input, $input]);
                $bannedUser = $unbanStmt->fetch();
                if ($bannedUser) {
                    $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$bannedUser['id']]);
                    $modMessage = "Пользователь {$bannedUser['username']} разбанен.";
                } else { $modError = "Забаненный пользователь не найден."; }
            }
        }
    }
    try { $pendingTopics = $pdo->query("SELECT t.*, u.username, c.title as cat_title FROM topics t JOIN users u ON t.user_id = u.id JOIN categories c ON t.category_id = c.id WHERE t.is_approved = 0 ORDER BY t.created_at ASC")->fetchAll(); } catch (PDOException $e) {}
    try { $pendingCategories = $pdo->query("SELECT * FROM shop_categories WHERE is_approved = 0")->fetchAll(); } catch (PDOException $e) { $pendingCategories = []; }
}


// --- 4. ДАННЫЕ ПРОФИЛЯ ---
$myPosts = [];
try {
    $stmtPosts = $pdo->prepare("SELECT p.*, t.title as topic_title FROM posts p LEFT JOIN topics t ON p.topic_id = t.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 5");
    $stmtPosts->execute([$_SESSION['user_id']]);
    $myPosts = $stmtPosts->fetchAll();
} catch (PDOException $e) {}

$orders = [];
$totalSpent = 0;
try {
    $stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmtOrders->execute([$_SESSION['user_id']]);
    $orders = $stmtOrders->fetchAll();
    foreach($orders as $ord) {
        if ($ord['status'] !== 'cancelled') {
            $amount = isset($ord['total_amount']) ? $ord['total_amount'] : ($ord['total_price'] ?? 0);
            $totalSpent += $amount;
        }
    }
} catch (PDOException $e) {}

$avatarUrl = (!empty($user['avatar']) && file_exists(__DIR__ . '/../' . $user['avatar'])) ? '/' . $user['avatar'] : null;
?>

<style>
    /* === ФОН NEON GRID === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }
    
    .street-font {
        font-family: 'Arial Black', 'Impact', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .bg-main-anim {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px);
        background-size: 80px 80px; perspective: 500px; transform-style: preserve-3d; animation: grid-move 6s linear infinite; box-shadow: inset 0 0 150px rgba(0,0,0,0.9); z-index: -1;
    }
    @keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }
    .bg-main-anim::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 40px 40px; }

    /* === СТИЛИ === */
    .profile-card { background: #fff; color: #000; border: 3px solid #000; box-shadow: 10px 10px 0 #000; }
    .mod-card { box-shadow: 8px 8px 0 rgba(0,0,0,0.5); }
    .btn-street { background: #FCE300; color: #000; border: 2px solid #000; font-family: 'Arial Black', sans-serif; text-transform: uppercase; }
    .btn-street:hover { background: #000; color: #FCE300; }
    .suggestions-list { position: absolute; width: 100%; background: #fff; border: 3px solid #000; border-top: none; z-index: 1000; max-height: 200px; overflow-y: auto; display: none; }
    .suggestion-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; font-weight: bold; font-size: 0.9rem; color: #000; }
    .suggestion-item:hover { background-color: #FCE300; }
    .form-label-sm { font-size: 0.7rem; font-family: 'Arial Black', sans-serif; text-transform: uppercase; color: #666; margin-bottom: 2px; }
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: #eee; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #000; }
    .list-hover-effect { transition: all 0.2s; border-left: 0px solid transparent; }
    .list-hover-effect:hover { background: #f8f9fa; border-left: 5px solid #000; padding-left: 1.5rem !important; }
    .ban-input { border-radius: 0; border: 2px solid #000; font-weight: bold; font-size: 0.9rem; }
    .ban-input:focus { box-shadow: none; background-color: #fff9c4; }
    .card-header:first-child {border-radius: 0;}
</style>

<div class="bg-main-anim"></div>

<div class="container py-3 py-md-5" style="position: relative; z-index: 2;">
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'topic_pending'): ?>
       <div class="alert alert-warning border-3 border-dark rounded-0 fw-bold text-center mb-4 shadow-sm street-font text-dark">
            <i class="bi bi-hourglass-split"></i> Тема отправлена на модерацию. Ожидайте одобрения!
       </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- === ЛЕВАЯ КОЛОНКА === -->
        <div class="col-12 col-md-4 col-lg-3 order-1">
            <div class="profile-card d-flex flex-column mb-4">
                <div class="bg-black text-white text-center py-3 street-font border-bottom border-dark">
                    <?= htmlspecialchars($user['username']) ?>
                </div>
                <div class="p-4 text-center flex-grow-1">
                    <div class="mx-auto mb-3 border-2 border-dark d-flex align-items-center justify-content-center overflow-hidden bg-light position-relative" style="width: 140px; height: 140px; border-radius: 50%;">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span class="display-3 street-font text-muted"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <span class="badge bg-warning text-dark border border-dark rounded-0 px-3 py-2 text-uppercase street-font"><?= strtoupper($user['role']) ?></span>
                        <div class="small text-muted fw-bold mt-2 text-uppercase font-monospace">ID: #<?= $user['id'] ?></div>
                    </div>
                    <?php if ($user['role'] === 'super_admin'): ?>
                        <div class="mb-3">
                            <a href="/admin/index.php" class="btn btn-dark w-100 rounded-0 street-font border-2 border-warning text-warning py-2 shadow-sm">★ АДМИН ПАНЕЛЬ</a>
                        </div>
                    <?php endif; ?>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-street py-2" data-bs-toggle="modal" data-bs-target="#avatarModal"><?= $avatarUrl ? 'СМЕНИТЬ ФОТО' : 'ЗАГРУЗИТЬ' ?></button>
                    </div>

                    <!-- КНОПКА УДАЛЕНИЯ -->
                    <div class="mt-4 pt-3 border-top border-dark">
                        <button type="button" class="btn btn-outline-danger w-100 rounded-0 street-font border-2 py-2" data-bs-toggle="modal" data-bs-target="#deleteProfileModal">
                            <i class="bi bi-trash3-fill me-1"></i> Удалить аккаунт
                        </button>
                    </div>
                </div>
                <div class="border-top border-dark p-0">
                    <a href="/actions/logout.php" class="btn btn-light w-100 rounded-0 street-font py-3 hover-bg-gray">Выйти</a>
                </div>
            </div>
        </div>

        <!-- === ПРАВАЯ КОЛОНКА === -->
        <div class="col-12 col-md-8 col-lg-9 order-2">
            
            <?php if ($isModerator): ?>
                <div class="mb-5">
                    <h4 class="street-font text-white mb-3"><i class="bi bi-shield-lock-fill text-danger"></i> Центр управления</h4>
                    <?php if ($modMessage): ?><div class="alert alert-success border-3 border-dark rounded-0 fw-bold text-center mb-3"><?= $modMessage ?></div><?php endif; ?>
                    <?php if ($modError): ?><div class="alert alert-danger border-3 border-dark rounded-0 fw-bold text-center mb-3"><?= $modError ?></div><?php endif; ?>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card border-3 border-danger rounded-0 h-100 mod-card">
                                <div class="card-header bg-danger text-white street-font">Темы (<?= count($pendingTopics) ?>)</div>
                                <div class="card-body bg-light p-0 custom-scroll" style="max-height: 200px; overflow-y: auto;">
                                    <?php if (empty($pendingTopics)): ?>
                                        <div class="p-3 text-center text-muted small fw-bold text-uppercase">Пусто</div>
                                    <?php else: ?>
                                        <?php foreach ($pendingTopics as $pt): ?>
                                            <div class="p-2 border-bottom border-dark bg-white d-flex justify-content-between align-items-center">
                                                <div class="text-truncate me-2"><small class="fw-bold d-block text-truncate"><?= htmlspecialchars($pt['title']) ?></small></div>
                                                <form method="POST"><input type="hidden" name="approve_topic_id" value="<?= $pt['id'] ?>"><button class="btn btn-sm btn-success rounded-0 fw-bold border-dark">ОК</button></form>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-3 border-primary rounded-0 h-100 mod-card">
                                <div class="card-header bg-primary text-white street-font">Категории (<?= count($pendingCategories) ?>)</div>
                                <div class="card-body bg-light p-0 custom-scroll" style="max-height: 200px; overflow-y: auto;">
                                    <?php if (empty($pendingCategories)): ?>
                                        <div class="p-3 text-center text-muted small fw-bold text-uppercase">Пусто</div>
                                    <?php else: ?>
                                        <?php foreach ($pendingCategories as $pc): ?>
                                            <div class="p-2 border-bottom border-dark bg-white d-flex justify-content-between">
                                                <small class="fw-bold"><?= htmlspecialchars($pc['title']) ?></small>
                                                <form method="POST"><input type="hidden" name="approve_cat_id" value="<?= $pc['id'] ?>"><button class="btn btn-sm btn-success rounded-0 fw-bold border-dark">ОК</button></form>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-3 border-dark rounded-0 mod-card">
                                <div class="card-header bg-black text-danger street-font border-bottom border-dark"><i class="bi bi-hammer me-2"></i> Банхаммер</div>
                                <div class="card-body bg-white p-3">
                                    <form method="POST">
                                        <div class="input-group">
                                            <span class="input-group-text bg-danger text-white border-2 border-dark rounded-0 fw-bold street-font">БАН</span>
                                            <input type="text" name="ban_user_input" class="form-control ban-input" placeholder="Ник или ID жертвы" required style="min-width: 120px;">
                                            <input type="number" name="ban_amount" class="form-control ban-input text-center" value="1" min="1" placeholder="Срок" style="max-width: 80px;">
                                            <select name="ban_unit" class="form-select ban-input" id="banUnitSelect" style="max-width: 100px;">
                                                <option value="days">Дней</option>
                                                <option value="hours">Часов</option>
                                                <option value="months">Месяцев</option>
                                                <option value="perm">НАВСЕГДА</option>
                                            </select>
                                            <input type="text" name="ban_reason" class="form-control ban-input" placeholder="Причина бана..." style="min-width: 150px;">
                                            <button type="submit" class="btn btn-black rounded-0 street-font text-danger border-2 border-dark px-4" style="background: #000;">ВЫПОЛНИТЬ</button>
                                        </div>
                                    </form>
                                    <form method="POST" class="mt-3">
                                        <div class="input-group">
                                            <span class="input-group-text bg-success text-white border-2 border-dark rounded-0 fw-bold street-font">UNBAN</span>
                                            <input type="text" name="unban_user_input" class="form-control ban-input" placeholder="Ник или ID для разбана" required>
                                            <button type="submit" class="btn btn-outline-success rounded-0 street-font border-2 border-dark px-4">РАЗБАНИТЬ</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-3 border-dark mb-4 profile-card">
                <div class="card-header bg-warning text-dark border-bottom border-dark py-2 d-flex justify-content-between align-items-center">
                    <span class="street-font">Личные данные</span>
                    <button type="button" id="saveProfileBtn" class="btn btn-sm btn-dark rounded-0 street-font text-warning border border-dark"><i class="bi bi-save me-1"></i> СОХРАНИТЬ</button>
                </div>
                <div class="card-body p-4">
                    <form action="/actions/update_profile.php" method="POST" id="profileForm">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label-sm">ФИО</label>
                                <input type="text" name="full_name" class="form-control border-2 border-dark rounded-0 fw-bold" placeholder="Иванов Иван" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6 position-relative">
                                <label class="form-label-sm">ГОРОД</label>
                                <input type="text" id="cityInput" name="city" class="form-control border-2 border-dark rounded-0 fw-bold" placeholder="Начните вводить..." value="<?= htmlspecialchars($user['city'] ?? '') ?>" autocomplete="off">
                                <div id="cityList" class="suggestions-list"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label-sm">ТЕЛЕФОН</label>
                                <input type="text" id="phoneInput" name="phone" class="form-control border-2 border-dark rounded-0 fw-bold" placeholder="+7 (___) ___-__-__" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label-sm">АДРЕС</label>
                                <input type="text" name="address" class="form-control border-2 border-dark rounded-0 fw-bold" placeholder="Улица, дом, кв" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card border-3 border-dark h-100 mb-0 profile-card d-flex flex-column">
                        <div class="card-header bg-black text-white py-2 d-flex justify-content-between align-items-center border-bottom border-dark">
                            <span class="street-font">Заказы</span>
                            <span class="badge bg-warning text-dark rounded-0 street-font"><?= count($orders) ?></span>
                        </div>
                        <div class="list-group list-group-flush custom-scroll flex-grow-1" style="max-height: 300px; overflow-y: auto;">
                            <?php if(empty($orders)): ?>
                                <div class="list-group-item text-center text-muted py-5 border-0"><i class="bi bi-cart-x fs-1 d-block mb-2 opacity-50"></i><span class="small fw-bold text-uppercase street-font">История пуста</span></div>
                            <?php else: ?>
                                <?php foreach($orders as $ord): ?>
                                    <?php 
                                        $amount = isset($ord['total_amount']) ? $ord['total_amount'] : ($ord['total_price'] ?? 0);
                                        switch ($ord['status']) {
                                            case 'new': $statusClass = 'bg-warning text-dark'; break;
                                            case 'completed': $statusClass = 'bg-success text-white'; break;
                                            case 'cancelled': $statusClass = 'bg-danger text-white'; break;
                                            default: $statusClass = 'bg-secondary text-white';
                                        }
                                    ?>
                                    <a href="/pages/my_orders.php" class="list-group-item list-group-item-action py-3 px-3 border-bottom list-hover-effect">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="street-font text-dark">#<?= $ord['id'] ?></span>
                                            <span class="street-font fs-5 text-dark"><?= number_format($amount, 0, '', ' ') ?> ₽</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted fw-bold font-monospace"><?= date('d.m.y', strtotime($ord['created_at'])) ?></small>
                                            <span class="badge <?= $statusClass ?> rounded-0 text-uppercase border border-dark fw-bold" style="font-size: 0.65rem;"><?= $ord['status'] ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white p-0 border-top border-3 border-dark">
                            <div class="p-3 border-bottom border-dark border-1 text-center"><a href="/pages/my_orders.php" class="btn btn-dark w-100 rounded-0 street-font border-2 border-dark shadow-sm">ВСЯ ИСТОРИЯ</a></div>
                            <div class="py-2 text-center text-dark bg-warning bg-opacity-10"><small class="text-uppercase fw-bold opacity-75 d-block mb-0 street-font" style="font-size: 0.65rem;">Всего потрачено</small><span class="fs-4 street-font lh-1"><?= number_format($totalSpent, 0, '', ' ') ?> ₽</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card border-3 border-dark h-100 mb-0 profile-card">
                        <div class="card-header bg-white text-dark border-bottom border-dark py-2 street-font">Активность на форуме</div>
                        <div class="list-group list-group-flush">
                            <?php if(empty($myPosts)): ?>
                                <div class="list-group-item text-center text-muted py-5 border-0 street-font">Нет активности</div>
                            <?php else: ?>
                                <?php foreach($myPosts as $mp): ?>
                                    <a href="/pages/topic.php?id=<?= $mp['topic_id'] ?>#post-<?= $mp['id'] ?>" class="list-group-item list-group-item-action py-3 border-bottom list-hover-effect">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong class="text-truncate small text-uppercase street-font" style="max-width: 70%;"><?= htmlspecialchars($mp['topic_title'] ?? 'Тема удалена') ?></strong>
                                            <small class="text-muted fw-bold" style="font-size: 0.7rem;"><?= date('d.m', strtotime($mp['created_at'])) ?></small>
                                        </div>
                                        <div class="text-secondary text-truncate small fst-italic">"<?= htmlspecialchars($mp['content']) ?>"</div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="avatarModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-3 border-dark rounded-0"><div class="modal-header bg-warning border-bottom border-dark py-2"><h6 class="modal-title street-font">АВАТАР</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-3"><form action="/actions/upload_avatar.php" method="POST" enctype="multipart/form-data"><input type="file" name="avatar" class="form-control form-control-sm mb-3 border-dark rounded-0" required accept="image/*"><button type="submit" class="btn btn-street w-100 btn-sm">ЗАГРУЗИТЬ</button></form></div></div></div></div>

<!-- ОБНОВЛЕННОЕ МОДАЛЬНОЕ ОКНО СОХРАНЕНИЯ (С ПОДСКАЗКАМИ) -->
<div class="modal fade" id="passwordConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-3 border-dark rounded-0">
            <div class="modal-header bg-warning border-bottom-4 border-dark">
                <h5 class="modal-title street-font"><i class="bi bi-shield-lock-fill me-2"></i> Сохранение данных</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="alert alert-warning border-2 border-warning rounded-0 d-flex align-items-start mb-3 bg-warning bg-opacity-10">
                    <div class="me-3 mt-1"><i class="bi bi-info-circle-fill fs-3 text-warning text-dark"></i></div>
                    <div>
                        <strong class="text-uppercase text-dark street-font">Проверка безопасности</strong>
                        <p class="small fw-bold mb-0 text-dark opacity-75">Вы обновляете личную информацию. Чтобы никто не изменил её без вашего ведома, подтвердите действие паролем.</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted street-font">Ваш текущий пароль:</label>
                    <input type="password" id="confirmPasswordInput" class="form-control form-control-lg border-2 border-dark text-center street-font rounded-0" placeholder="••••••••">
                </div>
                <button type="button" id="confirmSaveBtn" class="btn btn-black w-100 rounded-0 street-font border-2 border-dark py-3 shadow-sm text-warning" style="background: #000;">Подтвердить и сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-4 border-danger">
            <div class="modal-header bg-danger text-white border-bottom-4 border-dark">
                <h5 class="modal-title street-font"><i class="bi bi-exclamation-triangle-fill me-2"></i> УДАЛЕНИЕ АККАУНТА</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="alert alert-danger border-2 border-danger rounded-0 d-flex align-items-center mb-4 bg-danger bg-opacity-10">
                    <div class="me-3"><i class="bi bi-radioactive fs-1 text-danger"></i></div>
                    <div>
                        <strong class="text-uppercase text-danger street-font">ВНИМАНИЕ! ЭТО КОНЕЦ.</strong><br>
                        <span class="small fw-bold">При удалении профиля будет стёрто:</span>
                        <ul class="mb-0 small ps-3 fw-bold mt-1 text-dark">
                            <li>История всех заказов</li>
                            <li>Все сообщения и темы на форуме</li>
                            <li>Аватар и личные данные</li>
                        </ul>
                    </div>
                </div>
                <form action="/actions/delete_profile.php" method="POST">
                    <input type="hidden" name="action" value="self_delete">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase street-font">Подтвердите паролем:</label>
                        <input type="password" name="password" class="form-control rounded-0 border-2 border-dark fw-bold text-center" placeholder="Ваш текущий пароль" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 rounded-0 street-font border-2 border-dark py-3 shadow-sm">Я всё понял, удалить навсегда</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/imask@7.1.3/dist/imask.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneEl = document.getElementById('phoneInput');
    if (phoneEl && window.IMask) IMask(phoneEl, { mask: '+{7} (000) 000-00-00' });
    const token = "a5944510b64d081f93f9c73d937015494191c01e";
    const cityInput = document.getElementById("cityInput");
    const cityList = document.getElementById("cityList");
    if (cityInput) {
        cityInput.addEventListener("input", function() {
            const query = this.value;
            if (query.length < 2) { cityList.style.display = 'none'; return; }
            fetch("https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address", { method: "POST", mode: "cors", headers: { "Content-Type": "application/json", "Accept": "application/json", "Authorization": "Token " + token }, body: JSON.stringify({ query: query, from_bound: { value: "city" }, to_bound: { value: "settlement" }, count: 5 }) }).then(r => r.json()).then(res => {
                cityList.innerHTML = '';
                if (res.suggestions.length > 0) { cityList.style.display = 'block'; res.suggestions.forEach(item => { const div = document.createElement("div"); div.className = "suggestion-item"; div.textContent = item.data.city || item.value; div.onclick = function() { cityInput.value = item.data.city || item.value; cityList.style.display = 'none'; }; cityList.appendChild(div); }); } else { cityList.style.display = 'none'; }
            }).catch(e => console.log(e));
        });
        document.addEventListener('click', e => { if (e.target !== cityInput && e.target !== cityList) cityList.style.display = 'none'; });
    }
    const saveBtn = document.getElementById('saveProfileBtn');
    const confirmBtn = document.getElementById('confirmSaveBtn');
    if (saveBtn) saveBtn.addEventListener('click', () => new bootstrap.Modal(document.getElementById('passwordConfirmModal')).show());
    if (confirmBtn) confirmBtn.addEventListener('click', () => { const pwd = document.getElementById('confirmPasswordInput').value; if (!pwd) { alert('Пароль обязателен!'); return; } const form = document.getElementById('profileForm'); const h = document.createElement('input'); h.type='hidden'; h.name='password_confirm'; h.value=pwd; form.appendChild(h); form.submit(); });
    const banUnitSelect = document.getElementById('banUnitSelect');
    const banAmountInput = document.querySelector('input[name="ban_amount"]');
    if(banUnitSelect && banAmountInput) { banUnitSelect.addEventListener('change', function() { if(this.value === 'perm') { banAmountInput.disabled = true; banAmountInput.value = ''; } else { banAmountInput.disabled = false; if(banAmountInput.value === '') banAmountInput.value = '1'; } }); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
    