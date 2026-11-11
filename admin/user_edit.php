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

$allowedRoles = isSuperAdmin() ? ['user', 'admin', 'super_admin'] : ['user'];
$hasTopicBanCol = hasUserBansTopicColumn($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selfUrl = '/admin/user_edit.php?id=' . $id;
    requireCsrfOrRedirect($selfUrl);

    // 1. Выдача форумного бана (общий по форуму или в конкретной теме)
    if (isset($_POST['issue_ban'])) {
        if ($id === $actorId) {
            redirectWithFlash($selfUrl, 'Нельзя выдать бан самому себе.', 'warning');
        }
        $banTopicId = !empty($_POST['ban_topic_id']) ? (int)$_POST['ban_topic_id'] : null;
        if ($banTopicId !== null && !$hasTopicBanCol) {
            redirectWithFlash($selfUrl, 'Для темового бана сначала выполните SQL-миграцию (колонка topic_id в user_bans).', 'warning');
        }
        if ($banTopicId !== null) {
            $tChk = $pdo->prepare("SELECT id FROM topics WHERE id = ? LIMIT 1");
            $tChk->execute([$banTopicId]);
            if (!$tChk->fetch()) {
                redirectWithFlash($selfUrl, 'Указанная тема для бана не найдена.', 'danger');
            }
        }

        $unit = $_POST['duration_unit'] ?? 'hours';
        $val  = max(1, min(365, (int)($_POST['duration_val'] ?? 24)));
        $expiresAt = null;
        if ($unit === 'hours') {
            $expiresAt = date('Y-m-d H:i:s', time() + $val * 3600);
        } elseif ($unit === 'days') {
            $expiresAt = date('Y-m-d H:i:s', time() + $val * 86400);
        } elseif ($unit === 'months') {
            $expiresAt = date('Y-m-d H:i:s', time() + $val * 2592000);
        } elseif ($unit !== 'perm') {
            redirectWithFlash($selfUrl, 'Некорректная единица длительности бана.', 'danger');
        }

        $reason = mb_substr(trim((string)($_POST['ban_reason'] ?? '')), 0, 255, 'UTF-8');
        if ($reason === '') {
            $reason = 'Нарушение правил форума';
        }

        if ($hasTopicBanCol) {
            $pdo->prepare("INSERT INTO user_bans (user_id, topic_id, reason, banned_by_id, expires_at) VALUES (?, ?, ?, ?, ?)")
                ->execute([$id, $banTopicId, $reason, $actorId, $expiresAt]);
        } else {
            $pdo->prepare("INSERT INTO user_bans (user_id, reason, banned_by_id, expires_at) VALUES (?, ?, ?, ?)")
                ->execute([$id, $reason, $actorId, $expiresAt]);
        }
        redirectWithFlash($selfUrl, 'Форумный бан выдан (покупки в магазине остаются доступны).', 'warning');
    }

    // 2. Снятие конкретного бана
    if (isset($_POST['remove_ban_id'])) {
        $banId = (int)$_POST['remove_ban_id'];
        if ($banId > 0) {
            $pdo->prepare("UPDATE user_bans SET expires_at = NOW() WHERE id = ? AND user_id = ?")
                ->execute([$banId, $id]);
        }
        redirectWithFlash($selfUrl, 'Ограничение снято.', 'success');
    }

    // 3. Снятие всех банов пользователя на форуме
    if (isset($_POST['clear_all_bans'])) {
        $pdo->prepare("UPDATE user_bans SET expires_at = NOW() WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())")
            ->execute([$id]);
        if (($user['role'] ?? '') === 'banned') {
            $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'banned'")->execute([$id]);
        }
        redirectWithFlash($selfUrl, 'Все форумные баны пользователя сняты.', 'success');
    }

    // 4. Обновление профиля, роли и пароля
    $username  = trim((string)($_POST['username'] ?? ''));
    $emailRaw  = trim((string)($_POST['email'] ?? ''));
    $full_name = mb_substr(trim((string)($_POST['full_name'] ?? '')), 0, 100, 'UTF-8');
    $new_pass  = (string)($_POST['new_password'] ?? '');

    $emailCheck = validateEmailStrict($emailRaw);
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ0-9_.\-]{3,30}$/u', $username)) {
        redirectWithFlash($selfUrl, 'Никнейм должен содержать 3–30 символов (буквы, цифры, _ . - без пробелов).', 'danger');
    }
    if (!$emailCheck['valid']) {
        redirectWithFlash($selfUrl, $emailCheck['error'], 'danger');
    }
    $email = $emailCheck['value'];

    $role = $user['role'];
    if ($id !== $actorId && isset($_POST['role']) && in_array($_POST['role'], $allowedRoles, true)) {
        $role = $_POST['role'];
    }

    try {
        if ($new_pass !== '') {
            $passCheck = validatePasswordRegistration($new_pass);
            if (!$passCheck['valid']) {
                redirectWithFlash($selfUrl, $passCheck['error'], 'danger');
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
        redirectWithFlash($selfUrl, 'Ошибка обновления (возможно, ник или email уже занят).', 'danger');
    }
}

require_once __DIR__ . '/layout_header.php';
$avatarSrc = getAvatarUrl($user['avatar'] ?? '');

if ($hasTopicBanCol) {
    $bansStmt = $pdo->prepare("
        SELECT b.*, t.title AS topic_title, adm.username AS banned_by_name
        FROM user_bans b
        LEFT JOIN topics t ON b.topic_id = t.id
        LEFT JOIN users adm ON b.banned_by_id = adm.id
        WHERE b.user_id = ? AND (b.expires_at IS NULL OR b.expires_at > NOW())
        ORDER BY b.banned_at DESC
    ");
} else {
    $bansStmt = $pdo->prepare("
        SELECT b.*, adm.username AS banned_by_name
        FROM user_bans b
        LEFT JOIN users adm ON b.banned_by_id = adm.id
        WHERE b.user_id = ? AND (b.expires_at IS NULL OR b.expires_at > NOW())
        ORDER BY b.banned_at DESC
    ");
}
$bansStmt->execute([$id]);
$activeBans = $bansStmt->fetchAll();

$topicsForBan = $hasTopicBanCol
    ? $pdo->query("SELECT id, title FROM topics ORDER BY id DESC LIMIT 100")->fetchAll()
    : [];
?>

<div class="d-flex align-items-center mb-4">
    <a href="/admin/users.php" class="btn btn-outline-dark rounded-0 me-3" aria-label="Назад"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    <h2 class="fw-black text-uppercase m-0">Редактирование: @<?= e($user['username']) ?></h2>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card admin-card p-4">
            <form method="POST" data-validate-form data-prevent-double-submit="1" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label for="editUsername" class="fw-bold small text-muted mb-1">LOGIN (НИКНЕЙМ)</label>
                    <input id="editUsername" type="text" name="username" class="form-control" value="<?= e($user['username']) ?>" required minlength="3" maxlength="30" data-rule="username">
                    <div class="auth-field-error"></div>
                </div>

                <div class="mb-3">
                    <label for="editEmail" class="fw-bold small text-muted mb-1">EMAIL</label>
                    <input id="editEmail" type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required maxlength="100" data-rule="email">
                    <div class="auth-field-error"></div>
                </div>

                <div class="mb-3">
                    <label for="editFullName" class="fw-bold small text-muted mb-1">ФИО</label>
                    <input id="editFullName" type="text" name="full_name" class="form-control" maxlength="100" value="<?= e($user['full_name'] ?? '') ?>">
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
                    <div class="input-group">
                        <input id="editPass" type="password" name="new_password" class="form-control border-danger" placeholder="Оставьте пустым, если не меняете" minlength="6" maxlength="64" data-rule="password-optional">
                        <button type="button" class="btn btn-outline-danger rounded-0 toggle-password-btn" data-target="editPass" aria-label="Показать или скрыть пароль"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    </div>
                    <div class="form-text">От 6 до 64 символов: латинская буква + цифра, без пробелов.</div>
                    <div class="auth-field-error"></div>
                </div>

                <button type="submit" class="btn btn-dark btn-admin w-100 py-3 text-warning">СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card admin-card p-4 bg-light mb-4">
            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3">Статистика</h5>
            <p class="mb-1"><strong>ID:</strong> #<?= (int)$user['id'] ?></p>
            <p class="mb-0"><strong>Дата регистрации:</strong> <?= e($user['created_at']) ?></p>

            <?php if ($avatarSrc): ?>
                <hr>
                <p class="fw-bold mb-2">Аватар:</p>
                <img src="<?= e($avatarSrc) ?>" alt="<?= e($user['username']) ?>" class="img-thumbnail rounded-0 border-dark" style="max-width: 120px;">
            <?php endif; ?>
        </div>

        <div class="card admin-card p-4">
            <div class="d-flex justify-content-between align-items-center border-bottom border-dark pb-2 mb-3">
                <h5 class="fw-bold m-0 text-uppercase">Баны форума</h5>
                <?php if (!empty($activeBans)): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Снять все активные баны пользователя?');">
                        <?= csrfField() ?>
                        <button type="submit" name="clear_all_bans" value="1" class="btn btn-sm btn-success rounded-0 fw-bold">Снять все</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="small text-muted mb-3">
                Ограничения действуют только на публикации и ответы на форуме; магазин и заказы остаются доступны.
            </div>

            <?php if (!empty($activeBans)): ?>
                <div class="list-group mb-4 rounded-0">
                    <?php foreach ($activeBans as $ab): ?>
                        <div class="list-group-item border-2 border-dark rounded-0 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <?php if (!empty($ab['topic_id'])): ?>
                                        <span class="badge bg-warning text-dark rounded-0 mb-1">ТЕМА #<?= (int)$ab['topic_id'] ?></span>
                                        <div class="small fw-bold"><?= e($ab['topic_title'] ?? 'Удалённая тема') ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-0 mb-1">ВЕСЬ ФОРУМ</span>
                                    <?php endif; ?>
                                    <div class="small mt-1"><strong>Причина:</strong> <?= e($ab['reason'] ?? 'Без причины') ?></div>
                                    <div class="small text-muted">
                                        До: <?= !empty($ab['expires_at']) ? e(date('d.m.Y H:i', strtotime($ab['expires_at']))) : 'Навсегда' ?>
                                        <?php if (!empty($ab['banned_by_name'])): ?> • Выдал: @<?= e($ab['banned_by_name']) ?><?php endif; ?>
                                    </div>
                                </div>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="remove_ban_id" value="<?= (int)$ab['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success rounded-0 fw-bold">Снять</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-success border-2 border-dark rounded-0 py-2 small fw-bold mb-4">
                    Активных форумных банов нет.
                </div>
            <?php endif; ?>

            <?php if ($id !== $actorId): ?>
                <form method="POST" data-validate-form data-prevent-double-submit="1" novalidate class="border-top border-2 border-dark pt-3">
                    <?= csrfField() ?>
                    <h6 class="fw-bold text-uppercase mb-3">Выдать бан на форуме</h6>

                    <?php if ($hasTopicBanCol): ?>
                        <div class="mb-2">
                            <label for="banTopicId" class="small fw-bold text-muted">Область</label>
                            <select id="banTopicId" name="ban_topic_id" class="form-select form-select-sm">
                                <option value="">Весь форум (все темы и посты)</option>
                                <?php foreach ($topicsForBan as $bt): ?>
                                    <option value="<?= (int)$bt['id'] ?>">#<?= (int)$bt['id'] ?> — <?= e(mb_substr($bt['title'], 0, 50, 'UTF-8')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="row g-2 mb-2">
                        <div class="col-5">
                            <label for="banDurVal" class="small fw-bold text-muted">Срок</label>
                            <input id="banDurVal" type="number" name="duration_val" class="form-control form-control-sm" value="24" min="1" max="365">
                        </div>
                        <div class="col-7">
                            <label for="banDurUnit" class="small fw-bold text-muted">Единица</label>
                            <select id="banDurUnit" name="duration_unit" class="form-select form-select-sm">
                                <option value="hours" selected>Часов</option>
                                <option value="days">Дней</option>
                                <option value="months">Месяцев</option>
                                <option value="perm">Навсегда (форум)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="banReason" class="small fw-bold text-muted">Причина</label>
                        <input id="banReason" type="text" name="ban_reason" class="form-control form-control-sm" maxlength="255" placeholder="Нарушение правил форума" required data-rule="required-text" data-min="3" data-max="255">
                        <div class="auth-field-error"></div>
                    </div>

                    <button type="submit" name="issue_ban" value="1" class="btn btn-sm btn-admin btn-admin-danger w-100">Выдать бан форума</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
