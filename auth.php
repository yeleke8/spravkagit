<?php
require_once 'headers.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Android-тан келетін JSON деректерді оқу
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
            // Жаңа Token генерациялау
            $newToken = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE users SET api_key = ? WHERE user_id = ?")->execute([$newToken, $user['user_id']]);
            
            response(true, 'Сәтті кірдіңіз', [
                'user_id' => $user['user_id'],
                'name' => $user['user_name'],
                'token' => $newToken,
                'avatar' => null // Егер аватар болса қосуға болады
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

        // Логин тексеру
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) response(false, 'Бұл логин бос емес');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        try {
            $sql = "INSERT INTO users (login, password, user_name, user_phone, user_type, api_key, registereddate) VALUES (?, ?, ?, ?, 'user', ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$login, $hash, $name, $phone, $token]);
            
            response(true, 'Тіркелу сәтті өтті', [
                'token' => $token,
                'name' => $name
            ]);
        } catch (Exception $e) {
            response(false, 'Дерекқор қатесі');
        }
    }
}
response(false, 'Action required');
?>