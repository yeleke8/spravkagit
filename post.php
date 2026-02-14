<?php
// post.php - Детальная страница заведения (Bootstrap Standard)
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

echo '<div class="col-md-9">';

$slug = $_GET['slug'] ?? '';
$post = getPostBySlug($pdo, $slug);

if (!$post) {
    echo "<div class='alert alert-danger'>Заведение не найдено или удалено.</div>";
    echo '</div>'; 
    require_once 'templates/footer.php';
    exit;
}

// 1. Увеличиваем просмотры
if (!isset($_SESSION['viewed_posts'][$post['post_id']])) {
    $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$post['post_id']]);
    $_SESSION['viewed_posts'][$post['post_id']] = true;
    $post['views']++;
}

// 2. Доп данные
$stmtPhotos = $pdo->prepare("SELECT * FROM photos WHERE post_id = ? ORDER BY sort_order ASC");
$stmtPhotos->execute([$post['post_id']]);
$photos = $stmtPhotos->fetchAll();

$stmtTags = $pdo->prepare("SELECT t.* FROM tags t JOIN s_tags st ON t.attr_id = st.attr_id WHERE st.post_id = ?");
$stmtTags->execute([$post['post_id']]);
$tags = $stmtTags->fetchAll();

$contacts = json_decode($post['contacts'], true) ?? [];
$attributes = json_decode($post['attributes'], true) ?? [];
$worktime = json_decode($post['worktime'], true) ?? [];

