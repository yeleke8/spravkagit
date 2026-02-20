<?php
require_once 'headers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = 0;

if (!$id) response(false, 'ID required');

// Auth check (необязательно для просмотра, но нужно для статуса избранного)
if (isset($_GET['api_key'])) {
    $headers = getallheaders();
    $token = $_GET['api_key'];
    $stmtU = $pdo->prepare("SELECT user_id FROM users WHERE api_key = ?");
    $stmtU->execute([$token]);
    $u = $stmtU->fetch();
    if($u) $user_id = $u['user_id'];
}

try {
    // 1. ЛОГИРОВАНИЕ ПРОСМОТРА (Защита от накрутки)
    $ip = $_SERVER['REMOTE_ADDR'];
    // Проверяем, смотрел ли этот IP этот пост за последний час
    $stmtCheckView = $pdo->prepare("SELECT view_id FROM log_post_views WHERE post_id = ? AND ip_address = INET6_ATON(?) AND viewed_at > (NOW() - INTERVAL 1 HOUR)");
    $stmtCheckView->execute([$id, $ip]);
    
    if (!$stmtCheckView->fetch()) {
        // Записываем просмотр
        $pdo->prepare("INSERT INTO log_post_views (post_id, user_id, ip_address) VALUES (?, ?, INET6_ATON(?))")
            ->execute([$id, ($user_id ?: NULL), $ip]);
            
        // Обновляем счетчик в таблице post
        $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$id]);
    }

    // 2. ПОЛУЧЕНИЕ ДАННЫХ
    $stmt = $pdo->prepare("SELECT * FROM post WHERE post_id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) response(false, 'Post not found');

    $post['photo'] = (strpos($post['photo'], 'http') === 0) ? $post['photo'] : $baseUrl . '/' . $post['photo'];
    $post['contacts'] = json_decode($post['contacts'], true);
    $post['worktime'] = json_decode($post['worktime'], true);
    $post['attributes'] = json_decode($post['attributes'], true);

    // Удобства (Tags)
    $stmtTags = $pdo->prepare("SELECT t.attr_id, t.attr_name, t.attr_icon FROM tags t JOIN s_tags st ON t.attr_id = st.attr_id WHERE st.post_id = ?");
    $stmtTags->execute([$id]);
    $post['tags'] = $stmtTags->fetchAll();

    // Категории (Breadcrumbs)
    $stmtCats = $pdo->prepare("SELECT c.cat_id, c.cat_name FROM categories c JOIN s_categories sc ON c.cat_id = sc.cat_id WHERE sc.post_id = ?");
    $stmtCats->execute([$id]);
    $post['categories'] = $stmtCats->fetchAll();

    // Отзывы
    $stmtComm = $pdo->prepare("SELECT c.rating, c.comment, c.created_at, u.user_name, c.owner_reply, c.reply_created_at 
                               FROM comments c 
                               JOIN users u ON c.user_id = u.user_id 
                               WHERE c.post_id = ? AND c.is_approved = 1 
                               ORDER BY c.created_at DESC");
    $stmtComm->execute([$id]);
    $post['comments'] = $stmtComm->fetchAll();

    // Статус "Избранное"
    $isFav = false;
    if ($user_id > 0) {
        $stmtFav = $pdo->prepare("SELECT favorites_id FROM s_favorites WHERE user_id = ? AND post_id = ?");
        $stmtFav->execute([$user_id, $id]);
        if($stmtFav->fetch()) $isFav = true;
    }
    $post['is_favorite'] = $isFav;

    response(true, 'Post details', $post);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>