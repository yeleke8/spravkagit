<?php 
// templates/header
require_once __DIR__ . '/../back/db.php';
require_once __DIR__ . '/../back/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$myFavs = [];
if (is_logged_in()) {
    $stmtFavs = $pdo->prepare("SELECT post_id FROM s_favorites WHERE user_id = ?");
    $stmtFavs->execute([$_SESSION['user_id']]);
    $myFavs = $stmtFavs->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="ru" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справочник Туркестана</title>
    <meta name="description" content="Лучшие заведения, магазины и услуги Туркестана.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Добавлен слеш в начале пути, чтобы стили не слетали на внутренних страницах -->
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .navbar { backdrop-filter: blur(10px); background-color: rgba(255,255,255,0.95) !important; }
        .navbar-brand { font-weight: 800; letter-spacing: -0.5px; }
        .avatar-small { width: 36px; height: 36px; object-fit: cover; border-radius: 50%; }
        
        /* Улучшенные тени и карточки для Full Width */
        .card { border: none; transition: all 0.2s ease-in-out; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important; }
        
        /* Сайдбар */
        .list-group-item { border: none; padding: 12px 20px; }
        .list-group-item.active { background-color: #0d6efd; color: white; font-weight: 600; }
    </style>
</head>
<body class="d-flex flex-column h-100">

<!-- Navbar Full Width -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top border-bottom">
    <div class="container-fluid px-4 px-lg-5">
        <!-- Ссылка на главную через / -->
        <a class="navbar-brand text-primary fs-4 me-4" href="/">
            <i class="fa-solid fa-map-location-dot"></i> Spravka<span class="text-dark">.kz</span>
        </a>

        <button class="navbar-toggler border-0 bg-light rounded-circle p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Поиск в шапке: action="/search" (абсолютный путь) -->
            <form class="d-flex mx-lg-auto my-2 my-lg-0 col-lg-5" action="/search" method="GET">
                <div class="input-group bg-light rounded-pill border px-2 py-1 w-100">
                    <span class="input-group-text bg-transparent border-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input class="form-control bg-transparent border-0 shadow-none" type="search" name="q" placeholder="Поиск мест, услуг, товаров..." value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
                </div>
            </form>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center fw-medium">
                <li class="nav-item px-2"><a class="nav-link" href="/">Главная</a></li>
                <li class="nav-item px-2"><a class="nav-link" href="/search?sort=rating">Рейтинг</a></li>
                <li class="nav-item px-2"><a class="nav-link" href="/search?sort=date">Новинки</a></li>

                <li class="nav-item d-none d-lg-block mx-2 text-muted opacity-25">|</li>

                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center bg-light rounded-pill py-1 pe-3 ps-1 border" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2 avatar-small shadow-sm">
                                <span class="small fw-bold"><?= mb_substr($_SESSION['user_name'] ?? 'U', 0, 1) ?></span>
                            </div>
                            <span class="small text-dark"><?= h($_SESSION['user_name'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 rounded-4 overflow-hidden" aria-labelledby="userDropdown">
                            <li><div class="dropdown-header">Ваш аккаунт</div></li>
                            <li><a class="dropdown-item py-2" href="/dashboard"><i class="fa-solid fa-gauge me-2 text-primary"></i> Личный кабинет</a></li>
                            <?php if ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'owner'): ?>
                                <li><a class="dropdown-item py-2" href="/add"><i class="fa-solid fa-plus me-2 text-success"></i> Добавить место</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger py-2" href="/login?logout=1"><i class="fa-solid fa-right-from-bracket me-2"></i> Выход</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link" href="/login">Вход</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary rounded-pill px-4 shadow-sm" href="/register">Регистрация</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Отступ для фиксированного хедера -->
<div style="padding-top: 80px;"></div>

<!-- Flash Messages Container -->
<?php if (isset($_SESSION['flash']) || isset($_SESSION['flash_error'])): ?>
    <div class="container-fluid px-4 px-lg-5 mb-3">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 bg-white border-start border-success border-4" role="alert">
                <i class="fa-solid fa-check-circle text-success me-2"></i> <?= h($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 bg-white border-start border-danger border-4" role="alert">
                <i class="fa-solid fa-circle-exclamation text-danger me-2"></i> <?= h($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Main Content Wrapper Full Width -->
<!-- Внимание: Этот div закрывается в footer -->
<div class="container-fluid px-4 px-lg-5 flex-shrink-0">
    <div class="row">