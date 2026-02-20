<?php
require_once 'headers.php';

// Обязательная проверка: доступ только для owner или admin
$user = authenticate($pdo);
if ($user['user_type'] !== 'owner' && $user['user_type'] !== 'admin') {
    response(false, 'Доступ запрещен. Только для владельцев бизнеса.', null, ['code' => 403]);
}

$user_id = $user['user_id'];
$action = $_GET['action'] ?? '';

try {
    // 1. Получить список своих заведений со статистикой
    if ($action === 'my_places' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT post_id, title, photo, views, rating_avg, rating_count, status 
                FROM post 
                WHERE owner_id = ? 
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $places = $stmt->fetchAll();

        foreach ($places as &$place) {
            $place['photo'] = (strpos($place['photo'], 'http') === 0) ? $place['photo'] : $baseUrl . '/' . $place['photo'];
        }

        response(true, 'Мои заведения', $places);
    }

    // 2. Ответить на комментарий клиента
    elseif ($action === 'reply_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $comment_id = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;
        $reply_text = isset($input['reply_text']) ? trim($input['reply_text']) : '';

        if (!$comment_id || empty($reply_text)) {
            response(false, 'Укажите ID комментария и текст ответа');
        }

        // Проверяем, принадлежит ли заведение этому владельцу
        $checkSql = "SELECT c.comment_id FROM comments c 
                     JOIN post p ON c.post_id = p.post_id 
                     WHERE c.comment_id = ? AND p.owner_id = ?";
        $stmtCheck = $pdo->prepare($checkSql);
        $stmtCheck->execute([$comment_id, $user_id]);
        
        if (!$stmtCheck->fetch()) {
            response(false, 'Комментарий не найден или вы не являетесь владельцем этого заведения');
        }

        // Обновляем ответ
        $updateSql = "UPDATE comments SET owner_reply = ?, reply_created_at = NOW() WHERE comment_id = ?";
        $stmtUpdate = $pdo->prepare($updateSql);
        $stmtUpdate->execute([$reply_text, $comment_id]);

        response(true, 'Ответ успешно опубликован');
    }
    
    else {
        response(false, 'Неизвестное действие (action) или неверный HTTP метод');
    }

} catch (Exception $e) {
    response(false, 'Ошибка сервера: ' . $e->getMessage());
}
?>