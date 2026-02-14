<?php
// index.php - Главная страница
require_once 'templates/header.php';

// --- ХАК РАЗМЕТКИ ---
// Закрываем стандартный контейнер из header.php
?>
    </div></div>

<?php
// 1. ПОЛУЧЕНИЕ ДАННЫХ ДЛЯ КОНТЕНТА
$stmtPopular = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY rating_avg DESC, rating_count DESC LIMIT 3");
$popularPosts = $stmtPopular->fetchAll();

$stmtNew = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY created_at DESC LIMIT 3");
$newPosts = $stmtNew->fetchAll();
?>

<div class="container pb-5">
    <div class="row">

        <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ САЙДБАР -->
        <?php require_once 'templates/sidebar.php'; ?>

        <!-- ПРАВЫЙ КОНТЕНТ -->
        <div class="col-lg-9 col-md-8 col-12">
            
            <!-- HERO БЛОК -->
            <div class="p-5 mb-4 bg-light rounded-3 border shadow-sm" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="container-fluid py-2">
                    <h1 class="display-6 fw-bold mb-3">Найди любое место в Туркестане</h1>
                    <p class="col-md-10 fs-5 text-muted mb-4">Рестораны, отели, магазины и услуги. Рейтинги, фото и реальные отзывы жителей города.</p>
                    
                    <form action="search.php" method="GET" class="shadow-sm rounded-pill bg-white p-1 d-flex">
                        <span class="input-group-text bg-white border-0 rounded-pill ps-3"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent" placeholder="Например: Rixos, плов, сантехник..." required style="height: 50px;">
                        <button class="btn btn-primary rounded-pill px-4 m-1 fw-bold" type="submit">Найти</button>
                    </form>
                    
                    <div class="mt-4 text-muted small">
                        <span class="fw-bold text-dark">Популярные запросы:</span> 
                        <a href="search.php?q=кофе" class="badge bg-white text-dark border text-decoration-none ms-1">Кофе</a>
                        <a href="search.php?q=бургер" class="badge bg-white text-dark border text-decoration-none ms-1">Бургеры</a>
                        <a href="search.php?q=парк" class="badge bg-white text-dark border text-decoration-none ms-1">Парки</a>
                        <a href="category.php?slug=food" class="badge bg-white text-dark border text-decoration-none ms-1">Еда</a>
                    </div>
                </div>
            </div>

            <!-- ПОПУЛЯРНОЕ -->
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h3 class="h4 mb-0 text-dark fw-bold"><i class="fa-solid fa-fire text-danger me-2"></i>Популярные места</h3>
                <a href="search.php?sort=rating" class="btn btn-sm btn-light border text-muted hover-dark">Все <i class="fa-solid fa-angle-right ms-1"></i></a>
            </div>

            <div class="row mb-5">
                <?php if (!empty($popularPosts)): ?>
                    <?php foreach ($popularPosts as $post): ?>
                        <?php include 'templates/card.php'; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-light border text-center text-muted">Пока нет популярных мест</div></div>
                <?php endif; ?>
            </div>

            <!-- НОВИНКИ -->
             <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h3 class="h4 mb-0 text-dark fw-bold"><i class="fa-regular fa-clock text-primary me-2"></i>Новинки в городе</h3>
                <a href="search.php?sort=date" class="btn btn-sm btn-light border text-muted hover-dark">Все <i class="fa-solid fa-angle-right ms-1"></i></a>
            </div>

            <div class="row">
                <?php if (!empty($newPosts)): ?>
                    <?php foreach ($newPosts as $post): ?>
                        <?php include 'templates/card.php'; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-light border text-center text-muted">Пока нет новых мест</div></div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5 mb-4">
                <a href="search.php" class="btn btn-outline-primary rounded-pill px-5 py-2 fw-medium">Перейти к полному каталогу</a>
            </div>

        </div>
    </div>
</div>

<?php
// --- ХАК РАЗМЕТКИ (КОНЕЦ) ---
?>
<div class="container"><div class="row">

<?php
require_once 'templates/footer.php'; 
?>