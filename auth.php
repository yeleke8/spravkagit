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
            $pdo->prepare("UPDATE users SET lastonline = NOW() WHERE user_id = ?")->execute([$user['user_id']]);
            
            response(true, 'Сәтті кірдіңіз', [
                'user_id' => $user['user_id'],
                'name' => $user['user_name'],
                'user_type' => $user['user_type'],
                'token' => $jwt
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
            $sql = "INSERT INTO users (login, password, user_name, user_phone, user_type, registereddate) VALUES (?, ?, ?, ?, 'user', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$login, $hash, $name, $cleanPhone]);
            
            $new_user_id = $pdo->lastInsertId();
            $jwt = generate_jwt($new_user_id, 'user');
            
            response(true, 'Тіркелу сәтті өтті', [
                'token' => $jwt,
                'name' => $name,
                'user_type' => 'user'
            ]);
        } catch (Exception $e) {
            response(false, 'Дерекқор қатесі: ' . $e->getMessage());
        }
    } 
    
    // --- СБРОС ПАРОЛЯ (RESET PASSWORD STUB) ---
    elseif ($action === 'reset_password') {
        $login = trim($input['login'] ?? '');
        if (!$login) response(false, 'Введите логин');

        $stmt = $pdo->prepare("SELECT user_id, user_phone FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user) {
            response(false, 'Пользователь с таким логином не найден');
        }

        // В реальном проекте здесь вы отправляете SMS с кодом или email.
        // Так как инфраструктуры для SMS нет, генерируем временный пароль и отдаем в ответ (для теста).
        $new_password = rand(100000, 999999);
        $hash = password_hash((string)$new_password, PASSWORD_DEFAULT);
        
        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hash, $user['user_id']]);

        response(true, "Сброс успешен (ДЕМО). Ваш новый пароль: $new_password", [
            'new_password' => $new_password // В продакшене НИКОГДА не возвращайте пароль в API, отправляйте по SMS!
        ]);
    } 
    
    else {
        response(false, 'Invalid action');
    }
} else {
    response(false, 'Method Not Allowed');
}
?>