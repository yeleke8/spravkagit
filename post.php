<?php
// post.php - Детальная страница заведения (Updated Comments)
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

echo '<div class="col-md-9">';

$slug = $_GET['slug'] ?? '';
$post = getPostBySlug($pdo, $slug);

if (!$post) {
    echo "<div class='alert alert-danger'>Заведение не найдено или удалено.</div>";
    echo '</div>'; // Закрываем col-md-9
    require_once 'templates/footer.php';
    exit;
}

// 1. Увеличиваем просмотры
if (!isset($_SESSION['viewed_posts'][$post['post_id']])) {
    $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$post['post_id']]);
    $_SESSION['viewed_posts'][$post['post_id']] = true;
    $post['views']++;
}

// 2. Получаем доп. данные
$stmtPhotos = $pdo->prepare("SELECT * FROM photos WHERE post_id = ? ORDER BY sort_order ASC");
$stmtPhotos->execute([$post['post_id']]);
$photos = $stmtPhotos->fetchAll();

$stmtTags = $pdo->prepare("SELECT t.* FROM tags t JOIN s_tags st ON t.attr_id = st.attr_id WHERE st.post_id = ?");
$stmtTags->execute([$post['post_id']]);
$tags = $stmtTags->fetchAll();

$contacts = json_decode($post['contacts'], true) ?? [];
$attributes = json_decode($post['attributes'], true) ?? [];
$worktime = json_decode($post['worktime'], true) ?? [];

