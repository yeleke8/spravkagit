<?php
// register.php - Регистрация нового пользователя
require_once 'back/db.php';
require_once 'back/functions.php';

// Если уже авторизован - отправляем в кабинет
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$old = ['name' => '', 'login' => '', 'phone' => ''];

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка безопасности (CSRF). Обновите страницу.");
    }

    $name = trim($_POST['name']);
    $login = trim($_POST['login']);
    $phone = trim($_POST['phone']);
    $pass = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    // Сохраняем введенные данные, чтобы вернуть их в форму при ошибке
    $old['name'] = $name;
    $old['login'] = $login;
    $old['phone'] = $phone;

    // Валидация
    if (mb_strlen($name) < 2) $errors[] = "Имя должно быть не короче 2 символов.";
    if (mb_strlen($login) < 4) $errors[] = "Логин должен быть не короче 4 символов.";
    if (mb_strlen($pass) < 6) $errors[] = "Пароль должен быть не короче 6 символов.";
    if ($pass !== $pass_confirm) $errors[] = "Пароли не совпадают.";
    
    // Проверка на уникальность логина
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE login = ?");
    $stmt->execute([$login]);
    if ($stmt->fetch()) {
        $errors[] = "Такой логин уже занят.";
    }

    // Если ошибок нет - регистрируем
    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (login, password, user_name, user_phone, user_type) VALUES (?, ?, ?, ?, 'user')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$login, $hash, $name, $phone])) {
            // Сразу авторизуем или отправляем на вход
            $_SESSION['flash'] = "Регистрация успешна! Теперь вы можете войти.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Ошибка базы данных.";
        }
    }
}

require_once 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 rounded-3 mt-4">
            <div class="card-body p-5">
                <h3 class="text-center fw-bold mb-4">Регистрация</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= h($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Ваше Имя</label>
                        <input type="text" name="name" class="form-control" value="<?= h($old['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Телефон</label>
                        <input type="text" name="phone" class="form-control" value="<?= h($old['phone']) ?>" placeholder="+7..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Логин (для входа)</label>
                        <input type="text" name="login" class="form-control" value="<?= h($old['login']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Повтор пароля</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">Зарегистрироваться</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-3">
                Уже есть аккаунт? <a href="login.php" class="text-decoration-none fw-bold">Войти</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>