<?php
require_once 'headers.php';

// GET параметрлерін аламыз
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = $_GET['sort'] ?? 'rating'; // rating немесе date
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$limit = 10; 
$offset = ($page - 1) * $limit;

try {
    // 1. Егер категория таңдалса, оның балаларын (subcategories) тауып аламыз
    $cat_ids = [];
    if ($cat_id > 0) {
        $cat_ids[] = $cat_id; // Өзін қосамыз
        
        // Ішкі категорияларды іздейміз
        $stmtSub = $pdo->prepare("SELECT cat_id FROM categories WHERE cat_parent_id = ?");
        $stmtSub->execute([$cat_id]);
        $subs = $stmtSub->fetchAll(PDO::FETCH_COLUMN);
        
        if ($subs) {
            $cat_ids = array_merge($cat_ids, $subs);
        }
    }
    
    // ID-ларды SQL сұранысқа дайындаймыз (мысалы: 1,5,6)
    $inQuery = implode(',', array_map('intval', $cat_ids));

    // Негізгі сұраныс
    $sql = "SELECT p.post_id, p.title, p.address, p.photo, p.rating_avg, p.rating_count, p.worktime, p.attributes 
            FROM post p ";
    $params = [];

    // JOIN қосу
    if ($cat_id > 0) {
        $sql .= "JOIN s_categories sc ON p.post_id = sc.post_id ";
    }

    $sql .= "WHERE p.status = 1 "; 

    // Категория фильтрі (IN операторын қолданамыз)
    if ($cat_id > 0) {
        $sql .= "AND sc.cat_id IN ($inQuery) ";
        // $params-қа ештеңе қоспаймыз, себебі ID-ларды жоғарыда тікелей жаздық
    }

    // Іздеу
    if (!empty($q)) {
        $sql .= "AND (p.title LIKE ? OR p.psevdonim LIKE ?) ";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    $sql .= "GROUP BY p.post_id ";

    // Сұрыптау
    if ($sort === 'date') {
        $sql .= "ORDER BY p.created_at DESC ";
    } else {
        $sql .= "ORDER BY p.rating_avg DESC, p.rating_count DESC ";
    }

    $sql .= "LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Деректерді өңдеу
    $data = [];
    foreach ($posts as $post) {
        $photoUrl = (strpos($post['photo'], 'http') === 0) ? $post['photo'] : $baseUrl . '/' . $post['photo'];

        $attrs = json_decode($post['attributes'], true);
        $priceSign = '₸';
        if (isset($attrs['avg_check'])) {
            $check = (int)$attrs['avg_check'];
            if ($check > 2500) $priceSign = '₸₸';
            if ($check > 7000) $priceSign = '₸₸₸';
        }

        $data[] = [
            'id' => $post['post_id'],
            'title' => $post['title'],
            'address' => $post['address'],
            'photo' => $photoUrl,
            'rating' => (float)$post['rating_avg'],
            'reviews_count' => $post['rating_count'],
            'price_sign' => $priceSign
        ];
    }

    // Тіпті бос болса да, success=true қайтарамыз (қате емес қой)
    response(true, 'Posts loaded', $data);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>