<?php
// api/headers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../back/db.php';

$baseUrl = "https://fervent-williams.195-210-46-54.plesk.page/spravka/";

// Секретный ключ для JWT (НИКОМУ НЕ ПОКАЗЫВАТЬ)
define('JWT_SECRET', 'SpravkaSuperSecretKey2026!'); 

function response($success, $message, $data = null, $extra = []) {
    $out = array_merge([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], $extra);
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- ФУНКЦИИ JWT ---
function generate_jwt($user_id, $user_type) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'user_type' => $user_type,
        'iat' => time(), // Время создания
        'exp' => time() + (30 * 24 * 60 * 60) // Годен 30 дней
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function authenticate($pdo) {
    $headers = getallheaders();
    $token = '';
    
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_GET['api_key'])) {
        $token = $_GET['api_key'];
    }

    if (!$token) response(false, 'Auth token not found', null, ['code' => 401]);

    // Пробуем расшифровать как JWT
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (hash_equals($base64UrlSignature, $parts[2])) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (isset($payload['exp']) && $payload['exp'] >= time()) {
                return ['user_id' => $payload['user_id'], 'user_type' => $payload['user_type']];
            } else {
                response(false, 'Token expired. Please login again.', null, ['code' => 401]);
            }
        }
    }

    // Обратная совместимость (если это старый api_key из базы)
    $stmt = $pdo->prepare("SELECT user_id, user_name, user_type FROM users WHERE api_key = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) response(false, 'Invalid token', null, ['code' => 401]);
    return $user;
}
?>