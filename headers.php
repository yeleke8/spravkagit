<?php
// api/headers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Устанавливаем часовой пояс
date_default_timezone_set('Asia/Almaty');

require_once __DIR__ . '/../back/db.php';

$baseUrl = "https://fervent-williams.195-210-46-54.plesk.page/spravka/";

// Секретный ключ для JWT (НИКОМУ НЕ ПОКАЗЫВАТЬ)
define('JWT_SECRET', 'SpravkaSuperSecretKey2026!'); 

// ПУТЬ К ФАЙЛУ КЛЮЧЕЙ FIREBASE (.json)
// ВАЖНО: Скачайте его из консоли Firebase и положите на уровень выше папки api, чтобы он не был доступен из браузера
define('FIREBASE_CREDENTIALS_PATH', __DIR__ . '/../firebase_credentials.json');

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
function log_server_error($exception, $context = '') {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] ERROR {$context}: " . $exception->getMessage() . "\n";
    error_log($logMessage, 3, __DIR__ . '/../server_errors.log');
}

// --- ФУНКЦИИ JWT И СЕССИЙ ---
function generate_jwt($user_id, $user_type) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'user_type' => $user_type,
        'iat' => time(),
        'exp' => time() + (30 * 24 * 60 * 60)
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function generate_refresh_token() {
    return bin2hex(random_bytes(32));
}

function authenticate($pdo) {
    $token = '';
    
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

    $stmt = $pdo->prepare("SELECT user_id, user_name, user_type FROM users WHERE api_key = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) response(false, 'Invalid token', null, ['code' => 401]);
    return $user;
}


// ========================================================================
// НОВЫЙ БЛОК: FIREBASE HTTP V1 API (С ГЕНЕРАЦИЕЙ OAUTH2 ТОКЕНА БЕЗ COMPOSER)
// ========================================================================

/**
 * Генерирует и кэширует временный Access Token (живет 1 час) для работы с Google API
 */
function get_fcm_access_token() {
    if (!file_exists(FIREBASE_CREDENTIALS_PATH)) return false;

    $keyData = json_decode(file_get_contents(FIREBASE_CREDENTIALS_PATH), true);
    if (!$keyData) return false;

    $clientEmail = $keyData['client_email'];
    $privateKey = $keyData['private_key'];

    // Кэшируем токен во временную папку сервера, чтобы не дергать Google каждый раз
    $cacheFile = sys_get_temp_dir() . '/spravka_fcm_token_cache.json';
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        if ($cache && isset($cache['expires_at']) && $cache['expires_at'] > time() + 60) {
            return $cache['access_token'];
        }
    }

    // Создаем JWT для запроса токена OAuth2
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = '';
    openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    // Запрашиваем Access Token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        // Сохраняем в кэш
        file_put_contents($cacheFile, json_encode([
            'access_token' => $data['access_token'],
            'expires_at' => $now + $data['expires_in']
        ]));
        return $data['access_token'];
    }

    return false;
}

/**
 * Отправляет Push-уведомление через Firebase HTTP v1 API
 */
function send_fcm_push($pdo, $user_id, $title, $body, $data = []) {
    // 1. Получаем токены устройств юзера
    $stmt = $pdo->prepare("SELECT fcm_token FROM user_devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tokens)) return false; 

    // 2. Получаем ID проекта и Access Token
    if (!file_exists(FIREBASE_CREDENTIALS_PATH)) return false;
    $keyData = json_decode(file_get_contents(FIREBASE_CREDENTIALS_PATH), true);
    $projectId = $keyData['project_id'];

    $accessToken = get_fcm_access_token();
    if (!$accessToken) return false;

    $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    // ВАЖНО для HTTP v1: Все значения в массиве 'data' ДОЛЖНЫ быть строками.
    $stringData = [];
    foreach ($data as $key => $val) {
        $stringData[(string)$key] = (string)$val;
    }

    // 3. Отправляем запросы параллельно (Multi-cURL), так как HTTP v1 
    // не поддерживает отправку массива регистрационных токенов в одном запросе
    $mh = curl_multi_init();
    $handles = [];

    foreach ($tokens as $token) {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => (string)$title,
                    'body' => (string)$body,
                ],
                'data' => empty($stringData) ? null : $stringData,
                'android' => [
                    'priority' => 'high'
                ]
            ]
        ];

        // Убираем пустые узлы
        if (empty($payload['message']['data'])) unset($payload['message']['data']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    // Выполняем все запросы одновременно (высокая скорость)
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running);

    // Очищаем ресурсы
    foreach ($handles as $ch) {
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    return true;
}
?>