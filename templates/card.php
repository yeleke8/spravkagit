<?php
// templates/card.php
$attrs = isset($post['attributes']) ? json_decode($post['attributes'], true) : [];

// Цена
$priceBadge = '';
if (isset($attrs['avg_check']) && (int)$attrs['avg_check'] > 0) {
    $check = (int)$attrs['avg_check'];
    $priceSym = ($check <= 2500) ? '₸' : (($check <= 7000) ? '₸₸' : '₸₸₸');
    $priceBadge = '<span class="badge bg-light text-secondary border fw-medium">' . $priceSym . '</span>';
}

// Статус работы
$workStatus = '';
if (!empty($post['worktime'])) {
    $statusData = getWorkStatus($post['worktime']); // Функция из functions.php
    $dotColor = $statusData['status'] === 'open' ? 'success' : 'danger';
    $workStatus = '<span class="position-absolute top-0 start-0 m-3 p-1 rounded-circle bg-'.$dotColor.' border border-2 border-white" title="'.$statusData['text'].'"></span>';
}
?>

<div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <div class="card h-100">
        <div class="position-relative">
            <a href="place/<?= h($post['slug']) ?>" class="d-block overflow-hidden rounded-top" style="height: 200px;">
                <img src="<?= h($post['photo']) ?>" alt="<?= h($post['title']) ?>" 
                     class="w-100 h-100" style="object-fit: cover;">
            </a>
            
            <?= $workStatus ?>

            <?php 
            $isFav = (is_logged_in() && isset($myFavs) && in_array($post['post_id'], $myFavs));
            ?>
            <button class="btn btn-light rounded-circle shadow-sm position-absolute top-0 end-0 m-2 btn-favorite d-flex align-items-center justify-content-center" 
                    data-id="<?= $post['post_id'] ?>" 
                    style="width: 36px; height: 36px;">
                <i class="<?= $isFav ? 'fa-solid text-danger' : 'fa-regular text-secondary' ?> fa-heart"></i>
            </button>
        </div>

        <div class="card-body d-flex flex-column p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="fw-bold mb-0 text-truncate pe-2" style="font-size: 1.05rem;">
                    <a href="place/<?= h($post['slug']) ?>" class="text-dark text-decoration-none stretched-link">
                        <?= h($post['title']) ?>
                    </a>
                </h6>
                
                <?php if($post['rating_avg'] > 0): ?>
                <div class="d-flex align-items-center bg-warning bg-opacity-10 px-2 py-1 rounded text-warning fw-bold small">
                    <i class="fa-solid fa-star me-1" style="font-size: 0.8rem;"></i> <?= number_format($post['rating_avg'], 1) ?>
                </div>
                <?php endif; ?>
            </div>

            <p class="text-muted small mb-3 text-truncate">
                <i class="fa-solid fa-location-dot me-1 text-primary opacity-50"></i>
                <?= h($post['address']) ?>
            </p>

            <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top border-light">
                <small class="text-muted"><?= isset($attrs['cuisine']) ? explode(',', $attrs['cuisine'])[0] : 'Заведение' ?></small>
                <?= $priceBadge ?>
            </div>
        </div>
    </div>
</div>