<?php
require_once 'headers.php';

try {
    // ОДИН запрос для получения ВСЕХ категорий сразу (решение проблемы N+1 запроса)
    // Сортируем так, чтобы сначала шли родители (NULL), а затем дети
    $stmt = $pdo->query("SELECT cat_id, cat_name, cat_slug, cat_parent_id FROM categories ORDER BY cat_parent_id ASC, cat_id ASC");
    $allCategories = $stmt->fetchAll();

    $result = [];
    $subCategories = [];

    // Разделяем на главные категории и подкатегории
    foreach ($allCategories as $cat) {
        if ($cat['cat_parent_id'] === null) {
            $result[$cat['cat_id']] = [
                'id' => $cat['cat_id'],
                'name' => $cat['cat_name'],
                'slug' => $cat['cat_slug'],
                'subcategories' => []
            ];
        } else {
            $subCategories[] = $cat;
        }
    }

    // Прикрепляем подкатегории к их родителям (быстрая операция в оперативной памяти сервера)
    foreach ($subCategories as $sub) {
        $parentId = $sub['cat_parent_id'];
        if (isset($result[$parentId])) {
            $result[$parentId]['subcategories'][] = [
                'id' => $sub['cat_id'],
                'name' => $sub['cat_name'],
                'slug' => $sub['cat_slug']
            ];
        }
    }

    // Сбрасываем ключи ассоциативного массива, чтобы на выходе получился правильный JSON-массив [...]
    $finalResult = array_values($result);

    response(true, 'Категориялар', $finalResult);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>