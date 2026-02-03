<?php
// personal/recommender.php

/**
 * Простая функция для получения рекомендаций
 * Не требует создания классов, работает напрямую с PDO
 */
function getUserRecommendations($pdo, $userId) {
    
    // 1. ОПРЕДЕЛЯЕМ ИНТЕРЕСЫ (категории, где юзер активен)
    // Вес: Создал тему = 3, Ответил = 1
    $sqlInterests = "
        SELECT category_id, SUM(score) as interest_score 
        FROM (
            SELECT category_id, 3 as score FROM topics WHERE user_id = :uid1
            UNION ALL
            SELECT t.category_id, 1 as score 
            FROM posts p 
            JOIN topics t ON p.topic_id = t.id 
            WHERE p.user_id = :uid2
        ) as activity
        GROUP BY category_id 
        ORDER BY interest_score DESC 
        LIMIT 3
    ";
    
    $stmt = $pdo->prepare($sqlInterests);
    $stmt->execute(['uid1' => $userId, 'uid2' => $userId]);
    $favoriteCats = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $data = [
        'topics' => [],
        'products' => [],
        'materials' => []
    ];

    // 2. ЕСЛИ ЕСТЬ ИНТЕРЕСЫ -> ИЩЕМ ПОХОЖЕЕ
    if (!empty($favoriteCats)) {
        $inQuery = implode(',', array_fill(0, count($favoriteCats), '?'));
        
        // Темы из любимых категорий (чужие)
        $sqlTopics = "SELECT * FROM topics 
                      WHERE category_id IN ($inQuery) 
                      AND user_id != ? 
                      ORDER BY created_at DESC LIMIT 6";
        
        $params = array_merge($favoriteCats, [$userId]);
        $stmtT = $pdo->prepare($sqlTopics);
        $stmtT->execute($params);
        $data['topics'] = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        // Товары (пока просто случайные, т.к. привязки к категориям может не быть)
        // Если хотите привязать, добавьте WHERE category_id...
        $data['products'] = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

        // Материалы
        try {
            $data['materials'] = $pdo->query("SELECT * FROM learning_materials ORDER BY RAND() LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* Игнорируем, если таблицы нет */ }
    } 
    
    // 3. ЕСЛИ ИНТЕРЕСОВ НЕТ (или мало рекомендаций) -> ДОБИВАЕМ ПОПУЛЯРНЫМ
    if (empty($data['topics'])) {
        $data['topics'] = $pdo->query("SELECT * FROM topics ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    }
    if (empty($data['products'])) {
        $data['products'] = $pdo->query("SELECT * FROM products ORDER BY price DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
    }
    if (empty($data['materials'])) {
        try {
            $data['materials'] = $pdo->query("SELECT * FROM learning_materials LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }

    return $data;
}
?>
