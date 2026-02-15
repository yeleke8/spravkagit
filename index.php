<?php
// index.php - Главная страница
require_once 'templates/header.php';

// Данные
$stmtPopular = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY rating_avg DESC, rating_count DESC LIMIT 4");
$popularPosts = $stmtPopular->fetchAll();

$stmtNew = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY created_at DESC LIMIT 4");
$newPosts = $stmtNew->fetchAll();
?>

<!-- САЙДБАР (Слева) -->
<div class="col-lg-2 mb-4 d-none d-lg-block">
    <div class="sticky-top" style="top: 100px; z-index: 1;">
        <?php require 'templates/sidebar.php'; ?>
    </div>
</div>

<!-- ОСНОВНОЙ КОНТЕНТ (Справа) -->
<div class="col-lg-10 col-12">
    
    <?php require_once 'templates/hero.php'; ?>

    <!-- ПОПУЛЯРНОЕ -->
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="h4 mb-0 fw-bold text-dark">Популярные места</h3>
            <p class="text-muted small mb-0">Выбор жителей и гостей города</p>
        </div>
        <a href="search.php?sort=rating" class="btn btn-light rounded-pill px-3 fw-medium text-primary">Все <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row gx-4 mb-5">
        <?php if (!empty($popularPosts)): ?>
            <?php foreach ($popularPosts as $post): ?>
                <?php include 'templates/card.php'; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-light border text-center text-muted py-4">Пока нет популярных мест</div></div>
        <?php endif; ?>
    </div>

    <!-- НОВИНКИ -->
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="h4 mb-0 fw-bold text-dark">Новинки в городе</h3>
            <p class="text-muted small mb-0">Только открылись и ждут гостей</p>
        </div>
        <a href="search.php?sort=date" class="btn btn-light rounded-pill px-3 fw-medium text-primary">Все <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row gx-4">
        <?php if (!empty($newPosts)): ?>
            <?php foreach ($newPosts as $post): ?>
                <?php include 'templates/card.php'; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-light border text-center text-muted py-4">Пока нет новых мест</div></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>