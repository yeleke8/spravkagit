<?php
// ajax-favorite.php - Обработчик избранного
require_once 'back/db.php';
require_once 'back/functions.php';

header('Content-Type: application/json');

// 1. Если пользователь не авторизован
if (!is_logged_in()) {
    // Возвращаем специальный статус, чтобы JS знал, что нужно перенаправить на логин
    echo json_encode(['status' => 'login_required', 'message' => 'Сначала войдите в систему']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Только POST запросы']);
    exit;
}

// 2. Получаем ID поста
$input = json_decode(file_get_contents('php://input'), true);
$post_id = isset($input['id']) ? (int)$input['id'] : 0;

if (!$post_id) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 3. Проверяем наличие в базе
    $stmt = $pdo->prepare("SELECT favorites_id FROM s_favorites WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Удаляем
        $pdo->prepare("DELETE FROM s_favorites WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
        $action = 'removed';
    } else {
        // Добавляем (IGNORE на всякий случай)
        $pdo->prepare("INSERT IGNORE INTO s_favorites (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
        $action = 'added';
    }

    echo json_encode(['status' => 'success', 'action' => $action]);

} catch (Exception $e) {
    // В продакшене лучше писать в лог, а юзеру отдавать общее сообщение
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных']);
}
?>