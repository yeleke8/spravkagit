<?php
require_once 'headers.php';

$user = authenticate($pdo);
$user_id = $user['user_id'];

$action = $_GET['action'] ?? '';

try {
    // --- ПОЛУЧИТЬ СПИСОК ИЗБРАННОГО ---
    // Используем строгий GET метод
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $sql = "SELECT p.post_id, p.title, p.address, p.photo, p.rating_avg, p.rating_count 
                FROM post p 
                JOIN s_favorites f ON p.post_id = f.post_id 
                WHERE f.user_id = ? 
                ORDER BY f.favorites_id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $favorites = $stmt->fetchAll();

        // Форматируем фото
        foreach ($favorites as &$item) {
            $item['photo'] = (strpos($item['photo'], 'http') === 0) ? $item['photo'] : $baseUrl . '/' . $item['photo'];
        }

        response(true, 'My Favorites', $favorites);
    } 
    
    // --- ДОБАВИТЬ / УДАЛИТЬ ---
    // Используем строгий POST метод и читаем данные из JSON тела (Retrofit @Body)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
        
        if (!$post_id) response(false, 'Post ID required');

        $stmt = $pdo->prepare("SELECT favorites_id FROM s_favorites WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            $pdo->prepare("DELETE FROM s_favorites WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
            response(true, 'Removed from favorites', ['status' => false]);
        } else {
            $pdo->prepare("INSERT INTO s_favorites (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
            response(true, 'Added to favorites', ['status' => true]);
        }
    } else {
        response(false, 'Invalid request method');
    }

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>