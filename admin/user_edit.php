<?php
// admin/user_edit.php
require_once __DIR__ . '/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /admin/users.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirectWithFlash('/admin/users.php', 'Пользователь не найден.', 'warning');
}

$actorRole = $_SESSION['role'] ?? 'admin';
$actorId = (int)$_SESSION['user_id'];
if ($id !== $actorId && !canManageTargetUser($actorRole, $user['role'])) {
    redirectWithFlash('/admin/users.php', 'Недостаточно прав для редактирования этого пользователя.', 'danger');
}

$allowedRoles = isSuperAdmin() ? ['user', 'admin', 'super_admin', 'banned'] : ['user', 'banned'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect('/admin/user_edit.php?id=' . $id);

    $username  = trim((string)($_POST['username'] ?? ''));
    $email     = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
    $full_name = mb_substr(trim((string)($_POST['full_name'] ?? '')), 0, 100, 'UTF-8');
    $new_pass  = (string)($_POST['new_password'] ?? '');

    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ0-9_.\-]{3,30}$/u', $username) || !validateEmailStrict($email)) {
        redirectWithFlash('/admin/user_edit.php?id=' . $id, 'Никнейм (3–30 символов без пробелов) и Email (латиница) обязательны.', 'danger');
    }

    $role = $user['role'];
    if ($id !== $actorId && isset($_POST['role']) && in_array($_POST['role'], $allowedRoles, true)) {
        $role = $_POST['role'];
    }

    try {
        if ($new_pass !== '') {
            if (strlen($new_pass) < 6 || strlen($new_pass) > 64 || !preg_match('/^[\x21-\x7E]+$/', $new_pass)) {
                redirectWithFlash('/admin/user_edit.php?id=' . $id, 'Новый пароль: 6–64 символа латиницы и цифр без пробелов.', 'danger');
            }
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, password_hash = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$username, $email, $full_name, $role, $hash, $id]);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$username, $email, $full_name, $role, $id]);
        }

        redirectWithFlash('/admin/users.php', 'Данные пользователя обновлены.', 'success');
    } catch (\PDOException $e) {
        error_log("User edit error: " . $e->getMessage());
        redirectWithFlash('/admin/user_edit.php?id=' . $id, 'Ошибка обновления (возможно, ник или email уже занят).', 'danger');
    }
}

require_once __DIR__ . '/layout_header.php';
$avatarSrc = getAvatarUrl($user['avatar'] ?? '');
?>

<div class="d-flex align-items-center mb-4">
    <a href="/admin/users.php" class="btn btn-outline-dark rounded-0 me-3" aria-label="Назад"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    <h2 class="fw-black text-uppercase m-0">Редактирование: @<?= e($user['username']) ?></h2>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card admin-card p-4">
            <form method="POST">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label for="editUsername" class="fw-bold small text-muted mb-1">LOGIN (НИКНЕЙМ)</label>
                    <input id="editUsername" type="text" name="username" class="form-control" value="<?= e($user['username']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="editEmail" class="fw-bold small text-muted mb-1">EMAIL</label>
                    <input id="editEmail" type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="editFullName" class="fw-bold small text-muted mb-1">ФИО</label>
                    <input id="editFullName" type="text" name="full_name" class="form-control" value="<?= e($user['full_name'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="editRole" class="fw-bold small text-muted mb-1">РОЛЬ</label>
                    <select id="editRole" name="role" class="form-select" <?= ($id === $actorId) ? 'disabled' : '' ?>>
                        <?php foreach ($allowedRoles as $rOpt): ?>
                            <option value="<?= e($rOpt) ?>" <?= $user['role'] === $rOpt ? 'selected' : '' ?>><?= e($rOpt) ?></option>
                        <?php endforeach; ?>
                        <?php if (!in_array($user['role'], $allowedRoles, true)): ?>
                            <option value="<?= e($user['role']) ?>" selected><?= e($user['role']) ?></option>
                        <?php endif; ?>
                    </select>
                    <?php if ($id === $actorId): ?>
                        <div class="form-text text-danger fw-bold">Вы не можете изменить роль самому себе здесь.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4 border-top border-2 border-dark pt-3">
                    <label for="editPass" class="fw-bold small text-danger mb-1">СМЕНИТЬ ПАРОЛЬ</label>
                    <input id="editPass" type="password" name="new_password" class="form-control border-danger" placeholder="Оставьте пустым, если не меняете" minlength="6">
                    <div class="form-text">Минимум 6 символов, если меняете пароль.</div>
                </div>

                <button type="submit" class="btn btn-dark btn-admin w-100 py-3 text-warning">СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card admin-card p-4 bg-light">
            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3">Статистика</h5>
            <p><strong>Дата регистрации:</strong> <?= e($user['created_at']) ?></p>
            <p><strong>ID:</strong> #<?= (int)$user['id'] ?></p>

            <?php if ($avatarSrc): ?>
                <hr>
                <p class="fw-bold mb-2">Аватар:</p>
                <img src="<?= e($avatarSrc) ?>" alt="<?= e($user['username']) ?>" class="img-thumbnail rounded-0 border-dark" style="max-width: 150px;">
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
