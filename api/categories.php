<?php
require_once 'headers.php';

try {
    // Бас категорияларды алу
    $stmt = $pdo->query("SELECT cat_id, cat_name, cat_slug, cat_parent_id FROM categories WHERE cat_parent_id IS NULL ORDER BY cat_id ASC");
    $parents = $stmt->fetchAll();

    $result = [];
    foreach ($parents as $parent) {
        // Подкатегорияларды алу
        $stmtSub = $pdo->prepare("SELECT cat_id, cat_name, cat_slug FROM categories WHERE cat_parent_id = ?");
        $stmtSub->execute([$parent['cat_id']]);
        $subs = $stmtSub->fetchAll();

        // Иконкаларды анықтау (Android үшін)
        $icon = "ic_default"; // Android-тағы drawable аты
        // Мұнда logic қосуға болады: if ($parent['cat_slug'] == 'food') $icon = 'ic_food';

        $result[] = [
            'id' => $parent['cat_id'],
            'name' => $parent['cat_name'],
            'slug' => $parent['cat_slug'],
            'subcategories' => $subs
        ];
    }

    response(true, 'Категориялар', $result);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>