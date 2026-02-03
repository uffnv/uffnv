<?php
/**
 * Шаблон карточки темы для списка форума
 * Ожидает переменную $topic (массив данных темы)
 */
?>
<div class="card street-card border-3 border-dark rounded-0 shadow-sm h-100 position-relative transition-hover mb-3">
    <div class="card-body d-flex align-items-center justify-content-between p-3 p-md-4">
        
        <!-- Левая часть: Инфо -->
        <div style="flex: 1;">
            <div class="mb-2">
                <span class="badge bg-black text-warning rounded-0 border border-dark fw-bold">
                    <?= htmlspecialchars($topic['cat_title']) ?>
                </span>
                <span class="text-muted fw-bold small ms-2 text-uppercase">
                    <i class="bi bi-clock"></i> <?= date('d.m H:i', strtotime($topic['created_at'])) ?>
                </span>
            </div>
            
            <!-- Заголовок (Ссылка на всю карточку) -->
            <h4 class="fw-black text-uppercase m-0" style="line-height: 1.2;">
                <a href="/pages/topic.php?id=<?= $topic['id'] ?>" class="text-dark text-decoration-none stretched-link">
                    <?= htmlspecialchars($topic['title']) ?>
                </a>
            </h4>
            
            <div class="d-flex align-items-center mt-2">
                <img src="<?= $topic['avatar'] ? '/'.$topic['avatar'] : 'https://via.placeholder.com/40' ?>" 
                        class="rounded-circle border border-dark me-2" width="24" height="24" style="object-fit: cover;">
                <span class="fw-bold small">@<?= htmlspecialchars($topic['username']) ?></span>
            </div>
        </div>

        <!-- Правая часть: Статистика -->
        <div class="text-center bg-light border-2 border-dark p-2 ms-3 d-none d-sm-block align-self-stretch d-flex flex-column justify-content-center" style="min-width: 80px;">
            <div class="fw-black fs-4 lh-1"><?= $topic['replies_count'] ?></div>
            <div class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Сообщений</div>
        </div>

    </div>
</div>
