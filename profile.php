<?php
require_once 'headers.php';

// Проверяем авторизацию
$user = authenticate($pdo);
$user_id = $user['user_id'];

// --- ОБНОВЛЕНИЕ ПРОФИЛЯ И СМЕНА ПАРОЛЯ (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Определяем действие: 'update_profile' (по умолчанию) или 'change_password'
    $action = $input['action'] ?? 'update_profile'; 
    
    if ($action === 'change_password') {
        $old_password = $input['old_password'] ?? '';
        $new_password = $input['new_password'] ?? '';

        if (empty($old_password) || empty($new_password)) {
            response(false, 'Введите текущий и новый пароль');
        }
        if (mb_strlen($new_password) < 6) {
            response(false, 'Новый пароль должен содержать минимум 6 символов');
        }

        try {
            // Получаем текущий хэш пароля из БД
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();

            // Сравниваем старый пароль с хэшем
            if (!password_verify($old_password, $current_hash)) {
                response(false, 'Неверный текущий пароль');
            }

            // Генерируем новый хэш и сохраняем
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$new_hash, $user_id]);

            response(true, 'Пароль успешно изменен');
        } catch (Exception $e) {
            response(false, 'Ошибка смены пароля: ' . $e->getMessage());
        }
        
    } else {
        // Стандартное обновление данных (имя, телефон)
        $name = isset($input['name']) ? trim($input['name']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        
        if ($name && $phone) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET user_name = ?, user_phone = ? WHERE user_id = ?");
                $stmt->execute([$name, $phone, $user_id]);
                
                response(true, 'Профиль обновлен');
            } catch (Exception $e) {
                response(false, 'Ошибка обновления: ' . $e->getMessage());
            }
        } else {
            response(false, 'Заполните обязательные поля');
        }
    }
}

// --- ПОЛУЧЕНИЕ ДАННЫХ И АНАЛИТИКИ (GET) ---
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 1. Основные данные пользователя
        $stmt = $pdo->prepare("SELECT user_id, login, user_name, user_phone, user_type, registereddate, lastonline FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) response(false, 'Пользователь не найден');

        // 2. Расширенная аналитика пользователя (Статистика)
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

        // 3. Мои отзывы (ограничим до 20 последних для профиля)
        $sqlReviews = "SELECT c.comment_id, c.rating, c.comment, c.is_approved, c.created_at, c.owner_reply, c.reply_created_at,
                              p.post_id, p.title, p.photo 
                       FROM comments c 
                       JOIN post p ON c.post_id = p.post_id 
                       WHERE c.user_id = ? 
                       ORDER BY c.created_at DESC 
                       LIMIT 20";
                       
        $stmtReviews = $pdo->prepare($sqlReviews);
        $stmtReviews->execute([$user_id]);
        $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

        // Форматируем фото заведений в отзывах
        foreach ($reviews as &$rev) {
            $rev['photo'] = (strpos($rev['photo'], 'http') === 0) ? $rev['photo'] : $baseUrl . '/' . $rev['photo'];
            $rev['status_text'] = ($rev['is_approved'] == 1) ? 'Опубликован' : 'На модерации';
        }

        // Собираем ответ
        $response = [
            'profile' => $userData,
            'analytics' => [
                'total_reviews' => (int)$userStats['total_reviews'],
                'avg_rating_given' => (float)$userStats['avg_rating_given'],
                'total_favorites' => (int)$userStats['total_favorites'],
                'total_places_viewed' => (int)$userStats['total_places_viewed'],
                'days_with_us' => (int)$userStats['days_with_us']
            ],
            'recent_reviews' => $reviews
        ];

        response(true, 'Данные профиля', $response);

    } catch (Exception $e) {
        response(false, $e->getMessage());
    }
}
?>