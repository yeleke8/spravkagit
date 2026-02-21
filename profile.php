<?php
require_once 'headers.php';

// Проверяем авторизацию
$user = authenticate($pdo);
$user_id = $user['user_id'];

// --- ОБНОВЛЕНИЕ ПРОФИЛЯ И СМЕНА ПАРОЛЯ (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... [Остается как было, без изменений, код смены пароля и профиля] ...
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'update_profile'; 
    
    if ($action === 'change_password') {
        $old_password = $input['old_password'] ?? '';
        $new_password = $input['new_password'] ?? '';

        if (empty($old_password) || empty($new_password)) response(false, 'Введите текущий и новый пароль');
        if (mb_strlen($new_password) < 6) response(false, 'Новый пароль должен содержать минимум 6 символов');

        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();

            if (!password_verify($old_password, $current_hash)) response(false, 'Неверный текущий пароль');

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ?, refresh_token = NULL WHERE user_id = ?")->execute([$new_hash, $user_id]); // Сбрасываем сессии на других устройствах

            response(true, 'Пароль успешно изменен');
        } catch (Exception $e) {
            response(false, 'Ошибка смены пароля: ' . $e->getMessage());
        }
        
    } else {
        $name = isset($input['name']) ? trim($input['name']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        
        if ($name && $phone) {
            try {
                $pdo->prepare("UPDATE users SET user_name = ?, user_phone = ? WHERE user_id = ?")->execute([$name, $phone, $user_id]);
                response(true, 'Профиль обновлен');
            } catch (Exception $e) {
                response(false, 'Ошибка обновления: ' . $e->getMessage());
            }
        } else {
            response(false, 'Заполните обязательные поля');
        }
    }
}

// --- ПОЛУЧЕНИЕ ДАННЫХ И АНАЛИТИКИ С ПАГИНАЦИЕЙ (GET) ---
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // 1. Основные данные пользователя (отдаем всегда)
        $stmt = $pdo->prepare("SELECT user_id, login, user_name, user_phone, user_type, registereddate, lastonline FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) response(false, 'Пользователь не найден');

        // 2. Статистика
        $sqlStats = "SELECT 
                        (SELECT COUNT(*) FROM comments WHERE user_id = ?) AS total_reviews,
                        (SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM comments WHERE user_id = ?) AS avg_rating_given,
                        (SELECT COUNT(*) FROM s_favorites WHERE user_id = ?) AS total_favorites,
                        (SELECT COUNT(*) FROM log_post_views WHERE user_id = ?) AS total_places_viewed,
                        DATEDIFF(NOW(), registereddate) AS days_with_us
                     FROM users WHERE user_id = ?";
        $stmtStats = $pdo->prepare($sqlStats);
        $stmtStats->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        $userStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // 3. Мои отзывы (С пагинацией)
        $totalReviews = (int)$userStats['total_reviews'];
        $totalPages = ceil($totalReviews / $limit);

        $sqlReviews = "SELECT c.comment_id, c.rating, c.comment, c.is_approved, c.created_at, c.owner_reply, c.reply_created_at,
                              p.post_id, p.title, p.photo 
                       FROM comments c 
                       JOIN post p ON c.post_id = p.post_id 
                       WHERE c.user_id = ? 
                       ORDER BY c.created_at DESC 
                       LIMIT $limit OFFSET $offset";
                       
        $stmtReviews = $pdo->prepare($sqlReviews);
        $stmtReviews->execute([$user_id]);
        $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reviews as &$rev) {
            $rev['photo'] = (strpos($rev['photo'], 'http') === 0) ? $rev['photo'] : $baseUrl . '/' . $rev['photo'];
            $rev['status_text'] = ($rev['is_approved'] == 1) ? 'Опубликован' : 'На модерации';
        }

        // Собираем ответ
        $response = [
            'profile' => $userData,
            'analytics' => [
                'total_reviews' => $totalReviews,
                'avg_rating_given' => (float)$userStats['avg_rating_given'],
                'total_favorites' => (int)$userStats['total_favorites'],
                'total_places_viewed' => (int)$userStats['total_places_viewed'],
                'days_with_us' => (int)$userStats['days_with_us']
            ],
            'recent_reviews' => $reviews,
            'pagination' => [
                'total_items' => $totalReviews,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'has_more' => $page < $totalPages
            ]
        ];

        response(true, 'Данные профиля', $response);

    } catch (Exception $e) {
        response(false, $e->getMessage());
    }
}
?>