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
    
    <!-- 1. БЛОК ПОЛЬЗОВАТЕЛЯ (Только для авторизованных) -->
    <?php if (is_logged_in()): ?>
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-3 shadow-sm" style="width: 42px; height: 42px;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div style="min-width: 0;">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Вы вошли как</small>
                    <div class="fw-bold text-truncate text-dark">
                        <?= h($_SESSION['user_name']) ?>
                    </div>
                </div>
            </div>
            
            <div class="list-group list-group-flush bg-transparent small rounded-3">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent px-2 border-0 <?= $currentPage == 'dashboard.php' ? 'fw-bold text-primary' : '' ?>">
                    <i class="fa-solid fa-gauge me-2 text-secondary"></i> Личный кабинет
                </a>

                <?php if($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'owner'): ?>
                    <a href="add.php" class="list-group-item list-group-item-action bg-transparent px-2 border-0 <?= $currentPage == 'add.php' ? 'fw-bold text-primary' : '' ?>">
                        <i class="fa-solid fa-plus-circle me-2 text-success"></i> Добавить место
                    </a>
                <?php endif; ?>

                <a href="login.php?logout=1" class="list-group-item list-group-item-action bg-transparent px-2 border-0 text-danger">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Выход
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. ГЛАВНОЕ МЕНЮ -->
    <div class="list-group mb-4 shadow-sm">
        <div class="list-group-item bg-white fw-bold text-uppercase small text-muted py-3">
            Меню
        </div>
        <a href="index.php" class="list-group-item list-group-item-action <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-house me-2 <?= ($currentPage == 'index.php') ? '' : 'text-secondary' ?>"></i> Главная
        </a>
        <a href="search.php?sort=rating" class="list-group-item list-group-item-action <?= ($currentPage == 'search.php' && $currentSort == 'rating') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-simple me-2 <?= ($currentPage == 'search.php' && $currentSort == 'rating') ? '' : 'text-secondary' ?>"></i> Популярное
        </a>
        <a href="search.php?sort=date" class="list-group-item list-group-item-action <?= ($currentPage == 'search.php' && $currentSort == 'date') ? 'active' : '' ?>">
            <i class="fa-regular fa-clock me-2 <?= ($currentPage == 'search.php' && $currentSort == 'date') ? '' : 'text-secondary' ?>"></i> Новинки
        </a>
        <?php if(is_logged_in()): ?>
            <a href="dashboard.php" class="list-group-item list-group-item-action">
                <i class="fa-regular fa-heart me-2 text-danger"></i> Избранное
            </a>
        <?php endif; ?>
    </div>

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
    
    <!-- 4. КНОПКИ (Если не вошел) -->
    <?php if(!is_logged_in()): ?>
        <div class="d-grid gap-2">
            <a href="login.php" class="btn btn-outline-primary fw-medium">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Вход
            </a>
            <a href="register.php" class="btn btn-primary fw-medium">
                Регистрация
            </a>
        </div>
    <?php endif; ?>

</div>