<?php
require_once 'headers.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // --- КІРУ (LOGIN) ---
    if ($action === 'login') {
        try {
            $login = trim($input['login'] ?? '');
            $password = $input['password'] ?? '';

            if (!$login || !$password) response(false, 'Логин және пароль енгізіңіз');

            $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch();

            if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                $jwt = generate_jwt($user['user_id'], $user['user_type']);
                $refresh_token = generate_refresh_token();

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
        } catch (Exception $e) {
            log_server_error($e, 'Login API');
            response(false, 'Внутренняя ошибка сервера. Повторите позже.');
        }
    }

    // --- АВТОРИЗАЦИЯ ЧЕРЕЗ GOOGLE ---
    elseif ($action === 'google_login') {
        try {
            $google_token = $input['google_token'] ?? '';
            $agree_policy = isset($input['agree_policy']) ? (bool)$input['agree_policy'] : false;

            if (empty($google_token)) response(false, 'Google token required');

            // Проверяем токен через Google API (в Production лучше использовать Google Client Library)
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $google_token;
            $responseJson = @file_get_contents($url);
            $googleData = $responseJson ? json_decode($responseJson, true) : null;

            if (!$googleData || isset($googleData['error'])) {
                response(false, 'Неверный токен Google. Авторизация отклонена.');
            }

            $google_id = $googleData['sub']; // Уникальный ID пользователя Google
            $email = $googleData['email'] ?? null;
            $name = $googleData['name'] ?? 'Google User';

            // Ищем пользователя по google_id или email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR (email = ? AND email IS NOT NULL)");
            $stmt->execute([$google_id, $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Если юзер есть, но google_id пустой (совпадение по email) - привязываем аккаунт
                if (empty($user['google_id'])) {
                    $pdo->prepare("UPDATE users SET google_id = ? WHERE user_id = ?")->execute([$google_id, $user['user_id']]);
                }
                $user_id = $user['user_id'];
                $user_type = $user['user_type'];
            } else {
                // Регистрируем нового пользователя Google
                if (!$agree_policy) response(false, 'Необходимо согласие с политикой конфиденциальности');

                $login = 'g_' . substr(md5($google_id), 0, 10); // Генерируем уникальный логин
                $refresh_token = generate_refresh_token();

                $sql = "INSERT INTO users (login, user_name, email, google_id, user_type, registereddate, refresh_token, policy_accepted) VALUES (?, ?, ?, ?, 'user', NOW(), ?, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$login, $name, $email, $google_id, $refresh_token]);
                
                $user_id = $pdo->lastInsertId();
                $user_type = 'user';
            }

            // Генерируем сессию
            $jwt = generate_jwt($user_id, $user_type);
            $new_refresh = generate_refresh_token();
            $pdo->prepare("UPDATE users SET lastonline = NOW(), refresh_token = ? WHERE user_id = ?")->execute([$new_refresh, $user_id]);

            response(true, 'Успешный вход через Google', [
                'user_id' => $user_id,
                'name' => $name,
                'user_type' => $user_type,
                'token' => $jwt,
                'refresh_token' => $new_refresh
            ]);

        } catch (Exception $e) {
            log_server_error($e, 'Google Login API');
            response(false, 'Ошибка авторизации Google. Попробуйте позже.');
        }
    }

    // --- ТІРКЕЛУ (REGISTER) ---
    elseif ($action === 'register') {
        try {
            $name = trim($input['name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $login = trim($input['login'] ?? '');
            $password = $input['password'] ?? '';
            $agree_policy = isset($input['agree_policy']) ? (bool)$input['agree_policy'] : false;

            if (!$agree_policy) response(false, 'Необходимо согласие с Пользовательским соглашением и Политикой конфиденциальности');
            if (mb_strlen($login) < 4) response(false, 'Логин тым қысқа');
            if (mb_strlen($password) < 6) response(false, 'Пароль тым қысқа');

            $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
            if (!preg_match('/^\+?[0-9]{10,15}$/', $cleanPhone)) {
                 response(false, 'Қате телефон нөмірі');
            }

            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE login = ?");
            $stmt->execute([$login]);
            if ($stmt->fetch()) response(false, 'Бұл логин бос емес (Этот логин уже занят)');

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $refresh_token = generate_refresh_token();

            $sql = "INSERT INTO users (login, password, user_name, user_phone, user_type, registereddate, refresh_token, policy_accepted) VALUES (?, ?, ?, ?, 'user', NOW(), ?, 1)";
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
            log_server_error($e, 'Register API');
            response(false, 'Ошибка регистрации. Попробуйте позже.');
        }
    } 

    // --- ОБНОВЛЕНИЕ СЕССИИ (REFRESH TOKEN) ---
    elseif ($action === 'refresh') {
        try {
            $refresh_token = $input['refresh_token'] ?? '';
            
            if (empty($refresh_token)) response(false, 'Refresh token required', null, ['code' => 400]);

            $stmt = $pdo->prepare("SELECT user_id, user_type FROM users WHERE refresh_token = ?");
            $stmt->execute([$refresh_token]);
            $user = $stmt->fetch();

            if ($user) {
                $new_jwt = generate_jwt($user['user_id'], $user['user_type']);
                $new_refresh = generate_refresh_token();

                $pdo->prepare("UPDATE users SET refresh_token = ?, lastonline = NOW() WHERE user_id = ?")->execute([$new_refresh, $user['user_id']]);

                response(true, 'Token refreshed', [
                    'token' => $new_jwt,
                    'refresh_token' => $new_refresh
                ]);
            } else {
                response(false, 'Invalid or expired refresh token. Please login again.', null, ['code' => 401]);
            }
        } catch (Exception $e) {
            log_server_error($e, 'Refresh Token API');
            response(false, 'Ошибка обновления сессии.');
        }
    }
    
    // --- СБРОС ПАРОЛЯ ---
    elseif ($action === 'reset_password') {
        // ... [Остается без изменений из твоего текущего кода] ...
        try {
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
            $pdo->prepare("UPDATE users SET password = ?, refresh_token = NULL WHERE user_id = ?")->execute([$hash, $user['user_id']]);

            error_log("[" . date('Y-m-d H:i:s') . "] СБРОС ПАРОЛЯ - Логин: {$login} | Новый пароль: {$new_password}\n", 3, __DIR__ . '/../passwords_test.log');

            response(true, "Новый пароль успешно сгенерирован и отправлен на ваш номер телефона.");
            
        } catch (Exception $e) {
            log_server_error($e, 'Reset Password API');
            response(false, 'Произошла ошибка при сбросе пароля. Повторите попытку позже.');
        }
    } 
    
    else {
        response(false, 'Invalid action');
    }
} else {
    response(false, 'Method Not Allowed');
}
?>