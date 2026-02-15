<?php
// api/headers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Сіздің негізгі базаға қосылу файлыңызды қосамыз
// Жолды дұрыс көрсету керек (api папкасынан шығып, back папкасына кіру)
require_once __DIR__ . '/../back/db.php';

// Базалық URL (суреттер үшін керек)
// Мұны өз доменіңізге немесе IP-ге ауыстырыңыз
$baseUrl = "https://fervent-williams.195-210-46-54.plesk.page/spravka/"; // МЫСАЛ ҮШІН

function response($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Token арқылы юзерді тексеру функциясы
function authenticate($pdo) {
    $headers = getallheaders();
    $token = '';
    
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_GET['api_key'])) {
        $token = $_GET['api_key'];
    }

    if (!$token) {
        response(false, 'Auth token not found');
    }

    $stmt = $pdo->prepare("SELECT user_id, user_name, user_type FROM users WHERE api_key = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        response(false, 'Invalid token');
    }
    return $user;
}
?>