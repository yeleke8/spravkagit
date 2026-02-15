<?php
require_once 'headers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = 0;

// Егер юзер авторизациядан өткен болса, "Избранное" статусын тексеру үшін ID аламыз
if (isset($_GET['api_key'])) {
    $headers = getallheaders();
    $token = $_GET['api_key'];
    $stmtU = $pdo->prepare("SELECT user_id FROM users WHERE api_key = ?");
    $stmtU->execute([$token]);
    $u = $stmtU->fetch();
    if($u) $user_id = $u['user_id'];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM post WHERE post_id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) response(false, 'Post not found');

    // Фото
    $post['photo'] = (strpos($post['photo'], 'http') === 0) ? $post['photo'] : $baseUrl . '/' . $post['photo'];

    // JSON өрістерді array-ға айналдыру
    $post['contacts'] = json_decode($post['contacts'], true);
    $post['worktime'] = json_decode($post['worktime'], true);
    $post['attributes'] = json_decode($post['attributes'], true);

    // Тегтерді (ыңғайлылықтар) алу
    $stmtTags = $pdo->prepare("SELECT t.attr_name, t.attr_icon FROM tags t JOIN s_tags st ON t.attr_id = st.attr_id WHERE st.post_id = ?");
    $stmtTags->execute([$id]);
    $post['tags'] = $stmtTags->fetchAll();

    // Пікірлерді алу
    $stmtComm = $pdo->prepare("SELECT c.rating, c.comment, c.created_at, u.user_name FROM comments c JOIN users u ON c.user_id = u.user_id WHERE c.post_id = ? AND c.is_approved = 1 ORDER BY c.created_at DESC");
    $stmtComm->execute([$id]);
    $post['comments'] = $stmtComm->fetchAll();

    // Избранное статусы
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