<?php
// post.php - Детальная страница
require_once 'templates/header.php';

$slug = $_GET['slug'] ?? '';
$post = getPostBySlug($pdo, $slug);

if (!$post) {
    echo "<div class='col-12'><div class='alert alert-danger my-5 text-center'>Заведение не найдено.</div></div>";
    require_once 'templates/footer.php';
    exit;
}

// Логика просмотров... (оставляем ту же, что была)
$userIp = getRealIp();
$userIpBin = inet_pton($userIp); 
$currentUserId = is_logged_in() ? $_SESSION['user_id'] : null;
$stmtCheckView = $pdo->prepare("SELECT view_id FROM log_post_views WHERE post_id = ? AND ip_address = ? AND viewed_at > (NOW() - INTERVAL 5 MINUTE)");
$stmtCheckView->execute([$post['post_id'], $userIpBin]);
if (!$stmtCheckView->fetch()) {
    try {
        $pdo->prepare("INSERT INTO log_post_views (post_id, user_id, ip_address) VALUES (?, ?, ?)")->execute([$post['post_id'], $currentUserId, $userIpBin]);
        $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$post['post_id']]);
        $post['views']++;
    } catch (PDOException $e) {}
}

// Данные
$stmtTags = $pdo->prepare("SELECT t.* FROM tags t JOIN s_tags st ON t.attr_id = st.attr_id WHERE st.post_id = ?");
$stmtTags->execute([$post['post_id']]);
$tags = $stmtTags->fetchAll();

$contacts = json_decode($post['contacts'], true) ?? [];
$attributes = json_decode($post['attributes'], true) ?? [];
$worktime = json_decode($post['worktime'], true) ?? [];

$stmtComments = $pdo->prepare("SELECT c.*, u.user_name FROM comments c JOIN users u ON c.user_id = u.user_id WHERE c.post_id = ? AND c.is_approved = 1 ORDER BY c.created_at DESC");
$stmtComments->execute([$post['post_id']]);
$comments = $stmtComments->fetchAll();

$userHasReview = false;
if (is_logged_in()) {
    foreach($comments as $c) if ($c['user_id'] == $_SESSION['user_id']) $userHasReview = true;
    if (!$userHasReview) {
        $stmtCheck = $pdo->prepare("SELECT comment_id FROM comments WHERE user_id = ? AND post_id = ?");
        $stmtCheck->execute([$_SESSION['user_id'], $post['post_id']]);
        if ($stmtCheck->fetch()) $userHasReview = true;
    }
}
?>

<!-- Левая колонка: Навигация -->
<div class="col-lg-2 d-none d-lg-block">
    <div class="sticky-top" style="top: 100px;">
        <!-- Исправлены ссылки на ЧПУ -->
        <a href="/" class="btn btn-light w-100 text-start mb-2 rounded-pill"><i class="fa-solid fa-arrow-left me-2"></i> Главная</a>
        <a href="/search" class="btn btn-light w-100 text-start mb-2 rounded-pill"><i class="fa-solid fa-search me-2"></i> Поиск</a>
        <hr>
        <div class="text-muted small px-2">Категория</div>
        <?php 
           // Получаем категорию поста для хлебных крошек
           $stmtCat = $pdo->prepare("SELECT c.cat_name, c.cat_slug FROM categories c JOIN s_categories sc ON c.cat_id = sc.cat_id WHERE sc.post_id = ? LIMIT 1");
           $stmtCat->execute([$post['post_id']]);
           $pCat = $stmtCat->fetch();
        ?>
        <?php if($pCat): ?>
            <!-- Исправлена ссылка на ЧПУ -->
            <a href="/category/<?= h($pCat['cat_slug']) ?>" class="fw-bold text-decoration-none d-block px-2 mt-1"><?= h($pCat['cat_name']) ?></a>
        <?php endif; ?>
    </div>
</div>

