<?php
require_once 'back/db.php';
require_once 'back/functions.php';

header('Content-Type: application/json');

// 1. Проверки доступа
if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Нужна авторизация']);
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
    // 3. Проверяем, есть ли уже в избранном
    $stmt = $pdo->prepare("SELECT favorites_id FROM s_favorites WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Удаляем
        $pdo->prepare("DELETE FROM s_favorites WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
        $action = 'removed';
    } else {
        // Добавляем (используем IGNORE на случай гонки запросов, так как в базе стоит UNIQUE ключ)
        $pdo->prepare("INSERT IGNORE INTO s_favorites (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
        $action = 'added';
    }

    echo json_encode(['status' => 'success', 'action' => $action]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка БД']);
}