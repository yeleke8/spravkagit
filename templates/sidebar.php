<?php
// templates/sidebar.php - Единая боковая панель для всех страниц

// Получаем текущее имя файла для подсветки активных пунктов меню
$currentPage = basename($_SERVER['PHP_SELF']);
$currentSort = $_GET['sort'] ?? '';

// Иконки для категорий
$catIcons = [
    'food' => 'fa-utensils',
    'beauty-sport' => 'fa-spa',
    'health' => 'fa-heart-pulse',
    'education' => 'fa-graduation-cap',
    'entertainment' => 'fa-ticket',
    'shops' => 'fa-shopping-bag',
    'services' => 'fa-wrench',
    'tourism' => 'fa-hotel',
    'government-social-services' => 'fa-landmark',
    'transport-logistics' => 'fa-taxi'
];

// Получаем категории (родительские), если они еще не получены
if (!isset($sidebarCategories)) {
    $stmtSidebar = $pdo->query("SELECT * FROM categories WHERE cat_parent_id IS NULL ORDER BY cat_id ASC");
    $sidebarCategories = $stmtSidebar->fetchAll();
}
?>

<!-- Используем col-md-3, чтобы совпадать с сеткой на остальных страницах (post.php, category.php) -->
<div class="col-lg-3 col-md-4 mb-4">



    <!-- 3. КАТЕГОРИИ -->
    <div class="list-group shadow-sm mb-4">
        <div class="list-group-item bg-white fw-bold text-uppercase small text-muted py-3">
            Категории
        </div>
        <?php foreach ($sidebarCategories as $cat):
            $icon = $catIcons[$cat['cat_slug']] ?? 'fa-layer-group';
            // Проверка активности категории (если мы на странице category.php)
            $isActiveCat = (isset($_GET['slug']) && $_GET['slug'] === $cat['cat_slug']);
        ?>
        <a href="category.php?slug=<?= h($cat['cat_slug']) ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?= $isActiveCat ? 'active' : '' ?>">
            <span>
                <i class="fa-solid <?= $icon ?> me-2 <?= $isActiveCat ? '' : 'text-secondary opacity-75' ?>" style="width: 20px; text-align: center;"></i> 
                <?= h($cat['cat_name']) ?>
            </span>
            <i class="fa-solid fa-angle-right small opacity-50"></i>
        </a>
        <?php endforeach; ?>
    </div>

</div>