<!-- Центральная колонка: Контент -->
<div class="col-lg-7 col-xl-7">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <!-- Главное фото -->
        <div class="position-relative bg-dark" style="height: 400px;">
             <img src="<?= h($post['photo']) ?>" class="w-100 h-100" style="object-fit: cover; opacity: 0.9;" alt="Main Photo">
             <div class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                <h1 class="text-white fw-bold mb-1"><?= h($post['title']) ?></h1>
                <p class="text-white-50 mb-0"><i class="fa-solid fa-location-dot me-2"></i> <?= h($post['address']) ?></p>
             </div>
        </div>

        <div class="card-body p-4">
            <!-- Рейтинг и действия -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark px-2 py-1 rounded fw-bold fs-5 me-2"><?= number_format($post['rating_avg'], 1) ?></div>
                    <div class="text-warning me-3 small">
                        <?php $r = round($post['rating_avg']); for($i=1;$i<=5;$i++) echo ($i<=$r)?'<i class="fa-solid fa-star"></i>':'<i class="fa-regular fa-star text-secondary"></i>'; ?>
                        <div class="text-muted text-nowrap"><?= $post['rating_count'] ?> отзывов</div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <?php 
                        $isFav = false;
                        if(is_logged_in() && isset($myFavs)) $isFav = in_array($post['post_id'], $myFavs);
                    ?>
                    <button type="button" id="favButton" class="btn <?= $isFav ? 'btn-danger' : 'btn-outline-danger' ?> btn-favorite rounded-pill px-4" data-id="<?= $post['post_id'] ?>">
                        <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart me-1"></i> 
                        <span class="btn-text"><?= $isFav ? 'В избранном' : 'В избранное' ?></span>
                    </button>
                    <button class="btn btn-outline-primary rounded-pill px-3" onclick="navigator.clipboard.writeText(window.location.href); alert('Ссылка скопирована!');"><i class="fa-solid fa-share"></i></button>
                </div>
            </div>

            <!-- Теги -->
            <?php if($tags): ?>
            <div class="mb-4">
                <?php foreach($tags as $tag): ?>
                    <span class="d-inline-flex align-items-center bg-light border px-3 py-2 rounded-pill me-2 mb-2 text-dark small">
                        <i class="fa-solid <?= h($tag['attr_icon']) ?> text-primary me-2"></i> <?= h($tag['attr_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h5 class="fw-bold mb-3">О месте</h5>
            <p class="text-muted mb-4 lh-lg">
                <?= nl2br(h($post['description'])) ?>
            </p>

            <?php if(!empty($attributes)): ?>
                <div class="row g-3 p-3 bg-light rounded-3 mb-4">
                    <?php if(isset($attributes['avg_check'])): ?>
                        <div class="col-sm-6 d-flex align-items-center">
                            <i class="fa-solid fa-wallet text-secondary fs-4 me-3"></i>
                            <div>
                                <small class="text-muted d-block">Средний чек</small>
                                <span class="fw-bold"><?= h($attributes['avg_check']) ?> ₸</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($attributes['cuisine'])): ?>
                        <div class="col-sm-6 d-flex align-items-center">
                            <i class="fa-solid fa-utensils text-secondary fs-4 me-3"></i>
                            <div>
                                <small class="text-muted d-block">Кухня / Тип</small>
                                <span class="fw-bold"><?= h($attributes['cuisine']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Блок Отзывов -->
    <div class="mb-5">
        <h4 class="fw-bold mb-4">Отзывы <span class="text-muted fw-normal fs-6 ms-2">(<?= count($comments) ?>)</span></h4>

        <?php if(is_logged_in()): ?>
            <?php if(!$userHasReview): ?>
                <div class="card border-0 shadow-sm mb-4 bg-primary bg-opacity-10">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Оставьте свой отзыв</h6>
                        <!-- Исправлено на /add-comment.php -->
                        <form action="/add-comment.php" method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <div class="mb-3">
                                <div class="rating-input d-flex gap-2 fs-3 text-warning mb-2" style="cursor: pointer;">
                                    <!-- Простая реализация выбора -->
                                    <select name="rating" class="form-select w-auto">
                                        <option value="5">⭐⭐⭐⭐⭐ Отлично</option>
                                        <option value="4">⭐⭐⭐⭐ Хорошо</option>
                                        <option value="3">⭐⭐⭐ Нормально</option>
                                        <option value="2">⭐⭐ Плохо</option>
                                        <option value="1">⭐ Ужасно</option>
                                    </select>
                                </div>
                                <textarea name="comment" class="form-control" rows="3" required placeholder="Расскажите о своих впечатлениях..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary px-4">Опубликовать</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-light border text-center py-4">
                <!-- Исправлено на /login -->
                <a href="/login" class="fw-bold">Войдите</a>, чтобы оставить отзыв.
            </div>
        <?php endif; ?>

        <?php if(empty($comments)): ?>
            <div class="text-center text-muted py-5">Нет отзывов. Будьте первым!</div>
        <?php else: ?>
            <?php foreach($comments as $comment): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-user text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= h($comment['user_name']) ?></h6>
                                    <div class="text-warning small" style="font-size: 0.8rem;">
                                        <?php for ($i=1; $i<=5; $i++) echo ($i <= $comment['rating']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star text-secondary"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d.m.Y', strtotime($comment['created_at'])) ?></small>
                        </div>
                        <p class="mb-1 ms-5"><?= nl2br(h($comment['comment'])) ?></p>
                        <?php if($comment['owner_reply']): ?>
                            <div class="ms-5 mt-3 p-3 bg-light rounded-3 border-start border-4 border-success">
                                <small class="fw-bold text-success d-block mb-1">Ответ заведения</small>
                                <p class="mb-0 small text-secondary"><?= nl2br(h($comment['owner_reply'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Правая колонка: Инфо -->
<div class="col-lg-3 col-xl-3">
    <div class="card shadow-sm border-0 rounded-4 mb-4 sticky-top" style="top: 100px;">
        <div class="card-body p-4">
            
            <?php if (!empty($post['worktime'])):
                $status = getWorkStatus($post['worktime']);
            ?>
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-<?= $status['color'] ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fa-regular fa-clock fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold <?= 'text-'.$status['color'] ?>"><?= $status['text'] ?></div>
                        <small class="text-muted">Статус сейчас</small>
                    </div>
                </div>
            <?php endif; ?>

            <h6 class="fw-bold mb-3 border-bottom pb-2">Контакты</h6>
            <ul class="list-unstyled mb-4">
                <?php if(!empty($contacts['phone']) && $contacts['phone'] !== '-'): ?>
                <li class="mb-3">
                    <small class="text-muted d-block mb-1">Телефон</small>
                    <a href="tel:<?= h($contacts['phone']) ?>" class="fw-bold text-dark text-decoration-none fs-5"><?= h($contacts['phone']) ?></a>
                </li>
                <?php endif; ?>

                <?php if(!empty($contacts['whatsapp']) && $contacts['whatsapp'] !== '-'): ?>
                <li>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contacts['whatsapp']) ?>" class="btn btn-success w-100 fw-medium rounded-pill py-2">
                        <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <?php if(isset($worktime) && !empty($worktime)): ?>
                <h6 class="fw-bold mb-3 border-bottom pb-2">График работы</h6>
                <ul class="list-unstyled small mb-0">
                    <?php
                    $daysOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                    $todayKey = strtolower(date('D'));
                    foreach ($daysOrder as $d):
                        $time = $worktime[$d] ?? 'closed';
                        $isToday = ($d === $todayKey);
                    ?>
                        <li class="d-flex justify-content-between py-1 <?= $isToday ? 'fw-bold text-primary' : '' ?>">
                            <span><?= getDayName($d) ?></span>
                            <span><?= ($time === 'closed' || empty($time)) ? '<span class="text-danger">Вых.</span>' : h($time) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'owner' && $post['owner_id'] == $_SESSION['user_id'])): ?>
                <div class="mt-4 pt-3 border-top text-center">
                    <!-- Исправлено на /edit.php -->
                    <a href="/edit.php?id=<?= $post['post_id'] ?>" class="btn btn-secondary w-100 btn-sm">Редактировать</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// JS для кнопки избранного
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('favButton');
    if (btn) {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-id');
            const icon = this.querySelector('i');
            const textSpan = this.querySelector('.btn-text');
            
            try {
                // Исправлено: добавлен слеш
                let response = await fetch('/ajax-favorite.php', { method: 'POST', body: JSON.stringify({id: postId}) });
                let result = await response.json();
                if (result.status === 'success') {
                    if (result.action === 'added') {
                        icon.classList.remove('fa-regular'); icon.classList.add('fa-solid');
                        this.classList.remove('btn-outline-danger'); this.classList.add('btn-danger');
                        if(textSpan) textSpan.textContent = 'В избранном';
                    } else {
                        icon.classList.remove('fa-solid'); icon.classList.add('fa-regular');
                        this.classList.remove('btn-danger'); this.classList.add('btn-outline-danger');
                        if(textSpan) textSpan.textContent = 'В избранное';
                    }
                } else if (result.status === 'login_required') { window.location.href = '/login'; }
            } catch (err) {}
        });
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>