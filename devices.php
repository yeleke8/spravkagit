<?php
require_once 'headers.php';

$user = authenticate($pdo);
$user_id = $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fcm_token = $input['fcm_token'] ?? '';
    $device_type = $input['device_type'] ?? 'android';

    if (empty($fcm_token)) {
        response(false, 'FCM Token required');
    }

    try {
        // Удаляем старые записи этого токена, если они были у другого юзера
        $pdo->prepare("DELETE FROM user_devices WHERE fcm_token = ?")->execute([$fcm_token]);

        // Сохраняем новый токен
        $sql = "INSERT INTO user_devices (user_id, fcm_token, device_type) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $fcm_token, $device_type]);

        response(true, 'Device token updated');
    } catch (Exception $e) {
        response(false, 'Error: ' . $e->getMessage());
    }
} else {
    response(false, 'Method Not Allowed');
}
?>