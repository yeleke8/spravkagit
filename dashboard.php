<?php
// dashboard.php - Единый личный кабинет (Admin + Owner + User)
require_once 'back/db.php';
require_once 'back/functions.php';
require_once 'templates/header.php';

// 1. Проверка доступа
if (!is_logged_in()) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$msg = '';

// --- 2. ЛОГИКА АДМИНА (Модерация) ---
if ($user_type === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
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
    // Перезагрузка страницы, чтобы сбросить POST
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}

// --- 3. ЛОГИКА ПРОФИЛЯ (Смена данных) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $new_pass = trim($_POST['new_password']);

    if (mb_strlen($name) < 2) {
        $msg = "<div class='alert alert-danger'>Имя слишком короткое!</div>";
    } else {
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET user_name = ?, user_phone = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET user_name = ?, user_phone = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $user_id]);
        }
        $_SESSION['user_name'] = $name;
        $msg = "<div class='alert alert-success'>Профиль успешно обновлен!</div>";
    }
}

// Получаем свежие данные пользователя
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch();


// --- 4. СБОР СТАТИСТИКИ (ЕСЛИ АДМИН) ---
$adminStats = [];
$chartData = [];
$pendingPosts = [];
$pendingComments = [];
$topPosts = [];

if ($user_type === 'admin') {
    // А. Общие цифры
    $adminStats = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'posts' => $pdo->query("SELECT COUNT(*) FROM post")->fetchColumn(),
        'comments' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
        'views' => $pdo->query("SELECT SUM(views) FROM post")->fetchColumn() ?: 0,
    ];

    // Б. Графики
    $chartPosts = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM post WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
    $chartComments = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);

    $dates = []; $dataPosts = []; $dataComments = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d.m', strtotime($d));
        $dataPosts[] = $chartPosts[$d] ?? 0;
        $dataComments[] = $chartComments[$d] ?? 0;
    }
    $chartData = ['labels' => $dates, 'posts' => $dataPosts, 'comments' => $dataComments];

    // В. Топ-5
    $topPosts = $pdo->query("SELECT title, views, rating_avg FROM post ORDER BY views DESC LIMIT 5")->fetchAll();

    // Г. Модерация
    $pendingPosts = $pdo->query("SELECT * FROM post WHERE status = 0 ORDER BY created_at DESC")->fetchAll();
    $pendingComments = $pdo->query("SELECT c.*, u.user_name, p.title as post_title FROM comments c JOIN users u ON c.user_id = u.user_id JOIN post p ON c.post_id = p.post_id WHERE c.is_approved = 0 ORDER BY c.created_at DESC")->fetchAll();
}


// --- 5. СТАТИСТИКА ВЛАДЕЛЬЦА (ДЛЯ ADMIN и OWNER) ---
$myStats = [
    'views' => 0,
    'rating_avg' => 0,
    'posts_count' => 0
];
$my_posts = [];
$favs = [];

if ($user_type !== 'user') {
    // Админ видит свои посты, Владелец свои
    $stmtPosts = $pdo->prepare("SELECT * FROM post WHERE owner_id = ? ORDER BY created_at DESC");
    $stmtPosts->execute([$user_id]);
    $my_posts = $stmtPosts->fetchAll();

    $myStats['posts_count'] = count($my_posts);
    foreach ($my_posts as $p) {
        $myStats['views'] += $p['views'];
    }
    if ($myStats['posts_count'] > 0) {
        $sumRating = array_sum(array_column($my_posts, 'rating_avg'));
        $myStats['rating_avg'] = $sumRating / $myStats['posts_count'];
    }
} else {
    // Для обычных пользователей - получаем избранное
    $stmtFav = $pdo->prepare("SELECT p.* FROM post p JOIN s_favorites f ON p.post_id = f.post_id WHERE f.user_id = ?");
    $stmtFav->execute([$user_id]);
    $favs = $stmtFav->fetchAll();
}
?>

<div class="col-md-3 mb-4">
    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
        <div class="card-body text-center">
            <div class="mb-3">
                <i class="fa-solid fa-circle-user fa-5x text-secondary"></i>
            </div>
            <h5 class="fw-bold"><?= h($currentUser['user_name']) ?></h5>
            <p class="text-muted small">
                <?php
                    if ($user_type === 'owner') echo 'Владелец бизнеса';
                    elseif ($user_type === 'admin') echo 'Администратор';
                    else echo 'Пользователь';
                ?>
            </p>
        </div>
        <div class="list-group list-group-flush">
            <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                <i class="fa-solid fa-gauge me-2"></i> Обзор
            </a>
            <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fa-solid fa-gear me-2"></i> Настройки
            </a>
            <a href="login.php?logout=1" class="list-group-item list-group-item-action text-danger">
                <i class="fa-solid fa-right-from-bracket me-2"></i> Выход
            </a>
        </div>
    </div>
</div>

