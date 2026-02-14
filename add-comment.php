<?php
// add-comment.php - Безопасный обработчик отзывов
require_once 'back/db.php';
require_once 'back/functions.php';

// 1. Проверка авторизации
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// 2. Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
$comment = trim($_POST['comment'] ?? '');

// 3. Получаем slug поста для редиректа
// Важно проверить существование поста перед тем, как что-то делать
$stmtPost = $pdo->prepare("SELECT slug FROM post WHERE post_id = ?");
$stmtPost->execute([$post_id]);
$post = $stmtPost->fetch();

if (!$post) {
    $_SESSION['flash_error'] = "Ошибка: Заведение не найдено.";
    header("Location: index.php");
    exit;
}

$redirectUrl = "post.php?slug=" . $post['slug'];

// 4. Валидация данных
$errors = [];

if ($rating < 1 || $rating > 5) {
    $errors[] = "Некорректная оценка.";
}

if (mb_strlen($comment) < 5) {
    $errors[] = "Отзыв слишком короткий. Напишите хотя бы пару слов.";
}

if (mb_strlen($comment) > 1000) {
    $errors[] = "Отзыв слишком длинный (максимум 1000 символов).";
}

// 5. Проверка на дубликат (если пользователь уже оставлял отзыв)
$stmtCheck = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ? AND post_id = ?");
$stmtCheck->execute([$user_id, $post_id]);
if ($stmtCheck->fetch()) {
    $errors[] = "Вы уже оставляли отзыв к этому заведению.";
}

// Если есть ошибки - возвращаем назад через сессию
if (!empty($errors)) {
    // Собираем ошибки в одну строку через точку
    $_SESSION['flash_error'] = implode(" ", $errors);
    header("Location: " . $redirectUrl);
    exit;
}

// 6. Добавление в базу
try {
    $sql = "INSERT INTO comments (user_id, post_id, rating, comment, is_approved, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$user_id, $post_id, $rating, $comment])) {
        $_SESSION['flash'] = "Спасибо! Ваш отзыв принят и отправлен на модерацию.";
    } else {
        $_SESSION['flash_error'] = "Произошла ошибка при сохранении.";
    }

} catch (PDOException $e) {
    // Ловим ошибку уникального ключа (код 23000), если проверка выше не сработала (race condition)
    if ($e->getCode() == 23000) {
         $_SESSION['flash_error'] = "Вы уже оценивали это заведение!";
    } else {
         // Никогда не выводите $e->getMessage() пользователю в продакшене (безопасность)
         // Лучше записать в лог: error_log($e->getMessage());
         $_SESSION['flash_error'] = "Внутренняя ошибка сервера. Попробуйте позже.";
    }
}

// Финальный редирект
header("Location: " . $redirectUrl);
exit;
?>