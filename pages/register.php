<?php
// pages/register.php
session_start();
require_once __DIR__ . '/../includes/header.php';

// Если пользователь уже авторизован — редирект
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/pages/profile.php';</script>";
    exit;
}

// Получаем ошибки из сессии
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>

<style>
    /* === ФОН NEON GRID === */
    body { background: #120024; color: #fff; }
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

    /* === AUTH CARD === */
    .auth-container {
        min-height: 85vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative; z-index: 2;
    }
    .auth-card {
        background: #fff;
        border: 4px solid #000;
        box-shadow: 15px 15px 0 #bc13fe; /* Неоновая тень */
        width: 100%;
        max-width: 480px;
        position: relative;
    }
    .auth-header {
        background: #fff;
        color: #fff;
        padding-top: 30px;
        text-align: center;
        font-family: 'Arial Black', sans-serif;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 1.5rem;
    }
    .auth-header span { color: #FCE300; text-shadow: 2px 2px 0 #bc13fe; }
    
    .street-label {
        font-weight: 900;
        text-transform: uppercase;
        font-size: 0.75rem;
        margin-bottom: 5px;
        display: block;
        letter-spacing: 1px;
        color: #000;
    }
    .street-input {
        border: 3px solid #000;
        border-radius: 0;
        padding: 12px 12px;
        font-weight: bold;
        font-size: 1rem;
        transition: all 0.2s;
        background: #f8f9fa;
        color: #000;
    }
    .street-input:focus {
        background-color: #fff;
        box-shadow: 5px 5px 0 #FCE300;
        border-color: #000;
        outline: none;
        transform: translate(-2px, -2px);
    }
    .street-btn {
        background: #FCE300;
        border: 3px solid #000;
        color: #000;
        font-weight: 900;
        text-transform: uppercase;
        padding: 15px;
        width: 100%;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 5px 5px 0 #000;
        margin-top: 10px;
    }
    .street-btn:hover {
        transform: translate(-2px, -2px);
        box-shadow: 8px 8px 0 #000;
        background: #ffe600;
    }
    .street-btn:active {
        transform: translate(2px, 2px);
        box-shadow: 1px 1px 0 #000;
    }
    .street-error {
        color: #fff;
        background: #ff0000;
        font-weight: bold;
        font-size: 0.75rem;
        margin-top: 4px;
        padding: 4px 8px;
        display: inline-block;
        border: 2px solid #000;
        box-shadow: 3px 3px 0 #000;
    }
        .tape-strip {
        position: absolute; top: -15px; left: 50%; transform: translateX(-50%) rotate(-2deg);
        width: 140px; height: 40px;
        background: rgba(255, 255, 255, 0.4);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 10;
        border-left: 2px dotted rgba(0,0,0,0.2);
        border-right: 2px dotted rgba(0,0,0,0.2);
        background-color: #FCE300;
        opacity: 0.9;
    }
    /* Анимация появления */
    .fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
        transform: translateY(30px);
    }
    @keyframes fadeInUp {
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="auth-container">
    <div class="auth-card fade-in-up">
        
            <div class="tape-strip"></div>

        <!-- ЗАГОЛОВОК -->
        <div class="auth-header">
            <span>НОВЫЙ ИГРОК</span>
        </div>

        <div class="p-4 p-md-5">
            
            <form method="POST" action="/actions/signup.php" novalidate>
                
                <!-- ИМЯ     -->
                <div class="mb-3">
                    <label class="street-label">Никнейм</label>
                    <input type="text" name="username" 
                           class="form-control street-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                           placeholder="Придумай имя"
                           value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                    
                    <?php if (isset($errors['username'])): ?>
                        <div class="street-error"><?= $errors['username'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- EMAIL -->
                <div class="mb-3">
                    <label class="street-label">Email</label>
                    <input type="email" name="email" 
                           class="form-control street-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                           placeholder="name@example.com"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    
                    <?php if (isset($errors['email'])): ?>
                        <div class="street-error"><?= $errors['email'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- ПАРОЛЬ -->
                <div class="mb-3">
                    <label class="street-label">Пароль</label>
                    <input type="password" name="password" 
                           class="form-control street-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Минимум 6 символов">
                    
                    <?php if (isset($errors['password'])): ?>
                        <div class="street-error"><?= $errors['password'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- ПОВТОР ПАРОЛЯ -->
                <div class="mb-4">
                    <label class="street-label">Подтверждение</label>
                    <input type="password" name="password_confirm" 
                           class="form-control street-input <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                           placeholder="Повтори пароль">
                    
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="street-error"><?= $errors['password_confirm'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- КНОПКА -->
                <button type="submit" class="street-btn">
                    ЗАРЕГИСТРИРОВАТЬСЯ
                </button>
                
                <!-- ССЫЛКА НА ВХОД -->
                <div class="text-center mt-4 pt-3 border-top border-dark">
                    <span class="small fw-bold text-muted text-uppercase">Уже в игре?</span><br>
                    <a href="/pages/login.php" class="text-decoration-none fw-black text-dark fs-5" style="border-bottom: 3px solid #FCE300; box-shadow: inset 0 -5px 0 rgba(252, 227, 0, 0.3);">
                        ВОЙТИ В АККАУНТ
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
