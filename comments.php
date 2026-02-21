<?php
require_once 'headers.php';

$user = authenticate($pdo);
$user_id = $user['user_id'];
$action = $_GET['action'] ?? 'add';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ДОБАВИТЬ ОТЗЫВ
    if ($action === 'add') {
        $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
        $rating = isset($input['rating']) ? (int)$input['rating'] : 5;
        $comment = isset($input['comment']) ? trim($input['comment']) : '';

        if (!$post_id) response(false, 'Post ID required');
        if ($rating < 1 || $rating > 5) response(false, 'Rating must be 1-5');
        if (empty($comment)) response(false, 'Comment text required');

        try {
            // Проверяем, оставлял ли уже отзыв
            $stmtCheck = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ? AND post_id = ?");
            $stmtCheck->execute([$user_id, $post_id]);
            if ($stmtCheck->fetch()) {
                response(false, 'Вы уже оставляли отзыв к этому месту');
            }

            // Добавляем отзыв
            $sql = "INSERT INTO comments (user_id, post_id, rating, comment, is_approved, created_at) VALUES (?, ?, ?, ?, 1, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $post_id, $rating, $comment]);

            response(true, 'Отзыв успешно добавлен');
        } catch (Exception $e) {
            response(false, 'Ошибка: ' . $e->getMessage());
        }
    }
    
    // РЕДАКТИРОВАТЬ СВОЙ ОТЗЫВ
    elseif ($action === 'edit') {
        $comment_id = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;
        $rating = isset($input['rating']) ? (int)$input['rating'] : 5;
        $comment = isset($input['comment']) ? trim($input['comment']) : '';

        if (!$comment_id || empty($comment)) response(false, 'Неверные данные');

        try {
            $sql = "UPDATE comments SET comment = ?, rating = ?, created_at = NOW() WHERE comment_id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$comment, $rating, $comment_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Искусственно "касаемся" таблицы для срабатывания триггера обновления рейтинга в post
                $pdo->prepare("UPDATE comments SET comment_id = comment_id WHERE comment_id = ?")->execute([$comment_id]);
                response(true, 'Отзыв обновлен');
            } else {
                response(false, 'Отзыв не найден или вы не являетесь его автором');
            }
        } catch (Exception $e) {
            response(false, 'Ошибка: ' . $e->getMessage());
        }
    }

    // УДАЛИТЬ СВОЙ ОТЗЫВ
    elseif ($action === 'delete') {
        $comment_id = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;
        
        if (!$comment_id) response(false, 'ID отзыва обязателен');

        try {
            $sql = "DELETE FROM comments WHERE comment_id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$comment_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                response(true, 'Отзыв удален');
            } else {
                response(false, 'Отзыв не найден или вы не являетесь его автором');
            }
        } catch (Exception $e) {
            response(false, 'Ошибка: ' . $e->getMessage());
        }
    }
    else {
        response(false, 'Unknown action');
    }
} else {
    response(false, 'Only POST method allowed');
}
?>