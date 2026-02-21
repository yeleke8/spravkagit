<?php
require_once 'headers.php';

$user = authenticate($pdo);
$user_id = $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $target_id = (int)($input['target_id'] ?? 0);
    $target_type = $input['target_type'] ?? ''; // 'post' или 'comment'
    $reason = trim($input['reason'] ?? '');

    if (!$target_id || !in_array($target_type, ['post', 'comment']) || empty($reason)) {
        response(false, 'Неверные данные жалобы');
    }

    try {
        $sql = "INSERT INTO reports (reporter_id, target_id, target_type, reason) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $target_id, $target_type, $reason]);

        response(true, 'Жалоба принята и будет рассмотрена модератором');
    } catch (Exception $e) {
        response(false, 'Ошибка БД: ' . $e->getMessage());
    }
} else {
    response(false, 'Method Not Allowed');
}
?>