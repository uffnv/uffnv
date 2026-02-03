<?php
// pages/login.php
session_start();
require_once __DIR__ . '/../includes/header.php';
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

    /* === КАРТОЧКА ВХОДА === */
    .login-card {
        background: #fff;
        color: #000;
        border: 4px solid #000;
        box-shadow: 10px 10px 0 #000;
        transition: box-shadow 0.3s ease;
        max-width: 500px;
        margin: 0 auto;
    }
    .login-card:hover {
        box-shadow: 12px 12px 0 #bc13fe;
    }

    /* Инпуты */
    .form-control {
        border: 3px solid #000;
        border-radius: 0;
        font-weight: bold;
        padding: 15px;
    }
    .form-control:focus {
        box-shadow: 5px 5px 0 #bc13fe;
        border-color: #000;
        outline: none;
    }
    
    /* Кнопка "Войти" */
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

    /* Кнопка "Создать аккаунт" */
    .btn-register {
        background: transparent;
        color: #000;
        border: 3px solid #000;
        font-family: 'Arial Black', sans-serif;
        text-transform: uppercase;
        transition: all 0.2s;
    }
    .btn-register:hover {
        background: #000;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
    }
    
    /* Декоративный "Скотч" */
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
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2;">
    
    <div class="login-card p-5 position-relative w-100">
        <!-- Скотч -->
        <div class="tape-strip"></div>

        <div class="text-center mb-5 mt-2">
            <h1 class="display-4 street-font m-0 text-dark">ВХОД</h1>
            <p class="text-muted fw-bold text-uppercase mt-2 street-font" style="letter-spacing: 1px;">
                Welcome Back
            </p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger rounded-0 border-3 border-dark fw-bold mb-4 shadow-sm text-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success rounded-0 border-3 border-dark fw-bold mb-4 shadow-sm text-center">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="/actions/signin.php" method="POST">
            <div class="mb-4">
                <label class="street-font small text-muted mb-2">Твой Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-black text-white rounded-0 border-3 border-dark border-end-0">
                        <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input type="email" name="email" class="form-control form-control-lg rounded-0" placeholder="mail@example.com" required>
                </div>
            </div>

            <div class="mb-5">
                <label class="street-font small text-muted mb-2">Пароль</label>
                <div class="input-group">
                    <span class="input-group-text bg-black text-white rounded-0 border-3 border-dark border-end-0">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" name="password" class="form-control form-control-lg rounded-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="street-btn">
                ВОЙТИ В СИСТЕМУ
            </button>
            
                <div class="text-center mt-4 pt-3 border-top border-dark">
                    <span class="small fw-bold text-muted text-uppercase">Ещё нет аккаунта?</span><br>
                    <a href="/pages/register.php" class="text-decoration-none fw-black text-dark fs-5" style="border-bottom: 3px solid #FCE300; box-shadow: inset 0 -5px 0 rgba(252, 227, 0, 0.3);">
                        СОЗДАТЬ АККАУНТ
                    </a>
                </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
