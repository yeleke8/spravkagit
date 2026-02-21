<?php
require_once 'headers.php';

// Обязательная проверка: доступ только для owner или admin
$user = authenticate($pdo);
if ($user['user_type'] !== 'owner' && $user['user_type'] !== 'admin') {
    response(false, 'Доступ запрещен. Только для владельцев бизнеса.', null, ['code' => 403]);
}

$user_id = $user['user_id'];
$action = $_GET['action'] ?? '';

// Вспомогательная функция для создания slug
function createSlug($string) {
    $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
    $slug = $transliterator->transliterate($string);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    return trim($slug, '-');
}

try {
    // 1. Получить список своих заведений со статистикой
    if ($action === 'my_places' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT post_id, title, photo, views, rating_avg, rating_count, status 
                FROM post 
                WHERE owner_id = ? AND status != 2 
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $places = $stmt->fetchAll();

        foreach ($places as &$place) {
            $place['photo'] = (strpos($place['photo'], 'http') === 0) ? $place['photo'] : $baseUrl . '/' . $place['photo'];
        }

        response(true, 'Мои заведения', $places);
    }

    // 2. Ответить на комментарий клиента
    elseif ($action === 'reply_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $comment_id = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;
        $reply_text = isset($input['reply_text']) ? trim($input['reply_text']) : '';

        if (!$comment_id || empty($reply_text)) response(false, 'Укажите ID комментария и текст ответа');

        $checkSql = "SELECT c.comment_id FROM comments c JOIN post p ON c.post_id = p.post_id WHERE c.comment_id = ? AND p.owner_id = ?";
        $stmtCheck = $pdo->prepare($checkSql);
        $stmtCheck->execute([$comment_id, $user_id]);
        if (!$stmtCheck->fetch()) response(false, 'Комментарий не найден или вы не владелец');

        $pdo->prepare("UPDATE comments SET owner_reply = ?, reply_created_at = NOW() WHERE comment_id = ?")->execute([$reply_text, $comment_id]);
        response(true, 'Ответ успешно опубликован');
    }

    // 3. Добавить новое заведение
    elseif ($action === 'add_place' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $address = trim($input['address'] ?? '');
        $photo = $input['photo'] ?? 'uploads/default.jpg';
        $lat = isset($input['latitude']) ? (float)$input['latitude'] : null;
        $lng = isset($input['longitude']) ? (float)$input['longitude'] : null;
        $worktime = json_encode($input['worktime'] ?? []);
        $attributes = json_encode($input['attributes'] ?? []);
        $contacts = json_encode($input['contacts'] ?? []);
        $categories = $input['categories'] ?? []; // Массив ID категорий
        $tags = $input['tags'] ?? []; // Массив ID тегов

        if (empty($title) || empty($address)) response(false, 'Название и адрес обязательны');

        $slug = createSlug($title) . '-' . time(); // Уникальный slug

        $pdo->beginTransaction();

        $sql = "INSERT INTO post (title, slug, psevdonim, description, address, latitude, longitude, worktime, photo, owner_id, attributes, contacts, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"; // status 0 = на модерации
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $slug, $title, $description, $address, $lat, $lng, $worktime, $photo, $user_id, $attributes, $contacts]);
        
        $post_id = $pdo->lastInsertId();

        // Добавляем категории
        if (!empty($categories)) {
            $catStmt = $pdo->prepare("INSERT IGNORE INTO s_categories (post_id, cat_id) VALUES (?, ?)");
            foreach ($categories as $cat_id) {
                $catStmt->execute([$post_id, (int)$cat_id]);
            }
        }

        // Добавляем теги
        if (!empty($tags)) {
            $tagStmt = $pdo->prepare("INSERT IGNORE INTO s_tags (post_id, attr_id) VALUES (?, ?)");
            foreach ($tags as $tag_id) {
                $tagStmt->execute([$post_id, (int)$tag_id]);
            }
        }

        $pdo->commit();
        response(true, 'Заведение добавлено и отправлено на модерацию', ['post_id' => $post_id]);
    }

    // 4. Редактировать заведение
    elseif ($action === 'edit_place' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = (int)($input['post_id'] ?? 0);

        if (!$post_id) response(false, 'Укажите ID заведения');

        // Проверка владельца
        $stmtCheck = $pdo->prepare("SELECT post_id FROM post WHERE post_id = ? AND owner_id = ?");
        $stmtCheck->execute([$post_id, $user_id]);
        if (!$stmtCheck->fetch()) response(false, 'Доступ запрещен');

        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $address = trim($input['address'] ?? '');
        $photo = $input['photo'] ?? 'uploads/default.jpg';
        $lat = isset($input['latitude']) ? (float)$input['latitude'] : null;
        $lng = isset($input['longitude']) ? (float)$input['longitude'] : null;
        $worktime = json_encode($input['worktime'] ?? []);
        $attributes = json_encode($input['attributes'] ?? []);
        $contacts = json_encode($input['contacts'] ?? []);
        $categories = $input['categories'] ?? null;
        $tags = $input['tags'] ?? null;

        $pdo->beginTransaction();

        $sql = "UPDATE post SET title=?, description=?, address=?, latitude=?, longitude=?, worktime=?, photo=?, attributes=?, contacts=? WHERE post_id=?";
        $pdo->prepare($sql)->execute([$title, $description, $address, $lat, $lng, $worktime, $photo, $attributes, $contacts, $post_id]);

        // Обновляем категории, если переданы
        if (is_array($categories)) {
            $pdo->prepare("DELETE FROM s_categories WHERE post_id = ?")->execute([$post_id]);
            $catStmt = $pdo->prepare("INSERT IGNORE INTO s_categories (post_id, cat_id) VALUES (?, ?)");
            foreach ($categories as $cat_id) {
                $catStmt->execute([$post_id, (int)$cat_id]);
            }
        }

        // Обновляем теги, если переданы
        if (is_array($tags)) {
            $pdo->prepare("DELETE FROM s_tags WHERE post_id = ?")->execute([$post_id]);
            $tagStmt = $pdo->prepare("INSERT IGNORE INTO s_tags (post_id, attr_id) VALUES (?, ?)");
            foreach ($tags as $tag_id) {
                $tagStmt->execute([$post_id, (int)$tag_id]);
            }
        }

        $pdo->commit();
        response(true, 'Заведение успешно обновлено');
    }

    // 5. Удалить/Скрыть заведение (Архивация)
    elseif ($action === 'delete_place' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = (int)($input['post_id'] ?? 0);

        if (!$post_id) response(false, 'Укажите ID заведения');

        $stmtCheck = $pdo->prepare("SELECT post_id FROM post WHERE post_id = ? AND owner_id = ?");
        $stmtCheck->execute([$post_id, $user_id]);
        if (!$stmtCheck->fetch()) response(false, 'Доступ запрещен');

        // Меняем статус на 2 (Удален/Архив)
        $pdo->prepare("UPDATE post SET status = 2 WHERE post_id = ?")->execute([$post_id]);
        response(true, 'Заведение удалено');
    }
    
    else {
        response(false, 'Неизвестное действие (action) или неверный HTTP метод');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    response(false, 'Ошибка сервера: ' . $e->getMessage());
}
?>