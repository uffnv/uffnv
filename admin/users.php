<?php require_once 'layout_header.php'; ?>

<?php
// ОБРАБОТЧИК ДЕЙСТВИЙ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Создание пользователя
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $rawPassword = trim($_POST['password']);
        $role = $_POST['role'];
        
        if (!empty($username) && !empty($email) && !empty($rawPassword)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if (!$check->fetch()) {
                // Используем password_hash
                $hash = password_hash($rawPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                if ($stmt->execute([$username, $email, $hash, $role])) {
                    echo "<script>alert('Пользователь успешно создан!');</script>";
                } else {
                    echo "<script>alert('Ошибка при создании.');</script>";
                }
            } else {
                echo "<script>alert('Ошибка: Такой пользователь уже существует!');</script>";
            }
        }
    }

    // 2. Обновление пользователя
    if (isset($_POST['update_user'])) {
        $id = (int)$_POST['id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        $params = [$username, $email, $role, $id];
        
        // Если указан новый пароль
        if (!empty($_POST['new_password'])) {
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?";
            $params = [$username, $email, $role, password_hash($_POST['new_password'], PASSWORD_DEFAULT), $id];
        }
        
        $pdo->prepare($sql)->execute($params);
    }

    // 3. Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        } else {
            echo "<script>alert('Нельзя удалить свой собственный аккаунт!');</script>";
        }
    }
}

// Поиск
$search = trim($_GET['q'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Запрос списка
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY id DESC LIMIT 50");
$stmt->execute($params);
$usersList = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">Пользователи</h2>
    <button class="btn btn-admin btn-admin-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus-fill"></i> Добавить
    </button>
</div>

<!-- ПОИСК -->
<div class="admin-card p-3 mb-4">
    <form class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Поиск по нику или email..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-admin btn-admin-dark">Найти</button>
        <?php if($search): ?><a href="users.php" class="btn btn-outline-secondary">Сброс</a><?php endif; ?>
    </form>
</div>

<!-- ТАБЛИЦА -->
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
                <?php foreach($usersList as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-light border border-dark rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px;">
                                <?= strtoupper(substr($u['username'], 0, 1)) ?>
                            </div>
                            <span class="fw-bold"><?= htmlspecialchars($u['username']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php 
                            $badgeClass = 'bg-success';
                            if ($u['role'] === 'super_admin') {
                                $badgeClass = 'bg-danger';
                            } elseif ($u['role'] === 'admin') {
                                $badgeClass = 'bg-warning text-dark';
                            } elseif ($u['role'] === 'banned') {
                                $badgeClass = 'bg-dark';
                            }
                        ?>
                        <span class="badge <?= $badgeClass ?> rounded-0 text-uppercase"><?= $u['role'] ?></span>
                    </td>
                    <td class="small"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-admin btn-admin-dark me-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editUserModal<?= $u['id'] ?>">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        
                        <?php if($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Удалить пользователя <?= htmlspecialchars($u['username']) ?> навсегда?');">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" name="delete_user" class="btn btn-sm btn-admin btn-admin-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- МОДАЛКА РЕДАКТИРОВАНИЯ -->
                <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content border-3 border-dark rounded-0">
                            <div class="modal-header bg-black text-white rounded-0">
                                <h5 class="modal-title street-font">Редактирование: <?= htmlspecialchars($u['username']) ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <div class="mb-3">
                                        <label class="fw-bold small">Никнейм</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold small">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold small">Роль</label>
                                        <select name="role" class="form-select">
                                            <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
                                            <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                            <option value="super_admin" <?= $u['role']=='super_admin'?'selected':'' ?>>Super Admin</option>
                                            <option value="banned" <?= $u['role']=='banned'?'selected':'' ?>>Banned</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold small text-danger">Новый пароль (оставьте пустым, если не меняете)</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="******">
                                    </div>
                                    <button type="submit" name="update_user" class="btn btn-admin btn-admin-primary w-100">Сохранить изменения</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- МОДАЛКА СОЗДАНИЯ -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-3 border-dark rounded-0">
            <div class="modal-header bg-warning border-bottom border-dark rounded-0">
                <h5 class="modal-title street-font text-dark">Новый пользователь</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
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
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Роль</label>
                        <select name="role" class="form-select">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="create_user" class="btn btn-admin btn-admin-dark w-100">Создать</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
