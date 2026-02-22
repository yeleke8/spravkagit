<?php
// api/headers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Устанавливаем часовой пояс, чтобы время работы заведений (time()) и дата отзывов (NOW()) совпадали
date_default_timezone_set('Asia/Almaty');

require_once __DIR__ . '/../back/db.php';

$baseUrl = "https://fervent-williams.195-210-46-54.plesk.page/spravka/";

// Секретный ключ для JWT (НИКОМУ НЕ ПОКАЗЫВАТЬ)
// В идеале вынести в переменные окружения (.env)
define('JWT_SECRET', 'SpravkaSuperSecretKey2026!'); 
// Firebase Server Key (Для Legacy API) или Bearer токен (для HTTP v1)
define('FCM_SERVER_KEY', 'YOUR_FIREBASE_SERVER_KEY'); 

function response($success, $message, $data = null, $extra = []) {
    $out = array_merge([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], $extra);
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- БЕЗОПАСНОЕ ЛОГИРОВАНИЕ ОШИБОК ---
// Вызывайте эту функцию в блоках catch(), чтобы не отдавать структуру БД клиенту
function log_server_error($exception, $context = '') {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] ERROR {$context}: " . $exception->getMessage() . "\n";
    // Записываем ошибку в файл на уровень выше публичной папки, чтобы его нельзя было скачать по прямой ссылке
    error_log($logMessage, 3, __DIR__ . '/../server_errors.log');
}

// --- ФУНКЦИИ JWT И СЕССИЙ ---
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

function generate_refresh_token() {
    return bin2hex(random_bytes(32)); // Генерация случайной строки из 64 символов
}

function authenticate($pdo) {
    $token = '';
    
    // ИСПРАВЛЕНИЕ: Надежное получение заголовка Authorization для Nginx и Apache
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
                response(false, 'Token expired. Please refresh token.', null, ['code' => 401, 'action' => 'refresh_needed']);
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

// --- ОТПРАВКА PUSH УВЕДОМЛЕНИЙ ---
function send_fcm_push($pdo, $user_id, $title, $body, $data = []) {
    // 1. Получаем токены устройств юзера
    $stmt = $pdo->prepare("SELECT fcm_token FROM user_devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tokens)) return false; // У пользователя нет привязанных устройств

    // 2. Формируем тело запроса для Firebase
    $url = 'https://fcm.googleapis.com/fcm/send'; // Для Legacy API
    $notification = [
        'title' => $title,
        'body' => $body,
        'sound' => 'default',
        'badge' => '1'
    ];

    $payload = [
        'registration_ids' => $tokens,
        'notification' => $notification,
        'data' => $data, // Доп. данные, которые парсит Android без показа уведомления
        'priority' => 'high'
    ];

    $headers = [
        'Authorization: key=' . FCM_SERVER_KEY,
        'Content-Type: application/json'
    ];

    // 3. Отправка через cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
?>