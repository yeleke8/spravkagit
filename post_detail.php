<?php
require_once 'headers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Пагинация для отзывов
$comments_page = isset($_GET['c_page']) ? (int)$_GET['c_page'] : 1; 
$comments_limit = 10;
$c_offset = ($comments_page - 1) * $comments_limit;

$user_id = 0;

if (!$id) response(false, 'ID required');

$token = '';
// ИСПРАВЛЕНИЕ: Получение токена без вызова getallheaders() для совместимости с Nginx
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = trim(str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']));
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $token = trim(str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = trim(str_replace('Bearer ', '', $headers['Authorization']));
    }
}

if (!$token && isset($_GET['api_key'])) {
    $token = trim($_GET['api_key']);
}

if ($token) {
    // В post_detail.php мы делаем легкую проверку токена, чтобы не кидать 401 ошибку 
    // если токен истек (гость тоже может смотреть заведение)
    try {
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (isset($payload['exp']) && $payload['exp'] >= time()) {
                $user_id = $payload['user_id'];
            }
        } else {
            $stmtU = $pdo->prepare("SELECT user_id FROM users WHERE api_key = ?");
            $stmtU->execute([$token]);
            $u = $stmtU->fetch();
            if($u) $user_id = $u['user_id'];
        }
    } catch(Exception $e) {}
}

try {
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($user_id > 0) {
        $stmtCheckView = $pdo->prepare("SELECT view_id FROM log_post_views WHERE post_id = ? AND user_id = ? AND viewed_at > (NOW() - INTERVAL 1 HOUR)");
        $stmtCheckView->execute([$id, $user_id]);
    } else {
        $stmtCheckView = $pdo->prepare("SELECT view_id FROM log_post_views WHERE post_id = ? AND ip_address = INET6_ATON(?) AND viewed_at > (NOW() - INTERVAL 1 HOUR)");
        $stmtCheckView->execute([$id, $ip]);
    }
    
    if (!$stmtCheckView->fetch()) {
        $pdo->prepare("INSERT INTO log_post_views (post_id, user_id, ip_address) VALUES (?, ?, INET6_ATON(?))")->execute([$id, ($user_id ?: NULL), $ip]);
        $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$id]);
    }

    $stmt = $pdo->prepare("SELECT * FROM post WHERE post_id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) response(false, 'Post not found');

    $post['photo'] = (strpos($post['photo'], 'http') === 0) ? $post['photo'] : $baseUrl . '/' . $post['photo'];
    $post['contacts'] = json_decode($post['contacts'], true);
    $post['worktime'] = json_decode($post['worktime'], true);
    $post['attributes'] = json_decode($post['attributes'], true);

    $stmtTags = $pdo->prepare("SELECT t.attr_id, t.attr_name, t.attr_icon FROM tags t JOIN s_tags st ON t.attr_id = st.attr_id WHERE st.post_id = ?");
    $stmtTags->execute([$id]);
    $post['tags'] = $stmtTags->fetchAll();

    $stmtCats = $pdo->prepare("SELECT c.cat_id, c.cat_name FROM categories c JOIN s_categories sc ON c.cat_id = sc.cat_id WHERE sc.post_id = ?");
    $stmtCats->execute([$id]);
    $post['categories'] = $stmtCats->fetchAll();

    // Загрузка отзывов с ПАГИНАЦИЕЙ
    $stmtCommCount = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ? AND is_approved = 1");
    $stmtCommCount->execute([$id]);
    $totalComments = $stmtCommCount->fetchColumn();

    $stmtComm = $pdo->prepare("SELECT c.comment_id, c.rating, c.comment, c.created_at, u.user_name, c.owner_reply, c.reply_created_at 
                               FROM comments c 
                               JOIN users u ON c.user_id = u.user_id 
                               WHERE c.post_id = ? AND c.is_approved = 1 
                               ORDER BY c.created_at DESC 
                               LIMIT $comments_limit OFFSET $c_offset");
    $stmtComm->execute([$id]);
    $post['comments'] = $stmtComm->fetchAll();
    
    $post['comments_pagination'] = [
        'total' => $totalComments,
        'page' => $comments_page,
        'has_more' => ($c_offset + $comments_limit) < $totalComments
    ];

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