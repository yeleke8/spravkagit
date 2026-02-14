<?php
// back/functions.php - Основные функции для работы с БД

// Получить главные категории (без родителей)
function getParentCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories WHERE cat_parent_id IS NULL");
    return $stmt->fetchAll();
}

// Получить последние добавленные заведения
function getLatestPosts($pdo, $limit = 6) {
    $stmt = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY created_at DESC LIMIT $limit");
    return $stmt->fetchAll();
}


// Получить заведение по slug
function getPostBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM post WHERE slug = ? AND status = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Проверка авторизации пользователя
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Безопасный вывод данных
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// --- НОВЫЕ ФУНКЦИИ ДЛЯ ВРЕМЕНИ РАБОТЫ ---

// 1. Получить название дня недели
function getDayName($key) {
    $days = [
        'mon' => 'Понедельник',
        'tue' => 'Вторник',
        'wed' => 'Среда',
        'thu' => 'Четверг',
        'fri' => 'Пятница',
        'sat' => 'Суббота',
        'sun' => 'Воскресенье'
    ];
    return $days[$key] ?? $key;
}

// 2. Получить статус (Открыто/Закрыто)
function getWorkStatus($worktimeJson) {
    $schedule = json_decode($worktimeJson, true);
    if (!$schedule) return ['status' => 'unknown', 'text' => 'График не указан', 'color' => 'secondary'];

    // Определяем текущий день недели (mon, tue...)
    $today = strtolower(date('D'));
    // PHP date('D') возвращает Mon, Tue... нам нужно lowercase

    // Если в JSON ключи полные (mon, tue), то ок.
    // Если вдруг сегодня 'Thu', а в базе 'thu', приводим к нижнему регистру.

    $timeRange = $schedule[$today] ?? 'closed';

    if ($timeRange === 'closed' || empty($timeRange)) {
        return ['status' => 'closed', 'text' => 'Сегодня закрыто', 'color' => 'danger'];
    }

    // Парсим время "09:00-21:00"
    $parts = explode('-', $timeRange);
    if (count($parts) !== 2) return ['status' => 'unknown', 'text' => $timeRange, 'color' => 'secondary'];

    $start = strtotime(trim($parts[0]));
    $end = strtotime(trim($parts[1]));
    $now = time();

    // Обработка перехода через полночь (например, 18:00-02:00)
    if ($end < $start) {
        $end += 24 * 60 * 60; // Добавляем 24 часа к времени закрытия
        if ($now < $start) $now += 24 * 60 * 60; // Корректируем текущее время если нужно
    }

    if ($now >= $start && $now <= $end) {
        return ['status' => 'open', 'text' => 'Открыто до ' . trim($parts[1]), 'color' => 'success'];
    } else {
        return ['status' => 'closed', 'text' => 'Закрыто', 'color' => 'danger'];
    }
}

// --- ГЕНЕРАТОР АВАТАРОК ---
function getInitialsAvatar($name) {
    $colors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#198754', '#20c997', '#0dcaf0'];

    // Берем первую букву
    $initial = mb_substr($name, 0, 1, "UTF-8");

    // Выбираем цвет на основе имени (чтобы у одного юзера всегда был один цвет)
    $colorIndex = crc32($name) % count($colors);
    $bg = $colors[$colorIndex];

    return '<div class="avatar-initials shadow-sm" style="background-color: ' . $bg . ';">' . $initial . '</div>';
}

// --- ПОЛУЧЕНИЕ IP АДРЕСА ---
function getRealIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
?>