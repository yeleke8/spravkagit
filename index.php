<?php
// index.php - Главная страница (Dashboard Style)
require_once 'templates/header.php';

// --- ХАК РАЗМЕТКИ ---
// Закрываем стандартный контейнер из header.php, чтобы создать свой layout на всю ширину
?>
    </div></div>

<?php
// 1. ПОЛУЧЕНИЕ ДАННЫХ
// Популярные
$stmtPopular = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY rating_avg DESC, rating_count DESC LIMIT 3");
$popularPosts = $stmtPopular->fetchAll();

// Новинки
$stmtNew = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY created_at DESC LIMIT 3");
$newPosts = $stmtNew->fetchAll();

// Главные категории для меню
$stmtCats = $pdo->query("SELECT * FROM categories WHERE cat_parent_id IS NULL ORDER BY cat_id ASC");
$categories = $stmtCats->fetchAll();

// Словарь иконок
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
?>

<!-- СТИЛИ ДЛЯ DASHBOARD LAYOUT -->
<style>
    /* Фон страницы как на референсе - размытый градиент */
    body {
        background: radial-gradient(circle at 10% 20%, rgb(239, 246, 255) 0%, rgb(219, 228, 255) 90%);
        min-height: 100vh;
    }

    /* Основной контейнер-карточка */
    .dashboard-container {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.05);
        padding: 0;
        overflow: hidden;
        min-height: 800px;
        margin-bottom: 40px;
    }

    /* Левое меню */
    .dashboard-sidebar {
        background: #fcfcfc;
        border-right: 1px solid #f0f0f0;
        padding: 30px 20px;
        height: 100%;
    }

    .nav-item-custom {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        color: #64748b;
        text-decoration: none;
        border-radius: 12px;
        margin-bottom: 4px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .nav-item-custom:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .nav-item-custom.active {
        background: #fff;
        color: #000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        font-weight: 600;
    }

    .nav-item-custom i {
        width: 24px;
        margin-right: 12px;
    }

    /* Правая часть */
    .dashboard-content {
        padding: 30px 40px;
    }

    /* Hero баннер */
    .hero-banner {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-radius: 20px;
        padding: 40px;
        position: relative;
        overflow: hidden;
        margin-bottom: 40px;
    }

    /* Декоративные элементы баннера (имитация графика справа) */
    .hero-banner::after {
        content: '';
        position: absolute;
        right: -50px;
        bottom: -50px;
        width: 300px;
        height: 300px;
        background: url('https://cdn-icons-png.flaticon.com/512/7480/7480838.png'); /* Пример иконки графика */
        background-size: contain;
        background-repeat: no-repeat;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    /* Поисковая строка */
    .search-pill-container {
        background: #fff;
        border-radius: 50px;
        padding: 6px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        max-width: 600px;
        border: 1px solid #e2e8f0;
    }

    .search-input-clean {
        border: none;
        background: transparent;
        padding: 10px 20px;
        flex-grow: 1;
        outline: none;
        font-size: 1rem;
        color: #334155;
    }

    .search-btn-black {
        background: #1e293b;
        color: white;
        border: none;
        border-radius: 40px;
        padding: 10px 24px;
        font-weight: 500;
        transition: background 0.2s;
    }

    .search-btn-black:hover {
        background: #0f172a;
    }

    /* Табы над поиском */
    .hero-tabs span {
        display: inline-block;
        font-size: 0.9rem;
        color: #64748b;
        margin-right: 15px;
        background: rgba(255,255,255,0.6);
        padding: 4px 12px;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .badge-score {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 3px solid #10b981;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        font-weight: bold;
        position: absolute;
        right: 40px;
        bottom: 30px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
</style>

<div class="container pb-5">
    <div class="dashboard-container row g-0">

        <!-- ЛЕВОЕ МЕНЮ (SIDEBAR) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="dashboard-sidebar d-flex flex-column">
                <div class="mb-4 px-2">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Меню</small>
                </div>

                <a href="index.php" class="nav-item-custom active">
                    <i class="fa-solid fa-house"></i> Главная
                </a>
                <a href="search.php?sort=rating" class="nav-item-custom">
                    <i class="fa-solid fa-chart-simple"></i> Популярное
                </a>
                <a href="search.php?sort=date" class="nav-item-custom">
                    <i class="fa-regular fa-clock"></i> Новинки
                </a>

                <?php if(is_logged_in()): ?>
                    <a href="dashboard.php" class="nav-item-custom">
                        <i class="fa-regular fa-heart"></i> Избранное
                    </a>
                <?php endif; ?>

                <div class="mt-4 mb-2 px-2">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Категории</small>
                </div>

                <div style="overflow-y: auto; flex-grow: 1;">
                    <?php foreach ($categories as $cat):
                        $icon = $catIcons[$cat['cat_slug']] ?? 'fa-layer-group';
                    ?>
                    <a href="category.php?slug=<?= h($cat['cat_slug']) ?>" class="nav-item-custom">
                        <i class="fa-solid <?= $icon ?>"></i> <?= h($cat['cat_name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="mt-auto pt-4 border-top">
                     <?php if(is_logged_in()): ?>
                        <a href="login.php?logout=1" class="nav-item-custom text-danger">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Выход
                        </a>
                     <?php else: ?>
                        <a href="login.php" class="nav-item-custom text-primary">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Вход
                        </a>
                     <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ПРАВЫЙ КОНТЕНТ -->
        <div class="col-lg-9 col-12">
            <div class="dashboard-content">

                <!-- HERO БЛОК -->
                <div class="hero-banner">
                    <h2 class="fw-bold mb-4" style="font-size: 2rem; color: #1e293b;">
                        Найди любое место в <br>Туркестане
                    </h2>

                    <div class="hero-tabs">
                        <span><i class="fa-solid fa-magnifying-glass small me-1"></i> Поиск мест</span>
                        <span><i class="fa-solid fa-share-nodes small me-1"></i> Услуги</span>
                        <span><i class="fa-solid fa-map small me-1"></i> На карте</span>
                    </div>

                    <form action="search.php" method="GET" class="search-pill-container">
                        <i class="fa-solid fa-link text-muted ms-3"></i>
                        <input type="text" name="q" class="search-input-clean" placeholder="Введите название, например: Rixos..." required>
                        <button type="submit" class="search-btn-black">Найти</button>
                    </form>

                    <div class="badge-score">9.8</div>
                </div>

                <!-- ВАШИ ПОИСКИ (ПОПУЛЯРНОЕ) -->
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <h4 class="fw-bold m-0" style="color: #1e293b;">Популярные места</h4>
                    <a href="search.php?sort=rating" class="text-decoration-none small fw-bold text-muted">Показать все</a>
                </div>

                <div class="row mb-5">
                    <?php if (!empty($popularPosts)): ?>
                        <?php foreach ($popularPosts as $post): ?>
                            <!-- Используем стандартный card.php, как вы и просили -->
                            <?php include 'templates/card.php'; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><p class="text-muted">Нет данных</p></div>
                    <?php endif; ?>
                </div>

                <!-- НОВЫЕ ДОБАВЛЕНИЯ -->
                 <div class="d-flex justify-content-between align-items-end mb-3">
                    <h4 class="fw-bold m-0" style="color: #1e293b;">Новинки в городе</h4>
                    <a href="search.php?sort=date" class="text-decoration-none small fw-bold text-muted">Показать все</a>
                </div>

                <div class="row">
                    <?php if (!empty($newPosts)): ?>
                        <?php foreach ($newPosts as $post): ?>
                            <?php include 'templates/card.php'; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><p class="text-muted">Нет данных</p></div>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-5">
                    <a href="search.php" class="btn btn-outline-dark rounded-pill px-4">Перейти к полному каталогу</a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php
// --- ХАК РАЗМЕТКИ (КОНЕЦ) ---
// footer.php закроет два div
?>
<div class="container"><div class="row">

<?php
require_once 'templates/footer.php'; 
?>