<?php
require_once 'headers.php';

$user = authenticate($pdo);
$user_id = $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
    $rating = isset($input['rating']) ? (int)$input['rating'] : 5;
    $comment = isset($input['comment']) ? trim($input['comment']) : '';

    if (!$post_id) response(false, 'Post ID required');
    if ($rating < 1 || $rating > 5) response(false, 'Rating must be 1-5');
    if (empty($comment)) response(false, 'Comment text required');

    try {
        // Проверяем, оставлял ли уже отзыв (UNIQUE constraint в базе)
        $stmtCheck = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ? AND post_id = ?");
        $stmtCheck->execute([$user_id, $post_id]);
        if ($stmtCheck->fetch()) {
            response(false, 'Вы уже оставляли отзыв к этому месту');
        }

        // Добавляем отзыв
        // is_approved = 1 (Сразу публикуем. Если нужна модерация, поставьте 0)
        $sql = "INSERT INTO comments (user_id, post_id, rating, comment, is_approved, created_at) VALUES (?, ?, ?, ?, 1, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $post_id, $rating, $comment]);

        // Рейтинг в таблице post обновится САМ благодаря триггерам в SQL!
        
        response(true, 'Отзыв успешно добавлен');

    } catch (Exception $e) {
        response(false, 'Ошибка: ' . $e->getMessage());
    }
} else {
    response(false, 'Only POST method allowed');
}
?>