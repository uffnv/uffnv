<?php
// admin/users.php
require_once __DIR__ . '/admin_auth.php';

$actorRole = $_SESSION['role'] ?? 'admin';
$actorId = (int)$_SESSION['user_id'];
$allowedRoles = isSuperAdmin() ? ['user', 'admin', 'super_admin', 'banned'] : ['user', 'banned'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect('/admin/users.php');

    // 1. Создание пользователя
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rawPassword = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($rawPassword, 'UTF-8') < 6) {
            redirectWithFlash('/admin/users.php', 'Проверьте никнейм, корректный email и пароль (минимум 6 символов).', 'danger');
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            redirectWithFlash('/admin/users.php', 'Пользователь с таким ником или email уже существует.', 'warning');
        }

        $hash = password_hash($rawPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $hash, $role]);
        redirectWithFlash('/admin/users.php', 'Пользователь успешно создан.', 'success');
    }

    // 2. Обновление пользователя
    if (isset($_POST['update_user'])) {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $requestedRole = $_POST['role'] ?? 'user';

        $targetStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch();

        if (!$target) {
            redirectWithFlash('/admin/users.php', 'Пользователь не найден.', 'danger');
        }
        if ($id !== $actorId && !canManageTargetUser($actorRole, $target['role'])) {
            redirectWithFlash('/admin/users.php', 'Недостаточно прав для редактирования этого пользователя.', 'danger');
        }

        // Роль: нельзя сменить себе или назначить выше своих прав
        $role = $target['role'];
        if ($id !== $actorId && in_array($requestedRole, $allowedRoles, true)) {
            $role = $requestedRole;
        }

        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithFlash('/admin/users.php', 'Некорректные никнейм или email.', 'danger');
        }

        if (!empty($_POST['new_password'])) {
            if (mb_strlen($_POST['new_password'], 'UTF-8') < 6) {
                redirectWithFlash('/admin/users.php', 'Новый пароль должен быть не менее 6 символов.', 'danger');
            }
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?";
            $params = [$username, $email, $role, password_hash($_POST['new_password'], PASSWORD_DEFAULT), $id];
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
            $params = [$username, $email, $role, $id];
        }

        $pdo->prepare($sql)->execute($params);
        redirectWithFlash('/admin/users.php', 'Пользователь обновлён.', 'success');
    }

    // 3. Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $actorId) {
            redirectWithFlash('/admin/users.php', 'Нельзя удалить свой собственный аккаунт из таблицы.', 'warning');
        }

        $targetStmt = $pdo->prepare("SELECT role, avatar FROM users WHERE id = ? LIMIT 1");
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch();

        if (!$target) {
            redirectWithFlash('/admin/users.php', 'Пользователь не найден.', 'danger');
        }
        if (!canManageTargetUser($actorRole, $target['role'])) {
            redirectWithFlash('/admin/users.php', 'Недостаточно прав для удаления этого пользователя.', 'danger');
        }

        if (!empty($target['avatar'])) {
            safeUnlinkUpload($target['avatar']);
        }
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        redirectWithFlash('/admin/users.php', 'Пользователь удалён.', 'success');
    }
}

require_once __DIR__ . '/layout_header.php';

$search = trim($_GET['q'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalUsers / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY id DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$usersList = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">Пользователи (<?= $totalUsers ?>)</h2>
    <button type="button" class="btn btn-admin btn-admin-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus-fill" aria-hidden="true"></i> Добавить
    </button>
</div>

<div class="admin-card p-3 mb-4">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" class="form-control" placeholder="Поиск по нику или email..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-admin btn-admin-dark">Найти</button>
        <?php if ($search !== ''): ?><a href="users.php" class="btn btn-outline-secondary rounded-0">Сброс</a><?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin table-hover m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Регистрация</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $u):
                    $badgeClass = 'bg-success';
                    if ($u['role'] === 'super_admin') {
                        $badgeClass = 'bg-danger';
                    } elseif ($u['role'] === 'admin') {
                        $badgeClass = 'bg-warning text-dark';
                    } elseif ($u['role'] === 'banned') {
                        $badgeClass = 'bg-dark';
                    }
                    $canManage = ((int)$u['id'] === $actorId) || canManageTargetUser($actorRole, $u['role']);
                    $confirmMsg = json_encode('Удалить пользователя ' . $u['username'] . ' навсегда?', JSON_UNESCAPED_UNICODE);
                ?>
                <tr>
                    <td>#<?= (int)$u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-light border border-dark rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px;">
                                <?= e(safeFirstChar($u['username'])) ?>
                            </div>
                            <span class="fw-bold"><?= e($u['username']) ?></span>
                        </div>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <span class="badge <?= e($badgeClass) ?> rounded-0 text-uppercase"><?= e($u['role']) ?></span>
                    </td>
                    <td class="small"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($canManage): ?>
                            <button type="button" class="btn btn-sm btn-admin btn-admin-dark me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editUserModal<?= (int)$u['id'] ?>"
                                    aria-label="Быстрое редактирование <?= e($u['username']) ?>">
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            </button>
                            <a href="user_edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 me-1" title="Полная карточка" aria-label="Карточка <?= e($u['username']) ?>">
                                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ((int)$u['id'] !== $actorId && canManageTargetUser($actorRole, $u['role'])): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm(<?= e($confirmMsg) ?>);">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" name="delete_user" value="1" class="btn btn-sm btn-admin btn-admin-danger" aria-label="Удалить <?= e($u['username']) ?>">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav aria-label="Страницы пользователей" class="mb-4">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link rounded-0 border-dark fw-bold" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- МОДАЛКИ РЕДАКТИРОВАНИЯ (вне <table> для валидного HTML) -->
<?php foreach ($usersList as $u):
    if ((int)$u['id'] !== $actorId && !canManageTargetUser($actorRole, $u['role'])) {
        continue;
    }
?>
<div class="modal fade" id="editUserModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-3 border-dark rounded-0">
            <div class="modal-header bg-black text-white rounded-0">
                <h5 class="modal-title street-font">Редактирование: <?= e($u['username']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <div class="mb-3">
                        <label class="fw-bold small">Никнейм</label>
                        <input type="text" name="username" class="form-control" value="<?= e($u['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Роль</label>
                        <select name="role" class="form-select" <?= ((int)$u['id'] === $actorId) ? 'disabled' : '' ?>>
                            <?php foreach ($allowedRoles as $rOpt): ?>
                                <option value="<?= e($rOpt) ?>" <?= $u['role'] === $rOpt ? 'selected' : '' ?>><?= e($rOpt) ?></option>
                            <?php endforeach; ?>
                            <?php if (!in_array($u['role'], $allowedRoles, true)): ?>
                                <option value="<?= e($u['role']) ?>" selected><?= e($u['role']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-danger">Новый пароль (оставьте пустым, если не меняете)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="******" minlength="6">
                    </div>
                    <button type="submit" name="update_user" value="1" class="btn btn-admin btn-admin-primary w-100">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- МОДАЛКА СОЗДАНИЯ -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-3 border-dark rounded-0">
            <div class="modal-header bg-warning border-bottom border-dark rounded-0">
                <h5 class="modal-title street-font text-dark">Новый пользователь</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="fw-bold small">Никнейм</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Пароль</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Роль</label>
                        <select name="role" class="form-select">
                            <?php foreach ($allowedRoles as $rOpt): ?>
                                <option value="<?= e($rOpt) ?>"><?= e($rOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="create_user" value="1" class="btn btn-admin btn-admin-dark w-100">Создать</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
