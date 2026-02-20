<?php
require_once 'headers.php';

// --- ВХОДНЫЕ ПАРАМЕТРЫ ---
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = $_GET['sort'] ?? 'rating'; // rating, views, date
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tags = isset($_GET['tags']) ? $_GET['tags'] : ''; // Пример: "1,4,5" (ID тегов через запятую)

$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereClauses = ["p.status = 1"];

    // 1. Фильтр по категории (и подкатегориям)
    if ($cat_id > 0) {
        // Получаем ID всех подкатегорий
        $cat_ids = [$cat_id];
        $stmtSub = $pdo->prepare("SELECT cat_id FROM categories WHERE cat_parent_id = ?");
        $stmtSub->execute([$cat_id]);
        $subs = $stmtSub->fetchAll(PDO::FETCH_COLUMN);
        if ($subs) $cat_ids = array_merge($cat_ids, $subs);
        
        $inQuery = implode(',', array_map('intval', $cat_ids));
        
        // Используем EXISTS для производительности вместо JOIN всей таблицы
        $whereClauses[] = "EXISTS (
            SELECT 1 FROM s_categories sc 
            WHERE sc.post_id = p.post_id AND sc.cat_id IN ($inQuery)
        )";
    }

    // 2. Поиск (Название или Псевдонимы)
    if (!empty($q)) {
        $whereClauses[] = "(p.title LIKE ? OR p.psevdonim LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    // 3. Фильтр по удобствам (Tags: Wi-Fi, Kaspi Red и т.д.)
    if (!empty($tags)) {
        $tagIds = array_map('intval', explode(',', $tags));
        $tagCount = count($tagIds);
        $tagStr = implode(',', $tagIds);
        
        // Хитрая выборка: находим посты, у которых есть ВСЕ запрошенные теги
        $whereClauses[] = "p.post_id IN (
            SELECT st.post_id 
            FROM s_tags st 
            WHERE st.attr_id IN ($tagStr) 
            GROUP BY st.post_id 
            HAVING COUNT(DISTINCT st.attr_id) = $tagCount
        )";
    }

    // СБОРКА SQL
    $whereSql = implode(' AND ', $whereClauses);

    // Сначала считаем общее количество (для пагинации)
    $countSql = "SELECT COUNT(*) FROM post p WHERE $whereSql";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalPosts = $stmtCount->fetchColumn();
    $totalPages = ceil($totalPosts / $limit);

    // Основной запрос
    $sql = "SELECT p.post_id, p.title, p.address, p.photo, p.rating_avg, p.rating_count, p.views, p.worktime, p.attributes 
            FROM post p 
            WHERE $whereSql ";

    // Сортировка
    switch ($sort) {
        case 'date':
            $sql .= "ORDER BY p.created_at DESC ";
            break;
        case 'views':
            $sql .= "ORDER BY p.views DESC ";
            break;
        case 'rating':
        default:
            $sql .= "ORDER BY p.rating_avg DESC, p.rating_count DESC ";
            break;
    }

    $sql .= "LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Обработка данных
    $data = [];
    $currentDay = strtolower(date('D')); // mon, tue, wed...

    foreach ($posts as $post) {
        $photoUrl = (strpos($post['photo'], 'http') === 0) ? $post['photo'] : $baseUrl . '/' . $post['photo'];

        // Ценник
        $attrs = json_decode($post['attributes'], true);
        $priceSign = '₸';
        if (isset($attrs['avg_check'])) {
            $check = (int)$attrs['avg_check'];
            if ($check > 2500) $priceSign = '₸₸';
            if ($check > 7000) $priceSign = '₸₸₸';
        }

        // Статус "Открыто/Закрыто"
        $isOpen = false;
        $worktime = json_decode($post['worktime'], true);
        if ($worktime && isset($worktime[$currentDay])) {
            $timeRange = $worktime[$currentDay]; // "09:00-21:00" или "closed"
            if ($timeRange !== 'closed') {
                $times = explode('-', $timeRange);
                if (count($times) == 2) {
                    $now = time();
                    $start = strtotime($times[0]);
                    $end = strtotime($times[1]);
                    // Обработка перехода через полночь (например 18:00-02:00)
                    if ($end < $start) $end += 24 * 3600; 
                    if ($now >= $start && $now <= $end) {
                        $isOpen = true;
                    }
                }
            }
        }

        $data[] = [
            'id' => $post['post_id'],
            'title' => $post['title'],
            'address' => $post['address'],
            'photo' => $photoUrl,
            'rating' => (float)$post['rating_avg'],
            'reviews_count' => $post['rating_count'],
            'views' => $post['views'],
            'price_sign' => $priceSign,
            'is_open' => $isOpen
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Posts loaded',
        'data' => $data,
        'pagination' => [
            'total_items' => $totalPosts,
            'total_pages' => $totalPages,
            'current_page' => $page
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>