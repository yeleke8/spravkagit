<?php 
// templates/header.php
require_once __DIR__ . '/../back/db.php'; // Правильный путь к back/db.php
require_once __DIR__ . '/../back/functions.php';

// Проверяем, не запущена ли сессия (на случай двойного инклуда)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справочник Туркестана</title>
    <meta name="description" content="Лучшие заведения, магазины и услуги Туркестана. Отзывы, рейтинг, фото.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .navbar-brand { font-weight: 700; letter-spacing: -0.5px; }
        .avatar-small { width: 32px; height: 32px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body class="d-flex flex-column h-100">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand text-primary fs-4" href="index.php">
            <i class="fa-solid fa-map-location-dot"></i> Spravka<span class="text-dark">.kz</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <form class="d-flex mx-lg-4 my-2 my-lg-0 flex-grow-1" action="search.php" method="GET">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input class="form-control bg-light border-start-0 ps-0" type="search" name="q" placeholder="Поиск мест и услуг..." aria-label="Search" value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
                </div>
            </form>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Главная</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="search.php?sort=rating">Рейтинг</a>
                </li>

                <li class="nav-item d-none d-lg-block mx-2 text-muted">|</li>

                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2 avatar-small">
                                <i class="fa-solid fa-user small"></i>
                            </div>
                            <span class="fw-medium"><?= h($_SESSION['user_name'] ?? $_SESSION['user_login']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fa-solid fa-gauge me-2 text-muted"></i> Личный кабинет</a></li>
                            <?php if ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'owner'): ?>
                                <li><a class="dropdown-item" href="add.php"><i class="fa-solid fa-plus me-2 text-muted"></i> Добавить заведение</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="login.php?logout=1"><i class="fa-solid fa-right-from-bracket me-2"></i> Выход</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-medium" href="login.php">Вход</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary rounded-pill px-4" href="register.php">Регистрация</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="container mb-3">
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i> <?= h($_SESSION['flash']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="container mb-3">
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= h($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="container flex-shrink-0">
    <div class="row">