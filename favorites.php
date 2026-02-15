<?php
require_once 'headers.php';

// Авторизацияны тексереміз
$user = authenticate($pdo);
$user_id = $user['user_id'];

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if (!$post_id) response(false, 'Post ID required');

try {
    // Тексереміз: бар ма әлде жоқ па?
    $stmt = $pdo->prepare("SELECT favorites_id FROM s_favorites WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Бар болса өшіреміз
        $pdo->prepare("DELETE FROM s_favorites WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
        response(true, 'Removed from favorites', ['status' => 'removed']);
    } else {
        // Жоқ болса қосамыз
        $pdo->prepare("INSERT INTO s_favorites (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
        response(true, 'Added to favorites', ['status' => 'added']);
    }

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>