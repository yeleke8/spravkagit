<?php
require_once 'headers.php';

// Проверяем авторизацию
$user = authenticate($pdo);
$user_id = $user['user_id'];

// --- ОБНОВЛЕНИЕ ПРОФИЛЯ (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = isset($input['name']) ? trim($input['name']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    
    // Если пришел пароль, можно добавить логику смены пароля здесь
    
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

// --- ПОЛУЧЕНИЕ ДАННЫХ (GET) ---
else {
    try {
        // 1. Данные пользователя
        // Пароль и api_key не отдаем в целях безопасности
        $stmt = $pdo->prepare("SELECT user_id, login, user_name, user_phone, user_type, registereddate FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch();

        if (!$userData) response(false, 'User not found');

        // 2. Мои отзывы
        $sqlReviews = "SELECT c.comment_id, c.rating, c.comment, c.is_approved, c.created_at, c.owner_reply, 
                              p.post_id, p.title, p.photo 
                       FROM comments c 
                       JOIN post p ON c.post_id = p.post_id 
                       WHERE c.user_id = ? 
                       ORDER BY c.created_at DESC";
                       
        $stmtReviews = $pdo->prepare($sqlReviews);
        $stmtReviews->execute([$user_id]);
        $reviews = $stmtReviews->fetchAll();

        // Форматируем фото заведений в отзывах
        foreach ($reviews as &$rev) {
            $rev['photo'] = (strpos($rev['photo'], 'http') === 0) ? $rev['photo'] : $baseUrl . '/' . $rev['photo'];
            // Добавляем текстовый статус для удобства
            $rev['status_text'] = ($rev['is_approved'] == 1) ? 'Опубликован' : 'На модерации';
        }

        // Собираем ответ
        $response = [
            'profile' => $userData,
            'my_reviews' => $reviews
        ];

        response(true, 'Profile data', $response);

    } catch (Exception $e) {
        response(false, $e->getMessage());
    }
}
?>