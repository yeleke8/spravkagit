<?php
// add.php - Добавление заведения
require_once 'templates/header.php';

if (!is_logged_in() || $_SESSION['user_type'] === 'user') {
    die("<div class='container mt-5'><div class='alert alert-danger'>Доступ запрещен!</div></div>");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
// Инициализируем пустые массивы для шаблона
$data = []; 
$selectedCats = [];
$selectedTags = [];

// Справочники
$categories = $pdo->query("SELECT * FROM categories ORDER BY cat_name")->fetchAll();
$tags = $pdo->query("SELECT * FROM tags ORDER BY attr_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) die("Security Error");

    // Собираем данные формы
    $data = [
        'title' => trim($_POST['title']),
        'address' => trim($_POST['address']),
        'description' => trim($_POST['description']),
        'contacts' => [
            'phone' => $_POST['phone'] ?? '',
            'whatsapp' => $_POST['whatsapp'] ?? '',
            'instagram' => $_POST['instagram'] ?? ''
        ],
        'worktime' => $_POST['worktime'] ?? [] // Получаем массив времени
    ];
    $selectedCats = $_POST['categories'] ?? [];
    $selectedTags = $_POST['tags'] ?? [];

    // Валидация
    if (mb_strlen($data['title']) < 2) $errors[] = "Название слишком короткое.";
    if (empty($selectedCats)) $errors[] = "Выберите категорию.";

    // Загрузка фото
    $photoPath = 'uploads/default.jpg';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $photoPath = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
        } else {
            $errors[] = "Неверный формат фото.";
        }
    }

    if (empty($errors)) {
        // Генерация Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title'])));
        if(empty($slug)) $slug = 'post-'.time();

        $pdo->beginTransaction();
        try {
            // Добавили поле worktime в SQL запрос
            $sql = "INSERT INTO post (title, slug, description, address, photo, contacts, worktime, owner_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'],
                $slug,
                $data['description'],
                $data['address'],
                $photoPath,
                json_encode($data['contacts'], JSON_UNESCAPED_UNICODE),
                json_encode($data['worktime'], JSON_UNESCAPED_UNICODE), // Сохраняем worktime как JSON
                $_SESSION['user_id']
            ]);
            $post_id = $pdo->lastInsertId();

            // Категории
            $stmtCat = $pdo->prepare("INSERT INTO s_categories (post_id, cat_id) VALUES (?, ?)");
            foreach($selectedCats as $cid) $stmtCat->execute([$post_id, $cid]);

            // Теги
            $stmtTag = $pdo->prepare("INSERT INTO s_tags (post_id, attr_id) VALUES (?, ?)");
            foreach($selectedTags as $tid) $stmtTag->execute([$post_id, $tid]);

            $pdo->commit();
            
            $_SESSION['flash'] = "Заведение добавлено и отправлено на модерацию!";
            echo "<script>window.location.href='dashboard.php';</script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка БД: " . $e->getMessage();
        }
    }
}

// Подключаем шаблон формы
$pageTitle = "Добавить новое заведение";
require_once 'templates/form-post.php';
require_once 'templates/footer.php';
?>