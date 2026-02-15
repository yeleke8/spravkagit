<?php
// login.php - Вход в систему
require_once 'back/db.php';
require_once 'back/functions.php';

// Логика выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Если уже авторизован
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$login_val = '';

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка безопасности. Обновите страницу.");
    }

    $login_val = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$login_val || !$password) {
        $error = 'Введите логин и пароль';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login_val]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Успешный вход
            session_regenerate_id(true); // Защита от фиксации сессии
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['user_name'];

            // Обновляем время последнего входа
            $pdo->prepare("UPDATE users SET lastonline = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}

require_once 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-success shadow-sm">
                <?= h($_SESSION['flash']) ?>
                <?php unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-lg border-0 rounded-3 mt-5">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Вход</h3>
                    <p class="text-muted">Рады видеть вас снова!</p>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Логин</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" name="login" class="form-control" value="<?= h($login_val) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Пароль</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Войти</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-3">
                Нет аккаунта? <a href="register.php" class="text-decoration-none fw-bold">Зарегистрироваться</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>