<?php
require_once 'headers.php';

$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = $_GET['sort'] ?? 'rating'; // rating, views, date, distance
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tags = isset($_GET['tags']) ? $_GET['tags'] : '';

// Параметры для гео-поиска
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$radius = isset($_GET['radius']) ? (int)$_GET['radius'] : 10; // Радиус по умолчанию 10 км

$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereClauses = ["p.status = 1"];
    $distanceSelect = "";
    $havingClause = "";

    // 1. Гео-поиск (Рассчет дистанции в км по формуле Гаверсинуса)
    if ($lat !== null && $lng !== null) {
        $distanceSelect = ", (6371 * acos(cos(radians($lat)) * cos(radians(p.latitude)) * cos(radians(p.longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(p.latitude)))) AS distance";
        $whereClauses[] = "p.latitude IS NOT NULL AND p.longitude IS NOT NULL";
        // Фильтр по радиусу (используем HAVING, так как distance - это вычисляемое поле)
        $havingClause = "HAVING distance <= $radius";
    }

    if ($cat_id > 0) {
        $cat_ids = [$cat_id];
        $stmtSub = $pdo->prepare("SELECT cat_id FROM categories WHERE cat_parent_id = ?");
        $stmtSub->execute([$cat_id]);
        $subs = $stmtSub->fetchAll(PDO::FETCH_COLUMN);
        if ($subs) $cat_ids = array_merge($cat_ids, $subs);
        $inQuery = implode(',', array_map('intval', $cat_ids));
        $whereClauses[] = "EXISTS (SELECT 1 FROM s_categories sc WHERE sc.post_id = p.post_id AND sc.cat_id IN ($inQuery))";
    }

    // ИСПРАВЛЕНИЕ: Оптимизированный поиск через FULLTEXT индекс
    if (!empty($q)) {
        // Разбиваем поисковый запрос на отдельные слова
        $words = preg_split('/\s+/', $q);
        $searchQuery = '';
        foreach ($words as $word) {
            if (mb_strlen($word) > 0) {
                // Плюс (+) означает, что слово обязательно. Звездочка (*) ищет совпадения по началу слова (префиксу).
                $searchQuery .= '+' . $word . '* ';
            }
        }
        $searchQuery = trim($searchQuery);
        
        // Если после фильтрации запрос не пуст, выполняем MATCH AGAINST
        if (!empty($searchQuery)) {
            $whereClauses[] = "MATCH(p.title, p.psevdonim) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $searchQuery;
        }
    }

    if (!empty($tags)) {
        $tagIds = array_map('intval', explode(',', $tags));
        $tagCount = count($tagIds);
        $tagStr = implode(',', $tagIds);
        $whereClauses[] = "p.post_id IN (SELECT st.post_id FROM s_tags st WHERE st.attr_id IN ($tagStr) GROUP BY st.post_id HAVING COUNT(DISTINCT st.attr_id) = $tagCount)";
    }

    $whereSql = implode(' AND ', $whereClauses);

    // Подсчет общего количества (учитывая радиус)
    $countSql = "SELECT COUNT(*) FROM (SELECT p.post_id $distanceSelect FROM post p WHERE $whereSql $havingClause) AS tmp";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalPosts = $stmtCount->fetchColumn();
    $totalPages = ceil($totalPosts / $limit);

    // Основной запрос
    $sql = "SELECT p.post_id, p.title, p.address, p.photo, p.rating_avg, p.rating_count, p.views, p.worktime, p.attributes $distanceSelect 
            FROM post p 
            WHERE $whereSql 
            $havingClause ";

    switch ($sort) {
        case 'distance':
            if ($lat !== null && $lng !== null) $sql .= "ORDER BY distance ASC ";
            else $sql .= "ORDER BY p.rating_avg DESC ";
            break;
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

    $data = [];
    $currentDay = strtolower(date('D'));

    foreach ($posts as $post) {
        $photoUrl = (strpos($post['photo'], 'http') === 0) ? $post['photo'] : $baseUrl . '/' . $post['photo'];
        $attrs = json_decode($post['attributes'], true);
        $priceSign = '₸';
        if (isset($attrs['avg_check'])) {
            $check = (int)$attrs['avg_check'];
            if ($check > 2500) $priceSign = '₸₸';
            if ($check > 7000) $priceSign = '₸₸₸';
        }

        $isOpen = false;
        $worktime = json_decode($post['worktime'], true);
        if ($worktime && isset($worktime[$currentDay]) && $worktime[$currentDay] !== 'closed') {
            $times = explode('-', $worktime[$currentDay]);
            if (count($times) == 2) {
                $now = time();
                $start = strtotime($times[0]);
                $end = strtotime($times[1]);
                if ($end < $start) $end += 24 * 3600; 
                if ($now >= $start && $now <= $end) $isOpen = true;
            }
        }

        $item = [
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

        // Добавляем дистанцию в ответ, если искали по координатам
        if (isset($post['distance'])) {
            $item['distance_km'] = round((float)$post['distance'], 2);
        }

        $data[] = $item;
    }

    response(true, 'Posts loaded', $data, [
        'pagination' => [
            'total_items' => $totalPosts,
            'total_pages' => $totalPages,
            'current_page' => $page
        ]
    ]);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>