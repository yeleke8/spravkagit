<?php
require_once 'headers.php';

$user = authenticate($pdo);
// Строгая проверка на админа
if ($user['user_type'] !== 'admin') {
    response(false, 'Доступ запрещен. Только для администраторов.', null, ['code' => 403]);
}

$action = $_GET['action'] ?? '';

try {
    // 1. Получение списка жалоб
    if ($action === 'get_reports' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // ... [Без изменений] ...
        $status = $_GET['status'] ?? 'pending';
        $sql = "SELECT r.*, u.user_name as reporter_name 
                FROM reports r 
                JOIN users u ON r.reporter_id = u.user_id 
                WHERE r.status = ? ORDER BY r.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
        response(true, 'Жалобы', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 2. Изменение статуса жалобы
    elseif ($action === 'update_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // ... [Без изменений] ...
        $input = json_decode(file_get_contents('php://input'), true);
        $report_id = (int)($input['report_id'] ?? 0);
        $status = $input['status'] ?? ''; 

        if (!$report_id || !in_array($status, ['reviewed', 'dismissed'])) response(false, 'Неверные данные');

        $pdo->prepare("UPDATE reports SET status = ? WHERE report_id = ?")->execute([$status, $report_id]);
        response(true, 'Статус жалобы обновлен');
    }

    // 3. Модерация заведений
    elseif ($action === 'moderate_places' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT post_id, title, address, created_at, status FROM post WHERE status = 0 ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        response(true, 'Заведения на модерации', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 4. Одобрить/Отклонить заведение
    elseif ($action === 'approve_place' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = (int)($input['post_id'] ?? 0);
        $approve = isset($input['approve']) ? (bool)$input['approve'] : true;

        // Получаем информацию о владельце для пуша
        $stmtInfo = $pdo->prepare("SELECT owner_id, title FROM post WHERE post_id = ?");
        $stmtInfo->execute([$post_id]);
        $placeData = $stmtInfo->fetch();

        $new_status = $approve ? 1 : 2; // 1 - опубликовано, 2 - удалено/отклонено
        $pdo->prepare("UPDATE post SET status = ? WHERE post_id = ?")->execute([$new_status, $post_id]);
        
        // --- ОТПРАВКА PUSH УВЕДОМЛЕНИЯ ВЛАДЕЛЬЦУ ---
        if ($placeData && $placeData['owner_id']) {
            $msg = $approve ? "Модератор одобрил публикацию." : "Модератор отклонил публикацию.";
            send_fcm_push($pdo, $placeData['owner_id'], "Статус заведения «{$placeData['title']}» изменен", $msg, [
                'type' => 'place_moderation',
                'post_id' => $post_id,
                'status' => $new_status
            ]);
        }

        response(true, $approve ? 'Заведение опубликовано' : 'Заведение отклонено');
    }

    // 5. Управление пользователями
    elseif ($action === 'get_users' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query("SELECT user_id, login, user_name, user_phone, user_type, registereddate FROM users ORDER BY registereddate DESC");
        response(true, 'Пользователи', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 6. Изменить роль пользователя
    elseif ($action === 'change_role' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // ... [Без изменений] ...
        $input = json_decode(file_get_contents('php://input'), true);
        $target_user_id = (int)($input['user_id'] ?? 0);
        $new_role = $input['role'] ?? '';

        if (!in_array($new_role, ['admin', 'user', 'owner'])) response(false, 'Неверная роль');
        if ($target_user_id === $user['user_id']) response(false, 'Нельзя изменить роль самому себе');

        $pdo->prepare("UPDATE users SET user_type = ? WHERE user_id = ?")->execute([$new_role, $target_user_id]);
        response(true, 'Роль успешно изменена');
    }

    else {
        response(false, 'Неизвестное действие (action) или неверный HTTP метод');
    }

} catch (Exception $e) {
    response(false, 'Ошибка сервера: ' . $e->getMessage());
}
?>