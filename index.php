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
            
            <?php require_once 'templates/hero.php'; ?>

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