// 3. Получаем отзывы
$stmtComments = $pdo->prepare("
    SELECT c.*, u.user_name
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = ? AND c.is_approved = 1
    ORDER BY c.created_at DESC
");
$stmtComments->execute([$post['post_id']]);
$comments = $stmtComments->fetchAll();

// Проверяем, оставлял ли текущий юзер отзыв
$userHasReview = false;
if (is_logged_in()) {
    foreach($comments as $c) {
        if ($c['user_id'] == $_SESSION['user_id']) {
            $userHasReview = true;
            break;
        }
    }
    // Если в одобренных нет, проверим в модерации (чтобы не спамил)
    if (!$userHasReview) {
        $stmtCheck = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ? AND post_id = ?");
        $stmtCheck->execute([$_SESSION['user_id'], $post['post_id']]);
        if ($stmtCheck->fetch()) $userHasReview = true;
    }
}
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Главная</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($post['title']) ?></li>
  </ol>
</nav>

<div class="card border-0 shadow-sm overflow-hidden mb-4">
    <div class="row g-0">
        <div class="col-md-12">
            <div id="carouselGallery" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner" style="max-height: 400px;">
                    <div class="carousel-item active">
                        <img src="<?= h($post['photo']) ?>" class="d-block w-100" style="object-fit: cover; height: 400px;" alt="Main">
                        <div class="carousel-caption d-none d-md-block p-4 rounded-3 mb-4" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
                            <h2 class="fw-bold mb-1"><?= h($post['title']) ?></h2>
                            <p class="mb-0"><i class="fa-solid fa-location-dot text-danger"></i> <?= h($post['address']) ?></p>
                        </div>
                    </div>
                    <?php foreach($photos as $photo): ?>
                    <div class="carousel-item">
                        <img src="<?= h($photo['photo_url']) ?>" class="d-block w-100" style="object-fit: cover; height: 400px;" alt="Photo">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if(count($photos) > 0): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="fw-bold fs-3 mb-0 d-md-none"><?= h($post['title']) ?></h1>
            <div class="d-flex align-items-center bg-light px-3 py-2 rounded-pill">
                <span class="fw-bold fs-5 me-2"><?= number_format($post['rating_avg'], 1) ?></span>
                <div class="text-warning small me-2">
                    <?php
                    $rating = round($post['rating_avg']);
                    for ($i = 1; $i <= 5; $i++) echo ($i <= $rating) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star text-secondary opacity-25"></i>';
                    ?>
                </div>
                <small class="text-muted border-start ps-2"><?= $post['rating_count'] ?> отзывов</small>
            </div>
        </div>

        <?php if($tags): ?>
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach($tags as $tag): ?>
                    <span class="badge bg-light text-dark border py-2 px-3 fw-normal">
                        <i class="fa-solid <?= h($tag['attr_icon']) ?> text-primary me-2"></i> <?= h($tag['attr_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <h5 class="fw-bold mt-4">Описание</h5>
        <p class="text-secondary" style="line-height: 1.7; font-size: 1.05rem;">
            <?= nl2br(h($post['description'])) ?>
        </p>

        <?php if(!empty($attributes)): ?>
            <div class="bg-light p-3 rounded-3 mt-4">
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
    <!-- Левая колонка: Отзывы -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <h4 class="fw-bold mb-0">Отзывы посетителей</h4>
        </div>

        <!-- Форма отзыва -->
        <?php if(is_logged_in()): ?>
            <?php if(!$userHasReview): ?>
                <div class="card mb-5 border-0 shadow-sm bg-light">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fa-regular fa-pen-to-square"></i> Написать отзыв</h6>
                        <form action="add_comment.php" method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">

                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Ваша оценка</label>
                                <div class="rate">
                                    <input type="radio" id="star5" name="rating" value="5" />
                                    <label for="star5" title="5 звезд">5 stars</label>
                                    <input type="radio" id="star4" name="rating" value="4" />
                                    <label for="star4" title="4 звезды">4 stars</label>
                                    <input type="radio" id="star3" name="rating" value="3" />
                                    <label for="star3" title="3 звезды">3 stars</label>
                                    <input type="radio" id="star2" name="rating" value="2" />
                                    <label for="star2" title="2 звезды">2 stars</label>
                                    <input type="radio" id="star1" name="rating" value="1" required />
                                    <label for="star1" title="1 звезда">1 star</label>
                                </div>
                                <div class="clearfix"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Ваш комментарий</label>
                                <textarea name="comment" class="form-control" rows="3" placeholder="Что вам понравилось, а что нет?" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary px-4">Опубликовать</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-4 border-0 shadow-sm">
                    <i class="fa-solid fa-check-circle me-2"></i> Вы уже оценили это заведение. Спасибо!
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm bg-light mb-4 text-center">
                <div class="card-body py-4">
                    <i class="fa-solid fa-lock fa-2x text-muted mb-3"></i>
                    <p class="mb-3">Только зарегистрированные пользователи могут оставлять отзывы.</p>
                    <a href="login.php" class="btn btn-outline-primary rounded-pill px-4">Войти в аккаунт</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Список отзывов -->
        <?php if(empty($comments)): ?>
            <div class="text-center py-5">
                <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                    <i class="fa-regular fa-comment-dots fa-3x text-muted"></i>
                </div>
                <h5>Пока нет отзывов</h5>
                <p class="text-muted">Станьте первым, кто поделится мнением об этом месте!</p>
            </div>
        <?php else: ?>
            <?php foreach($comments as $comment): ?>
                <div class="card comment-card mb-3 p-3">
                    <div class="d-flex">
                        <div class="me-3">
                            <?= getInitialsAvatar($comment['user_name']) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0"><?= h($comment['user_name']) ?></h6>
                                <small class="text-muted"><?= date('d.m.Y', strtotime($comment['created_at'])) ?></small>
                            </div>

                            <div class="mb-2">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <i class="fa-solid fa-star small <?= ($i <= $comment['rating']) ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
                                <?php endfor; ?>
                            </div>

                            <p class="mb-2 text-dark" style="font-size: 0.95rem;"><?= nl2br(h($comment['comment'])) ?></p>

                            <!-- Ответ владельца -->
                            <?php if($comment['owner_reply']): ?>
                                <div class="owner-reply-box p-3 mt-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa-solid fa-store text-success me-2"></i>
                                        <strong class="text-dark small">Ответ представителя</strong>
                                        <small class="text-muted ms-auto"><?= date('d.m.Y', strtotime($comment['reply_created_at'])) ?></small>
                                    </div>
                                    <p class="mb-0 small text-secondary fst-italic">
                                        <?= nl2br(h($comment['owner_reply'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Правая колонка: Инфо (Sidebar) -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px; z-index: 1;">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Контакты</h5>

                <!-- Статус работы -->
                <?php if (!empty($post['worktime'])):
                    $status = getWorkStatus($post['worktime']);
                ?>
                    <div class="mb-3 p-3 bg-<?= $status['color'] ?> bg-opacity-10 rounded d-flex align-items-center border border-<?= $status['color'] ?> border-opacity-25">
                        <div class="me-3 text-<?= $status['color'] ?>">
                            <i class="fa-regular fa-clock fa-2x"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= $status['text'] ?></div>
                            <small class="text-muted">Текущий статус</small>
                        </div>
                    </div>
                <?php endif; ?>

                <ul class="list-unstyled mb-4">
                    <?php if(!empty($contacts['phone']) && $contacts['phone'] !== '-'): ?>
                    <li class="mb-3 d-flex align-items-center">
                        <div class="bg-light p-2 rounded-circle me-3"><i class="fa-solid fa-phone text-primary"></i></div>
                        <div>
                            <small class="text-muted d-block">Телефон</small>
                            <a href="tel:<?= h($contacts['phone']) ?>" class="fw-bold text-dark text-decoration-none fs-5"><?= h($contacts['phone']) ?></a>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if(!empty($contacts['whatsapp']) && $contacts['whatsapp'] !== '-'): ?>
                    <li class="mb-3 d-flex align-items-center">
                        <div class="bg-light p-2 rounded-circle me-3"><i class="fa-brands fa-whatsapp text-success"></i></div>
                        <div>
                            <small class="text-muted d-block">WhatsApp</small>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contacts['whatsapp']) ?>" class="fw-bold text-dark text-decoration-none">Написать сообщение</a>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="d-grid gap-2">
                    <?php if(is_logged_in() && $_SESSION['user_type'] === 'user'): ?>
                        <button class="btn btn-outline-danger rounded-pill"><i class="fa-regular fa-heart"></i> В избранное</button>
                    <?php endif; ?>

                    <?php if(is_logged_in() && ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'owner' && $post['owner_id'] == $_SESSION['user_id']))): ?>
                        <a href="edit.php?id=<?= $post['post_id'] ?>" class="btn btn-secondary rounded-pill"><i class="fa-solid fa-pen-to-square"></i> Редактировать</a>
                    <?php endif; ?>
                </div>

                <!-- Полный режим работы -->
                <?php if(isset($worktime) && !empty($worktime)): ?>
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold mb-3 text-uppercase small text-muted">График работы</h6>
                        <table class="table table-sm table-borderless small mb-0">
                            <?php
                            $daysOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                            $todayKey = strtolower(date('D'));
                            foreach ($daysOrder as $d):
                                $time = $worktime[$d] ?? 'closed';
                                $isToday = ($d === $todayKey);
                                $rowClass = $isToday ? 'bg-primary bg-opacity-10 rounded fw-bold text-primary' : '';
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="ps-2 py-1"><?= getDayName($d) ?></td>
                                    <td class="text-end pe-2 py-1">
                                        <?= ($time === 'closed' || empty($time)) ? '<span class="text-danger">Выходной</span>' : h($time) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php 
echo '</div>'; // Закрываем col-md-9
require_once 'templates/footer.php'; 
?>