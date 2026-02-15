<?php
// admin.php - Панель администратора со статистикой
require_once 'back/db.php';
require_once 'back/functions.php';
require_once 'templates/header.php';

// 1. Проверка прав
if (!is_logged_in() || $_SESSION['user_type'] !== 'admin') {
    die("<div class='container mt-5'><div class='alert alert-danger'>Доступ запрещен.</div></div>");
}

// 2. Обработка действий (Одобрить / Удалить)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $type = $_POST['type']; 

    if ($type === 'post') {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE post SET status = 1 WHERE post_id = ?")->execute([$id]);
            $_SESSION['flash'] = "Заведение одобрено!";
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM post WHERE post_id = ?")->execute([$id]);
            $_SESSION['flash'] = "Заведение удалено.";
        }
    } elseif ($type === 'comment') {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE comments SET is_approved = 1 WHERE comment_id = ?")->execute([$id]);
            $_SESSION['flash'] = "Отзыв одобрен!";
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM comments WHERE comment_id = ?")->execute([$id]);
            $_SESSION['flash'] = "Отзыв удален.";
        }
    }
    echo "<script>window.location.href='admin.php';</script>";
    exit;
}

// --- 3. СБОР СТАТИСТИКИ ---

// А. Общие цифры
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'posts' => $pdo->query("SELECT COUNT(*) FROM post")->fetchColumn(),
    'comments' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'views' => $pdo->query("SELECT SUM(views) FROM post")->fetchColumn() ?: 0,
];

// Б. График за последние 7 дней (Заведения)
$chartPosts = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM post 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// В. График за последние 7 дней (Отзывы)
$chartComments = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM comments 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Подготовка данных для JS (заполняем нулями пустые дни)
$dates = [];
$dataPosts = [];
$dataComments = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('d.m', strtotime($d)); // Формат "12.02"
    $dataPosts[] = $chartPosts[$d] ?? 0;
    $dataComments[] = $chartComments[$d] ?? 0;
}

// Г. Топ-5 популярных заведений
$topPosts = $pdo->query("SELECT title, views, rating_avg FROM post ORDER BY views DESC LIMIT 5")->fetchAll();

// --- 4. ДАННЫЕ ДЛЯ МОДЕРАЦИИ ---
$pendingPosts = $pdo->query("SELECT * FROM post WHERE status = 0 ORDER BY created_at DESC")->fetchAll();
$pendingComments = $pdo->query("
    SELECT c.*, u.user_name, p.title as post_title 
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    JOIN post p ON c.post_id = p.post_id
    WHERE c.is_approved = 0 
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<div class="col-md-9 mx-auto mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-chart-line text-primary"></i> Панель администратора</h2>
        <span class="text-muted small">Сегодня: <?= date('d.m.Y') ?></span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75 small">Просмотры</h6>
                    <h2 class="fw-bold mb-0"><?= number_format($stats['views']) ?></h2>
                    <i class="fa-regular fa-eye position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75 small">Заведения</h6>
                    <h2 class="fw-bold mb-0"><?= $stats['posts'] ?></h2>
                    <i class="fa-solid fa-store position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75 small">Отзывы</h6>
                    <h2 class="fw-bold mb-0"><?= $stats['comments'] ?></h2>
                    <i class="fa-regular fa-comments position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75 small">Пользователи</h6>
                    <h2 class="fw-bold mb-0"><?= $stats['users'] ?></h2>
                    <i class="fa-solid fa-users position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Активность за 7 дней</div>
                <div class="card-body">
                    <canvas id="activityChart" height="120"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Топ-5 по просмотрам</div>
                <ul class="list-group list-group-flush small">
                    <?php foreach($topPosts as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="text-truncate" style="max-width: 150px;" title="<?= h($p['title']) ?>">
                                <?= h($p['title']) ?>
                            </span>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-regular fa-eye text-muted"></i> <?= $p['views'] ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <h4 class="fw-bold mb-3">Модерация</h4>
    
    <ul class="nav nav-pills mb-3" id="adminTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button">
                Заведения <span class="badge bg-danger rounded-pill ms-1"><?= count($pendingPosts) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button">
                Отзывы <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($pendingComments) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabContent">
        
        <div class="tab-pane fade show active" id="posts">
            <?php if(empty($pendingPosts)): ?>
                <div class="alert alert-light border text-center py-4">
                    <i class="fa-solid fa-check-circle text-success mb-2 fs-4"></i>
                    <p class="mb-0">Нет новых заведений на проверку.</p>
                </div>
            <?php else: ?>
                <?php foreach($pendingPosts as $post): ?>
                    <div class="card mb-3 shadow-sm border-start border-4 border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title fw-bold mb-1"><?= h($post['title']) ?></h5>
                                <small class="text-muted"><?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></small>
                            </div>
                            <p class="small text-muted mb-2"><i class="fa-solid fa-map-pin"></i> <?= h($post['address']) ?></p>
                            
                            <div class="bg-light p-2 rounded small mb-3 text-secondary">
                                <?= h(mb_strimwidth($post['description'], 0, 200, '...')) ?>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="post.php?slug=<?= $post['slug'] ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fa-solid fa-external-link-alt"></i> Просмотр</a>
                                
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="type" value="post">
                                    <input type="hidden" name="id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-3">Одобрить</button>
                                </form>

                                <form method="POST" class="d-inline" onsubmit="return confirm('Удалить это заведение?');">
                                    <input type="hidden" name="type" value="post">
                                    <input type="hidden" name="id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger px-3">Удалить</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="comments">
            <?php if(empty($pendingComments)): ?>
                <div class="alert alert-light border text-center py-4">
                    <i class="fa-solid fa-check-circle text-success mb-2 fs-4"></i>
                    <p class="mb-0">Нет новых отзывов на проверку.</p>
                </div>
            <?php else: ?>
                <div class="list-group shadow-sm">
                    <?php foreach($pendingComments as $comm): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold"><?= h($comm['user_name']) ?></span>
                                    <span class="text-muted small mx-1">о месте</span>
                                    <a href="post.php?id=<?= $comm['post_id'] ?>" target="_blank" class="fw-bold text-decoration-none"><?= h($comm['post_title']) ?></a>
                                </div>
                                <small class="text-muted"><?= date('d.m H:i', strtotime($comm['created_at'])) ?></small>
                            </div>
                            
                            <div class="text-warning small my-1">
                                <?= str_repeat('<i class="fa-solid fa-star"></i>', $comm['rating']) ?>
                            </div>
                            
                            <p class="mb-2 bg-light p-2 rounded small fst-italic">"<?= nl2br(h($comm['comment'])) ?>"</p>
                            
                            <div class="d-flex gap-2 justify-content-end">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="type" value="comment">
                                    <input type="hidden" name="id" value="<?= $comm['comment_id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success py-0 px-2">Ок</button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Удалить этот отзыв?');">
                                    <input type="hidden" name="type" value="comment">
                                    <input type="hidden" name="id" value="<?= $comm['comment_id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger py-0 px-2">Удалить</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('activityChart');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Новые заведения',
                data: <?= json_encode($dataPosts) ?>,
                borderColor: '#198754', // success color
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Новые отзывы',
                data: <?= json_encode($dataComments) ?>,
                borderColor: '#ffc107', // warning color
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
</script>

<?php require_once 'templates/footer.php'; ?>