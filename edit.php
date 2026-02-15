<?php
// edit.php - Редактирование
require_once 'templates/header.php';

if (!is_logged_in() || $_SESSION['user_type'] === 'user') {
    die("<div class='container mt-5'><div class='alert alert-danger'>Доступ запрещен!</div></div>");
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM post WHERE post_id = ?");
$stmt->execute([$post_id]);
$data = $stmt->fetch();

if (!$data || ($_SESSION['user_type'] !== 'admin' && $data['owner_id'] != $_SESSION['user_id'])) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Запись не найдена.</div></div>");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$categories = $pdo->query("SELECT * FROM categories ORDER BY cat_name")->fetchAll();
$tags = $pdo->query("SELECT * FROM tags ORDER BY attr_name")->fetchAll();

// Текущие выбранные значения из БД
$selectedCats = $pdo->prepare("SELECT cat_id FROM s_categories WHERE post_id = ?");
$selectedCats->execute([$post_id]);
$selectedCats = $selectedCats->fetchAll(PDO::FETCH_COLUMN);

$selectedTags = $pdo->prepare("SELECT attr_id FROM s_tags WHERE post_id = ?");
$selectedTags->execute([$post_id]);
$selectedTags = $selectedTags->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) die("Security Error");

    // Обновляем массив data данными из формы
    $data['title'] = trim($_POST['title']);
    $data['address'] = trim($_POST['address']);
    $data['description'] = trim($_POST['description']);

    $contacts = [
        'phone' => $_POST['phone'] ?? '',
        'whatsapp' => $_POST['whatsapp'] ?? '',
        'instagram' => $_POST['instagram'] ?? ''
    ];
    $data['contacts'] = $contacts;

    // Получаем worktime
    $worktime = $_POST['worktime'] ?? [];
    $data['worktime'] = $worktime; // Для шаблона

    $selectedCats = $_POST['categories'] ?? [];
    $selectedTags = $_POST['tags'] ?? [];

    if (mb_strlen($data['title']) < 2) $errors[] = "Название короткое.";
    if (empty($selectedCats)) $errors[] = "Выберите категорию.";

    // Фото
    $photoPath = $data['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $photoPath = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
            $data['photo'] = $photoPath; // Обновляем для вида
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            // Если админ - статус не меняем, если владелец - сбрасываем на проверку (0)
            $newStatus = ($_SESSION['user_type'] === 'admin') ? $data['status'] : 0;

            // Добавили worktime в обновление
            $sql = "UPDATE post SET title=?, description=?, address=?, photo=?, contacts=?, worktime=?, status=?, updated_at=NOW() WHERE post_id=?";
            $pdo->prepare($sql)->execute([
                $data['title'],
                $data['description'],
                $data['address'],
                $photoPath,
                json_encode($contacts, JSON_UNESCAPED_UNICODE),
                json_encode($worktime, JSON_UNESCAPED_UNICODE), // Сохраняем worktime
                $newStatus,
                $post_id
            ]);

            // Обновление связей
            $pdo->prepare("DELETE FROM s_categories WHERE post_id=?")->execute([$post_id]);
            $stmtC = $pdo->prepare("INSERT INTO s_categories (post_id, cat_id) VALUES (?,?)");
            foreach($selectedCats as $c) $stmtC->execute([$post_id, $c]);

            $pdo->prepare("DELETE FROM s_tags WHERE post_id=?")->execute([$post_id]);
            $stmtT = $pdo->prepare("INSERT INTO s_tags (post_id, attr_id) VALUES (?,?)");
            foreach($selectedTags as $t) $stmtT->execute([$post_id, $t]);

            $pdo->commit();
            $_SESSION['flash'] = "Изменения сохранены!";
            echo "<script>window.location.href='dashboard.php';</script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка: " . $e->getMessage();
        }
    }
}

// Подключаем шаблон
$pageTitle = "Редактировать заведение";
require_once 'templates/form-post.php';
require_once 'templates/footer.php';
?>