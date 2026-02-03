<?php require_once 'layout_header.php'; ?>

<?php
// Проверка ID
if (!isset($_GET['id'])) {
    echo "<script>window.location='users.php';</script>";
    exit;
}
$id = (int)$_GET['id'];

// Получаем данные юзера
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Пользователь не найден.";
    exit;
}

// ОБРАБОТКА СОХРАНЕНИЯ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $new_pass = trim($_POST['new_password']);

    // Валидация прав на смену роли
    // Нельзя менять роль самому себе через эту форму (безопасность)
    if ($id == $_SESSION['user_id']) {
        $role = $user['role']; 
    }

    try {
        if (!empty($new_pass)) {
            // Если введен пароль - меняем всё + пароль
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, email=?, full_name=?, role=?, password=? WHERE id=?";
            $pdo->prepare($sql)->execute([$username, $email, $full_name, $role, $hash, $id]);
        } else {
            // Если пароль пустой - меняем только данные
            $sql = "UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?";
            $pdo->prepare($sql)->execute([$username, $email, $full_name, $role, $id]);
        }

        echo "<script>alert('Данные обновлены!'); window.location='users.php';</script>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Ошибка: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="d-flex align-items-center mb-4">
    <a href="users.php" class="btn btn-outline-dark rounded-0 me-3"><i class="bi bi-arrow-left"></i></a>
    <h2 class="fw-black text-uppercase m-0">Редактирование: @<?= htmlspecialchars($user['username']) ?></h2>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card admin-card p-4">
            <form method="POST">
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">LOGIN (НИКНЕЙМ)</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">EMAIL</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">ФИО</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="fw-bold small text-muted mb-1">РОЛЬ</label>
                    <select name="role" class="form-select" <?= ($id == $_SESSION['user_id']) ? 'disabled' : '' ?>>
                        <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>Пользователь</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Администратор</option>
                        <option value="super_admin" <?= $user['role'] == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        <option value="banned" <?= $user['role'] == 'banned' ? 'selected' : '' ?>>ЗАБАНЕН</option>
                    </select>
                    <?php if($id == $_SESSION['user_id']): ?>
                        <div class="form-text text-danger fw-bold">Вы не можете изменить роль самому себе здесь.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4 border-top border-2 border-dark pt-3">
                    <label class="fw-bold small text-danger mb-1">СМЕНИТЬ ПАРОЛЬ</label>
                    <input type="text" name="new_password" class="form-control border-danger" placeholder="Оставьте пустым, если не меняете">
                    <div class="form-text">Введите новый пароль, если пользователь забыл старый.</div>
                </div>

                <button type="submit" class="btn btn-black btn-admin w-100 py-3 text-warning">СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
            </form>
        </div>
    </div>
    
    <!-- Инфо блок -->
    <div class="col-lg-6">
        <div class="card admin-card p-4 bg-light">
            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3">Статистика</h5>
            <p><strong>Дата регистрации:</strong> <?= $user['created_at'] ?></p>
            <p><strong>ID:</strong> #<?= $user['id'] ?></p>
            
            <?php if(!empty($user['avatar'])): ?>
                <hr>
                <p class="fw-bold mb-2">Аватар:</p>
                <img src="/<?= $user['avatar'] ?>" class="img-thumbnail rounded-0 border-dark" style="max-width: 150px;">
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