<div class="col-md-9">
    <?= $msg ?>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="dashboard">

            <?php if ($user_type === 'admin'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-user-shield"></i> Панель управления</h4>
                    <span class="badge bg-danger">ADMIN MODE</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-primary text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Просмотры</h6>
                                <h2 class="fw-bold mb-0"><?= number_format($adminStats['views']) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-success text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Заведения</h6>
                                <h2 class="fw-bold mb-0"><?= $adminStats['posts'] ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-warning text-dark h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Отзывы</h6>
                                <h2 class="fw-bold mb-0"><?= $adminStats['comments'] ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-info text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Люди</h6>
                                <h2 class="fw-bold mb-0"><?= $adminStats['users'] ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-lg-8 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold">Активность (7 дней)</div>
                            <div class="card-body">
                                <canvas id="activityChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold">Топ-5 просмотров</div>
                            <ul class="list-group list-group-flush small">
                                <?php foreach($topPosts as $p): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                        <span class="text-truncate" style="max-width: 150px;" title="<?= h($p['title']) ?>"><?= h($p['title']) ?></span>
                                        <span class="badge bg-light text-dark border"><i class="fa-regular fa-eye text-muted"></i> <?= $p['views'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3">Модерация</h5>
                <ul class="nav nav-pills mb-3" id="adminTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="m-posts-tab" data-bs-toggle="tab" data-bs-target="#m-posts" type="button">
                            Новые места <span class="badge bg-danger rounded-pill ms-1"><?= count($pendingPosts) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="m-comments-tab" data-bs-toggle="tab" data-bs-target="#m-comments" type="button">
                            Новые отзывы <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($pendingComments) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content mb-5" id="adminTabContent">
                    <div class="tab-pane fade show active" id="m-posts">
                        <?php if(empty($pendingPosts)): ?>
                            <div class="alert alert-light border text-center py-3 text-muted">Нет новых заведений на проверку.</div>
                        <?php else: ?>
                            <?php foreach($pendingPosts as $post): ?>
                                <div class="card mb-2 shadow-sm border-start border-4 border-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="fw-bold mb-0"><?= h($post['title']) ?></h6>
                                            <small class="text-muted"><?= date('d.m H:i', strtotime($post['created_at'])) ?></small>
                                        </div>
                                        <div class="small text-muted mb-2"><?= h($post['address']) ?></div>
                                        <div class="d-flex gap-2 mt-2">
                                            <a href="post.php?slug=<?= $post['slug'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">Смотреть</a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="type" value="post"><input type="hidden" name="id" value="<?= $post['post_id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Одобрить</button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Удалить?');">
                                                <input type="hidden" name="type" value="post"><input type="hidden" name="id" value="<?= $post['post_id'] ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Удалить</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="m-comments">
                        <?php if(empty($pendingComments)): ?>
                            <div class="alert alert-light border text-center py-3 text-muted">Нет новых отзывов.</div>
                        <?php else: ?>
                            <div class="list-group shadow-sm">
                                <?php foreach($pendingComments as $comm): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <small class="fw-bold"><?= h($comm['user_name']) ?> <span class="fw-normal text-muted">о</span> <?= h($comm['post_title']) ?></small>
                                            <small class="text-muted"><?= date('d.m H:i', strtotime($comm['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-1 bg-light p-2 rounded small fst-italic mt-1">"<?= nl2br(h($comm['comment'])) ?>"</p>
                                        <div class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="type" value="comment"><input type="hidden" name="id" value="<?= $comm['comment_id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success py-0 px-2">Ок</button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Удалить?');">
                                                <input type="hidden" name="type" value="comment"><input type="hidden" name="id" value="<?= $comm['comment_id'] ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger py-0 px-2">X</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-5 border-secondary">
            <?php endif; ?>


            <?php if ($user_type === 'admin' || $user_type === 'owner'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Мои заведения</h3>
                    <a href="add.php" class="btn btn-success"><i class="fa-solid fa-plus"></i> Добавить</a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted small">Мои Просмотры</h6>
                                <h3 class="fw-bold mb-0 text-primary"><?= $myStats['views'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted small">Мой Рейтинг</h6>
                                <h3 class="fw-bold mb-0 text-warning"><?= number_format($myStats['rating_avg'], 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted small">Количество мест</h6>
                                <h3 class="fw-bold mb-0 text-success"><?= $myStats['posts_count'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <?php if(!empty($my_posts)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Заведение</th>
                                            <th>Статус</th>
                                            <th>Рейтинг</th>
                                            <th class="text-end pe-4">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($my_posts as $p): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= h($p['photo']) ?>" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <div>
                                                            <a href="post.php?slug=<?= $p['slug'] ?>" class="fw-bold text-dark text-decoration-none"><?= h($p['title']) ?></a>
                                                            <div class="small text-muted"><?= date('d.m.Y', strtotime($p['created_at'])) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($p['status'] == 1): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Активно</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">На проверке</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-warning"><i class="fa-solid fa-star"></i> <?= number_format($p['rating_avg'], 1) ?></span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="edit.php?id=<?= $p['post_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                                <p>У вас пока нет добавленных заведений.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <h3 class="fw-bold mb-4">Избранное ❤️</h3>

                <?php if(!empty($favs)): ?>
                    <div class="row">
                        <?php foreach ($favs as $post) { include 'templates/card.php'; } ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-4 text-center">
                        <i class="fa-regular fa-heart fa-2x mb-3 d-block"></i>
                        Вы пока ничего не добавили в избранное. <a href="index.php">Перейти к поиску</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>

        <div class="tab-pane fade" id="settings">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Редактирование профиля</h4>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Ваше имя</label>
                                <input type="text" name="name" class="form-control" value="<?= h($currentUser['user_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Телефон</label>
                                <input type="text" name="phone" class="form-control" value="<?= h($currentUser['user_phone']) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Логин (нельзя изменить)</label>
                            <input type="text" class="form-control bg-light" value="<?= h($currentUser['login']) ?>" readonly>
                        </div>
                        <hr class="my-4">
                        <h5 class="mb-3">Безопасность</h5>
                        <div class="mb-4">
                            <label class="form-label">Новый пароль</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Оставьте пустым, если не хотите менять">
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($user_type === 'admin'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('activityChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartData['labels']) ?>,
                datasets: [{
                    label: 'Заведения',
                    data: <?= json_encode($chartData['posts']) ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.3, fill: true
                }, {
                    label: 'Отзывы',
                    data: <?= json_encode($chartData['comments']) ?>,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.3, fill: true
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
</script>
<?php endif; ?>

<?php require_once 'templates/footer.php'; ?>