// 3. Отзывы
$stmtComments = $pdo->prepare("
    SELECT c.*, u.user_name
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = ? AND c.is_approved = 1
    ORDER BY c.created_at DESC
");
$stmtComments->execute([$post['post_id']]);
$comments = $stmtComments->fetchAll();

$userHasReview = false;
if (is_logged_in()) {
    foreach($comments as $c) {
        if ($c['user_id'] == $_SESSION['user_id']) {
            $userHasReview = true;
            break;
        }
    }
    if (!$userHasReview) {
        $stmtCheck = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ? AND post_id = ?");
        $stmtCheck->execute([$_SESSION['user_id'], $post['post_id']]);
        if ($stmtCheck->fetch()) $userHasReview = true;
    }
}
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Главная</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($post['title']) ?></li>
  </ol>
</nav>

<div class="card shadow-sm mb-4">
    <!-- Carousel -->
    <div id="carouselGallery" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="<?= h($post['photo']) ?>" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Main">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-3">
                    <h2 class="h3 mb-1"><?= h($post['title']) ?></h2>
                    <p class="mb-0 small"><i class="fa-solid fa-location-dot"></i> <?= h($post['address']) ?></p>
                </div>
            </div>
            <?php foreach($photos as $photo): ?>
            <div class="carousel-item">
                <img src="<?= h($photo['photo_url']) ?>" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Photo">
            </div>
            <?php endforeach; ?>
        </div>
        <?php if(count($photos) > 0): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-2"><?= h($post['title']) ?></h1>
            <div class="d-flex align-items-center bg-light border px-3 py-2 rounded">
                <span class="fw-bold fs-5 me-2"><?= number_format($post['rating_avg'], 1) ?></span>
                <div class="text-warning me-2">
                    <?php
                    $rating = round($post['rating_avg']);
                    for ($i = 1; $i <= 5; $i++) echo ($i <= $rating) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star text-secondary"></i>';
                    ?>
                </div>
                <small class="text-muted border-start ps-2"><?= $post['rating_count'] ?> отзывов</small>
            </div>
        </div>

        <?php if($tags): ?>
        <div class="mb-4">
            <?php foreach($tags as $tag): ?>
                <span class="badge bg-light text-dark border p-2 me-1 mb-1 fw-normal">
                    <i class="fa-solid <?= h($tag['attr_icon']) ?> text-primary me-1"></i> <?= h($tag['attr_name']) ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h5 class="fw-bold">Описание</h5>
        <p class="card-text text-muted">
            <?= nl2br(h($post['description'])) ?>
        </p>

        <?php if(!empty($attributes)): ?>
            <div class="alert alert-light border mt-4">
                <div class="row">
                    <?php if(isset($attributes['avg_check'])): ?>
                        <div class="col-md-6 mb-2">
                            <i class="fa-solid fa-wallet text-muted me-2"></i> Средний чек: <strong><?= h($attributes['avg_check']) ?> ₸</strong>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($attributes['cuisine'])): ?>
                        <div class="col-md-6 mb-2">
                            <i class="fa-solid fa-utensils text-muted me-2"></i> Кухня: <strong><?= h($attributes['cuisine']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Отзывы -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="h5 mb-0">Отзывы посетителей</h4>
        </div>

        <?php if(is_logged_in()): ?>
            <?php if(!$userHasReview): ?>
                <div class="card mb-4 border shadow-sm">
                    <div class="card-header bg-light fw-bold">Оставить отзыв</div>
                    <div class="card-body">
                        <form action="add-comment.php" method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Ваша оценка</label>
                                <select name="rating" class="form-select">
                                    <option value="5">5 - Отлично</option>
                                    <option value="4">4 - Хорошо</option>
                                    <option value="3">3 - Нормально</option>
                                    <option value="2">2 - Плохо</option>
                                    <option value="1">1 - Ужасно</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Комментарий</label>
                                <textarea name="comment" class="form-control" rows="3" required placeholder="Напишите ваше мнение..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Опубликовать</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-4">
                    <i class="fa-solid fa-check-circle me-2"></i> Вы уже оценили это заведение.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-secondary mb-4">
                <a href="login.php" class="alert-link">Войдите</a>, чтобы оставить отзыв.
            </div>
        <?php endif; ?>

        <?php if(empty($comments)): ?>
            <div class="text-center py-4 border rounded bg-light text-muted">
                Отзывов пока нет. Будьте первым!
            </div>
        <?php else: ?>
            <?php foreach($comments as $comment): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h6 class="card-title fw-bold mb-1">
                                <i class="fa-solid fa-user-circle text-muted"></i> <?= h($comment['user_name']) ?>
                            </h6>
                            <small class="text-muted"><?= date('d.m.Y', strtotime($comment['created_at'])) ?></small>
                        </div>
                        
                        <div class="mb-2 text-warning small">
                            <?php for ($i=1; $i<=5; $i++) echo ($i <= $comment['rating']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star text-secondary"></i>'; ?>
                        </div>

                        <p class="card-text mb-2"><?= nl2br(h($comment['comment'])) ?></p>

                        <?php if($comment['owner_reply']): ?>
                            <div class="mt-3 p-3 bg-light border-start border-4 border-success">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="text-success small"><i class="fa-solid fa-store me-1"></i> Ответ представителя</strong>
                                    <small class="text-muted"><?= date('d.m.Y', strtotime($comment['reply_created_at'])) ?></small>
                                </div>
                                <p class="mb-0 small fst-italic text-secondary"><?= nl2br(h($comment['owner_reply'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Сайдбар справа -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Контакты</div>
            <div class="card-body">
                
                <?php if (!empty($post['worktime'])):
                    $status = getWorkStatus($post['worktime']);
                ?>
                    <div class="alert alert-<?= $status['color'] ?> d-flex align-items-center p-2 mb-3">
                        <i class="fa-regular fa-clock fa-2x me-3"></i>
                        <div>
                            <div class="fw-bold"><?= $status['text'] ?></div>
                            <small>Статус сейчас</small>
                        </div>
                    </div>
                <?php endif; ?>

                <ul class="list-unstyled mb-3">
                    <?php if(!empty($contacts['phone']) && $contacts['phone'] !== '-'): ?>
                    <li class="mb-2">
                        <small class="text-muted d-block">Телефон</small>
                        <a href="tel:<?= h($contacts['phone']) ?>" class="text-decoration-none fw-bold text-dark fs-5"><?= h($contacts['phone']) ?></a>
                    </li>
                    <?php endif; ?>

                    <?php if(!empty($contacts['whatsapp']) && $contacts['whatsapp'] !== '-'): ?>
                    <li class="mb-2">
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contacts['whatsapp']) ?>" class="btn btn-success w-100 btn-sm">
                            <i class="fa-brands fa-whatsapp"></i> Написать в WhatsApp
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="d-grid gap-2">
                    <?php if(is_logged_in() && $_SESSION['user_type'] === 'user'): 
                        $isFav = in_array($post['post_id'], $myFavs);
                    ?>
                        <button class="btn <?= $isFav ? 'btn-danger' : 'btn-outline-danger' ?> btn-favorite" data-id="<?= $post['post_id'] ?>">
                            <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i> 
                            <span class="btn-text"><?= $isFav ? 'В избранном' : 'В избранное' ?></span>
                        </button>
                    <?php endif; ?>

                    <?php if(is_logged_in() && ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'owner' && $post['owner_id'] == $_SESSION['user_id']))): ?>
                        <a href="edit.php?id=<?= $post['post_id'] ?>" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Редактировать</a>
                    <?php endif; ?>
                </div>

                <?php if(isset($worktime) && !empty($worktime)): ?>
                    <hr>
                    <h6 class="small text-muted fw-bold">График работы</h6>
                    <table class="table table-sm table-borderless small mb-0">
                        <?php
                        $daysOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                        $todayKey = strtolower(date('D'));
                        foreach ($daysOrder as $d):
                            $time = $worktime[$d] ?? 'closed';
                            $rowClass = ($d === $todayKey) ? 'table-primary fw-bold' : '';
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td><?= getDayName($d) ?></td>
                                <td class="text-end">
                                    <?= ($time === 'closed' || empty($time)) ? '<span class="text-danger">Вых.</span>' : h($time) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php 
echo '</div>';
require_once 'templates/footer.php'; 
?>