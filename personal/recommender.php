<?php
// personal/recommender.php

function getUserRecommendations($pdo, $userId) {
    // 1. ОПРЕДЕЛЯЕМ ТОП КАТЕГОРИЙ (с названиями)
    $sqlInterests = "
        SELECT c.title, activity.category_id, SUM(score) as interest_score 
        FROM (
            SELECT category_id, 3 as score FROM topics WHERE user_id = :uid1
            UNION ALL
            SELECT t.category_id, 1 as score 
            FROM posts p 
            JOIN topics t ON p.topic_id = t.id 
            WHERE p.user_id = :uid2
        ) as activity
        JOIN categories c ON activity.category_id = c.id
        GROUP BY activity.category_id 
        ORDER BY interest_score DESC 
        LIMIT 3
    ";
    
    $stmt = $pdo->prepare($sqlInterests);
    $stmt->execute(['uid1' => $userId, 'uid2' => $userId]);
    $interests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [
        'topics' => [],
        'products' => [],
        'external_intel' => [] // Новый блок вместо материалов
    ];

    // База данных внешних ресурсов (маппинг по ключевым словам в названиях категорий)
    $externalLibrary = [
        'street' => [
            ['title' => 'StreetArtNews', 'url' => 'https://streetartnews.net/', 'desc' => 'Главный портал о мировом стрит-арте и муралах.'],
            ['title' => 'Graffiti Archive', 'url' => 'https://www.fatcap.com/', 'desc' => 'Огромная база данных граффити-художников со всего мира.']
        ],
        'tech' => [
            ['title' => 'The Verge: Tech', 'url' => 'https://www.theverge.com/tech', 'desc' => 'Свежие новости технологий и гаджетов.'],
            ['title' => 'Hacker News', 'url' => 'https://news.ycombinator.com/', 'desc' => 'Главный агрегатор новостей для разработчиков и гиков.']
        ],
        'design' => [
            ['title' => 'Behance: Street Style', 'url' => 'https://www.behance.net/search/projects?search=street%20style', 'desc' => 'Вдохновение и визуальные тренды уличной моды.'],
            ['title' => 'TouchDesigner Wiki', 'url' => 'https://derivative.ca/wiki099/index.php?title=Main_Page', 'desc' => 'Техническая документация для визуального программирования.']
        ],
        'default' => [
            ['title' => 'Pinterest Trends', 'url' => 'https://trends.pinterest.com/', 'desc' => 'Анализ того, что сейчас популярно в визуальной культуре.'],
            ['title' => 'Vogue: Street Style', 'url' => 'https://www.vogue.com/vogue-runway/street-style', 'desc' => 'Мировые тренды уличной моды и стайл-инфо.']
        ]
    ];

    if (!empty($interests)) {
        // Подбор тем на форуме
        $catIds = array_column($interests, 'category_id');
        $inQuery = implode(',', array_fill(0, count($catIds), '?'));
        
        $sqlTopics = "SELECT * FROM topics WHERE category_id IN ($inQuery) AND user_id != ? ORDER BY created_at DESC LIMIT 6";
        $stmtT = $pdo->prepare($sqlTopics);
        $stmtT->execute(array_merge($catIds, [$userId]));
        $data['topics'] = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        // Формирование внешнего интеллекта
        foreach ($interests as $interest) {
            $foundMatch = false;
            $catTitle = mb_strtolower($interest['title']);
            
            foreach ($externalLibrary as $keyword => $links) {
                if (strpos($catTitle, $keyword) !== false) {
                    $data['external_intel'] = array_merge($data['external_intel'], $links);
                    $foundMatch = true;
                    break;
                }
            }
        }
    }

    // Добиваем дефолтными ссылками, если ничего не нашли
    if (empty($data['external_intel'])) {
        $data['external_intel'] = $externalLibrary['default'];
    }

    // Лимитируем до 4 ссылок
    $data['external_intel'] = array_slice(array_unique($data['external_intel'], SORT_REGULAR), 0, 4);

    // Товары
    $data['products'] = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}
