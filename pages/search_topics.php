<?php
// pages/search_topics.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$search = trim((string)($_GET['q'] ?? ''));
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$whereClause = "WHERE t.is_approved = 1";
$params = [];

if ($search !== '') {
    $whereClause .= " AND t.title LIKE ?";
    $params[] = "%$search%";
}

if ($catId > 0) {
    $whereClause .= " AND t.category_id = ?";
    $params[] = $catId;
}

$sql = "
    SELECT t.*, COALESCE(u.username, 'deleted') AS username, u.avatar,
           COALESCE(c.title, 'Без раздела') AS cat_title,
           (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) AS replies_count
    FROM topics t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN categories c ON t.category_id = c.id
    $whereClause
    ORDER BY t.created_at DESC
    LIMIT 60
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($topics)) {
    echo '<div class="alert alert-light border-3 border-dark rounded-0 text-center py-5 shadow-sm col-12">
            <i class="bi bi-search fs-1 mb-3 d-block"></i>
            <h4 class="street-font">Ничего не найдено</h4>
            <p class="text-muted fw-bold">Попробуйте другой запрос.</p>
          </div>';
    exit;
}

foreach ($topics as $t) {
    $date = date('d.m', strtotime((string)$t['created_at']));
    $avatarUrl = getAvatarUrl($t['avatar'] ?? null);
    if ($avatarUrl) {
        $avatarHtml = '<img src="' . e($avatarUrl) . '" alt="' . e($t['username']) . '" class="rounded-circle border border-dark me-2" width="24" height="24" style="object-fit:cover;">';
    } else {
        $avatarHtml = '<div class="avatar-fallback-badge me-2" style="width:24px;height:24px;font-size:0.7rem;">' . e(safeFirstChar($t['username'])) . '</div>';
    }

    // Безопасная подсветка совпадения без повреждения HTML-сущностей
    $rawTitle = (string)$t['title'];
    if ($search !== '') {
        $parts = preg_split('/(' . preg_quote($search, '/') . ')/iu', $rawTitle, -1, PREG_SPLIT_DELIM_CAPTURE);
        $titleHtml = '';
        if (is_array($parts)) {
            foreach ($parts as $idx => $part) {
                if ($idx % 2 === 1) {
                    $titleHtml .= '<span class="bg-warning text-dark px-1">' . e($part) . '</span>';
                } else {
                    $titleHtml .= e($part);
                }
            }
        } else {
            $titleHtml = e($rawTitle);
        }
    } else {
        $titleHtml = e($rawTitle);
    }

    echo '
    <div class="col-md-6 col-xl-4 d-flex align-items-stretch topic-item-anim">
        <div class="card w-100 rounded-0 topic-card position-relative">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-black text-white rounded-0 border border-dark text-uppercase street-font" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                        ' . e($t['cat_title']) . '
                    </span>
                    <small class="text-muted fw-bold font-monospace" style="font-size: 0.7rem;">
                        ' . e($date) . '
                    </small>
                </div>
                <h6 class="street-font lh-sm mb-3">
                    <a href="/pages/topic.php?id=' . (int)$t['id'] . '" class="text-dark text-decoration-none stretched-link">
                        ' . $titleHtml . '
                    </a>
                </h6>
                <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top border-2 border-light">
                    <div class="d-flex align-items-center">
                        ' . $avatarHtml . '
                        <span class="small fw-bold text-muted text-truncate street-font" style="max-width: 80px;">
                            @' . e($t['username']) . '
                        </span>
                    </div>
                    <div class="fw-bold small text-dark">
                        <i class="bi bi-chat-fill text-warning me-1" style="text-shadow: 1px 1px 0 #000;"></i> ' . (int)$t['replies_count'] . '
                    </div>
                </div>
            </div>
        </div>
    </div>';
}
