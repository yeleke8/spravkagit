<?php
// Распаковка JSON атрибутов для доп. информации
$attrs = isset($post['attributes']) ? json_decode($post['attributes'], true) : [];

// 1. Логика для уровня цен (₸, ₸₸, ₸₸₸)
$priceLevel = '';
if (isset($attrs['avg_check'])) {
    $check = (int)$attrs['avg_check'];
    if ($check > 0) {
        if ($check <= 2500) $priceSym = '₸';
        elseif ($check <= 7000) $priceSym = '₸₸';
        else $priceSym = '₸₸₸';
        $priceLevel = '<span class="badge bg-light text-dark border fw-normal me-2" title="Средний чек: ~'.$check.'">' . $priceSym . '</span>';
    }
}

// 2. Логика для звезд отеля
$hotelStars = '';
if (isset($attrs['stars_count']) && $attrs['stars_count'] > 0) {
    $hotelStars = '<div class="text-warning small mb-1">' . str_repeat('<i class="fa-solid fa-star"></i>', $attrs['stars_count']) . '</div>';
}

// 3. Дополнительный тег
$extraTag = '';
if (isset($attrs['cuisine'])) {
    $cuisineParts = explode(',', $attrs['cuisine']);
    $extraTag = '<small class="text-muted text-truncate d-block" style="max-width: 150px;">' . h(trim($cuisineParts[0])) . '</small>';
}
?>

<!-- Адаптивная колонка: на больших экранах (XL) 4 в ряд, на огромных (XXL) 5 в ряд -->
<div class="col-sm-6 col-lg-4 col-xl-3 col-xxl-2 mb-4">
    <div class="card h-100 shadow-sm rounded-4 position-relative bg-white overflow-hidden">
        
        <!-- Верхняя часть: Фото -->
        <div class="position-relative">
            <a href="post.php?slug=<?= h($post['slug']) ?>" class="d-block overflow-hidden" style="height: 180px;">
                <img src="<?= h($post['photo']) ?>" alt="<?= h($post['title']) ?>" 
                     class="w-100 h-100" 
                     style="object-fit: cover; transition: transform 0.3s ease;">
            </a>
            
            <!-- Рейтинг (плашка) -->
            <?php if($post['rating_avg'] > 0): ?>
                <div class="position-absolute bottom-0 start-0 m-2 badge bg-white text-dark shadow-sm d-flex align-items-center py-1 px-2 rounded-3">
                    <i class="fa-solid fa-star text-warning me-1"></i> 
                    <span class="fw-bold"><?= number_format($post['rating_avg'], 1) ?></span>
                    <span class="text-muted fw-normal ms-1 small">(<?= $post['rating_count'] ?>)</span>
                </div>
            <?php endif; ?>

            <!-- Кнопка Избранного (Круглая) -->
            <?php 
            $isFav = false;
            if(is_logged_in() && isset($myFavs)) {
                $isFav = in_array($post['post_id'], $myFavs);
            }
            ?>
            <button class="btn btn-light rounded-circle shadow-sm position-absolute top-0 end-0 m-2 btn-favorite d-flex align-items-center justify-content-center" 
                    data-id="<?= $post['post_id'] ?>" 
                    style="width: 32px; height: 32px; border: none;">
                <i class="<?= $isFav ? 'fa-solid text-danger' : 'fa-regular text-secondary' ?> fa-heart"></i>
            </button>
        </div>

        <!-- Нижняя часть: Контент -->
        <div class="card-body p-3 d-flex flex-column">
            <?= $hotelStars ?>
            
            <h6 class="fw-bold mb-1 text-truncate">
                <a href="post.php?slug=<?= h($post['slug']) ?>" class="text-dark text-decoration-none stretched-link">
                    <?= h($post['title']) ?>
                </a>
            </h6>
            
            <p class="text-muted small mb-2 text-truncate">
                <i class="fa-solid fa-location-dot me-1 text-primary opacity-50"></i>
                <?= h($post['address']) ?>
            </p>

            <div class="mt-auto d-flex justify-content-between align-items-end pt-2 border-top border-light">
                <div class="d-flex align-items-center">
                    <?= $priceLevel ?>
                    <?= $extraTag ?>
                </div>
                
                <!-- Статус работы (точкой) -->
                <?php if(!empty($post['worktime'])):
                    $status = getWorkStatus($post['worktime']);
                    $dotColor = $status['status'] === 'open' ? 'success' : 'danger';
                    $titleStatus = $status['status'] === 'open' ? 'Открыто' : 'Закрыто';
                ?>
                    <div class="ms-auto" title="<?= $titleStatus ?>" data-bs-toggle="tooltip">
                        <span class="d-inline-block rounded-circle bg-<?= $dotColor ?>" style="width: 8px; height: 8px;"></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>