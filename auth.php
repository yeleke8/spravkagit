<?php
require_once 'headers.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // --- КІРУ (LOGIN) ---
    if ($action === 'login') {
        $login = trim($input['login'] ?? '');
        $password = $input['password'] ?? '';

        if (!$login || !$password) response(false, 'Логин және пароль енгізіңіз');

        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $jwt = generate_jwt($user['user_id'], $user['user_type']);
            $refresh_token = generate_refresh_token(); // Генерируем новый рефреш

            $pdo->prepare("UPDATE users SET lastonline = NOW(), refresh_token = ? WHERE user_id = ?")->execute([$refresh_token, $user['user_id']]);
            
            response(true, 'Сәтті кірдіңіз', [
                'user_id' => $user['user_id'],
                'name' => $user['user_name'],
                'user_type' => $user['user_type'],
                'token' => $jwt,
                'refresh_token' => $refresh_token
            ]);
        } else {
            response(false, 'Қате логин немесе пароль');
        }
    }

    // --- ТІРКЕЛУ (REGISTER) ---
    elseif ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $login = trim($input['login'] ?? '');
        $password = $input['password'] ?? '';

        if (mb_strlen($login) < 4) response(false, 'Логин тым қысқа');
        if (mb_strlen($password) < 6) response(false, 'Пароль тым қысқа');

        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        if (!preg_match('/^\+?[0-9]{10,15}$/', $cleanPhone)) {
             response(false, 'Қате телефон нөмірі');
        }

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) response(false, 'Бұл логин бос емес');

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $refresh_token = generate_refresh_token();

            $sql = "INSERT INTO users (login, password, user_name, user_phone, user_type, registereddate, refresh_token) VALUES (?, ?, ?, ?, 'user', NOW(), ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$login, $hash, $name, $cleanPhone, $refresh_token]);
            
            $new_user_id = $pdo->lastInsertId();
            $jwt = generate_jwt($new_user_id, 'user');
            
            response(true, 'Тіркелу сәтті өтті', [
                'user_id' => $new_user_id,
                'name' => $name,
                'user_type' => 'user',
                'token' => $jwt,
                'refresh_token' => $refresh_token
            ]);
        } catch (Exception $e) {
            response(false, 'Дерекқор қатесі: ' . $e->getMessage());
        }
    } 

    // --- ОБНОВЛЕНИЕ СЕССИИ (REFRESH TOKEN) ---
    elseif ($action === 'refresh') {
        $refresh_token = $input['refresh_token'] ?? '';
        
        if (empty($refresh_token)) response(false, 'Refresh token required', null, ['code' => 400]);

        $stmt = $pdo->prepare("SELECT user_id, user_type FROM users WHERE refresh_token = ?");
        $stmt->execute([$refresh_token]);
        $user = $stmt->fetch();

        if ($user) {
            // Генерируем новую пару
            $new_jwt = generate_jwt($user['user_id'], $user['user_type']);
            $new_refresh = generate_refresh_token();

            // Обновляем в базе (Security: старый refresh_token перестает действовать)
            $pdo->prepare("UPDATE users SET refresh_token = ?, lastonline = NOW() WHERE user_id = ?")->execute([$new_refresh, $user['user_id']]);

            response(true, 'Token refreshed', [
                'token' => $new_jwt,
                'refresh_token' => $new_refresh
            ]);
        } else {
            response(false, 'Invalid or expired refresh token. Please login again.', null, ['code' => 401]);
        }
    }
    
    // --- СБРОС ПАРОЛЯ ---
    elseif ($action === 'reset_password') {
        $login = trim($input['login'] ?? '');
        if (!$login) response(false, 'Введите логин');

        $stmt = $pdo->prepare("SELECT user_id, user_phone FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user) {
            response(false, 'Пользователь с таким логином не найден');
        }

        $new_password = rand(100000, 999999);
        $hash = password_hash((string)$new_password, PASSWORD_DEFAULT);
        
        // Инвалидируем текущие сессии (удаляем refresh_token)
        $pdo->prepare("UPDATE users SET password = ?, refresh_token = NULL WHERE user_id = ?")->execute([$hash, $user['user_id']]);

        response(true, "Сброс успешен (ДЕМО). Ваш новый пароль: $new_password", [
            'new_password' => $new_password 
        ]);
    } 
    
    else {
        response(false, 'Invalid action');
    }
} else {
    response(false, 'Method Not Allowed');
}
?>