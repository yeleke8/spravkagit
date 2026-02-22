<?php
require_once 'headers.php';

$user = authenticate($pdo);
$user_id = $user['user_id'];

$action = $_GET['action'] ?? '';

try {
    // --- ПОЛУЧИТЬ СПИСОК ИЗБРАННОГО С ПАГИНАЦИЕЙ ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        // Считаем общее количество для пагинации
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM s_favorites WHERE user_id = ?");
        $stmtCount->execute([$user_id]);
        $totalItems = $stmtCount->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Получаем сами данные с учетом лимитов
        $sql = "SELECT p.post_id, p.title, p.address, p.photo, p.rating_avg, p.rating_count 
                FROM post p 
                JOIN s_favorites f ON p.post_id = f.post_id 
                WHERE f.user_id = ? 
                ORDER BY f.favorites_id DESC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($favorites as &$item) {
            $item['photo'] = (strpos($item['photo'], 'http') === 0) ? $item['photo'] : $baseUrl . '/' . $item['photo'];
        }

        response(true, 'My Favorites', $favorites, [
            'pagination' => [
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'has_more' => $page < $totalPages
            ]
        ]);
    } 
    
    // --- ДОБАВИТЬ / УДАЛИТЬ ---
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