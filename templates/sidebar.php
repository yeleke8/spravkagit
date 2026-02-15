<?php
// templates/sidebar.php
// Внимание: Этот файл должен быть обернут в col-lg-3 или col-lg-2 в родительском файле!

$currentPage = basename($_SERVER['PHP_SELF']);
$currentSlug = $_GET['slug'] ?? '';

// Иконки
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

if (!isset($sidebarCategories)) {
    $stmtSidebar = $pdo->query("SELECT * FROM categories WHERE cat_parent_id IS NULL ORDER BY cat_id ASC");
    $sidebarCategories = $stmtSidebar->fetchAll();
}
?>

<div class="card border-0 shadow-sm p-3 sticky-top" style="top: 100px; z-index: 900;">
    <h6 class="text-uppercase text-muted fw-bold small mb-3 px-2 ls-1">Категории</h6>
    
    <div class="list-group list-group-flush">
        <a href="search.php" class="list-group-item list-group-item-action d-flex align-items-center">
            <i class="fa-solid fa-layer-group me-3" style="width: 20px;"></i> Все места
        </a>
        
        <?php foreach ($sidebarCategories as $cat):
            $icon = $catIcons[$cat['cat_slug']] ?? 'fa-circle';
            $isActive = ($currentSlug === $cat['cat_slug']);
        ?>
        <a href="/category/<?= h($cat['cat_slug']) ?>" 
           class="list-group-item list-group-item-action d-flex align-items-center <?= $isActive ? 'active' : '' ?>">
            <i class="fa-solid <?= $icon ?> me-3" style="width: 20px; text-align: center;"></i> 
            <?= h($cat['cat_name']) ?>
            <?php if($isActive): ?>
                <i class="fa-solid fa-chevron-right ms-auto small"></i>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <div class="mt-4 p-3 bg-light rounded-3 text-center border border-dashed">
        <p class="small text-muted mb-2">Владелец бизнеса?</p>
        <a href="/add.php" class="btn btn-outline-primary btn-sm w-100 fw-bold">Добавить место</a>
    </div>
</div>