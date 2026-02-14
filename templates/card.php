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
        
        $priceLevel = '<span class="badge bg-light text-dark border fw-normal me-1" title="Средний чек: ~'.$check.'">' . $priceSym . '</span>';
    }
}

// 2. Логика для звезд отеля
$hotelStars = '';
if (isset($attrs['stars_count']) && $attrs['stars_count'] > 0) {
    $hotelStars = '<span class="text-warning small me-1">' . str_repeat('<i class="fa-solid fa-star"></i>', $attrs['stars_count']) . '</span>';
}

// 3. Дополнительный тег (Кухня или Тип)
$extraTag = '';
if (isset($attrs['cuisine'])) {
    // Берем первое слово (например "Европейская, Итальянская" -> "Европейская")
    $cuisineParts = explode(',', $attrs['cuisine']);
    $extraTag = '<span class="text-muted small border-start ps-2 ms-1">' . h(trim($cuisineParts[0])) . '</span>';
}
?>

<div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100 border-0 shadow-sm rounded-4 position-relative bg-white" style="transition: transform 0.2s; box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;">
        <div class="card-body p-3 d-flex flex-column">
            
            <!-- ВЕРХНИЙ БЛОК: Основная инфа -->
            <div class="d-flex mb-3">
                <!-- Фото (Квадрат со скруглением) -->
                <div class="flex-shrink-0 position-relative">
                    <a href="post.php?slug=<?= h($post['slug']) ?>">
                        <img src="<?= h($post['photo']) ?>" alt="<?= h($post['title']) ?>" 
                             class="rounded-4 bg-light shadow-sm" 
                             style="width: 80px; height: 80px; object-fit: cover;">
                    </a>
                    <!-- Рейтинг поверх фото (как уведомление) -->
                    <?php if($post['rating_avg'] > 0): ?>
                        <div class="position-absolute bottom-0 start-50 translate-middle-x mb-1 bg-white bg-opacity-90 backdrop-blur rounded-pill px-2 py-0 border shadow-sm" style="font-size: 0.7rem; white-space: nowrap;">
                            <i class="fa-solid fa-star text-warning"></i> <b><?= number_format($post['rating_avg'], 1) ?></b>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Текстовая часть -->
                <div class="ms-3 flex-grow-1 overflow-hidden d-flex flex-column justify-content-center">
                    
                    <!-- Звезды отеля (если есть) -->
                    <?php if($hotelStars): ?>
                        <div class="mb-0 lh-1"><?= $hotelStars ?></div>
                    <?php endif; ?>

                    <h6 class="fw-bold mb-1 text-truncate fs-6 text-dark">
                        <a href="post.php?slug=<?= h($post['slug']) ?>" class="text-dark text-decoration-none stretched-link">
                            <?= h($post['title']) ?>
                        </a>
                    </h6>
                    
                    <p class="text-muted small mb-1 text-truncate opacity-75">
                        <i class="fa-solid fa-location-dot me-1 text-primary opacity-50"></i>
                        <?= h($post['address']) ?>
                    </p>

                    <!-- Цена и Тип -->
                    <div class="d-flex align-items-center small">
                        <?= $priceLevel ?>
                        <?= $extraTag ?>
                    </div>
                </div>

                <!-- Кнопка Like (абсолютно справа) -->
                <?php 
                $isFav = false;
                if(is_logged_in() && isset($myFavs)) {
                    $isFav = in_array($post['post_id'], $myFavs);
                }
                ?>
                <button class="btn btn-link p-0 ms-1 btn-favorite position-relative z-2 text-decoration-none align-self-start" 
                        data-id="<?= $post['post_id'] ?>" 
                        style="width: 24px; height: 24px;">
                    <i class="<?= $isFav ? 'fa-solid text-danger' : 'fa-regular text-muted opacity-50' ?> fa-heart fa-lg hover-scale"></i>
                </button>
            </div>

            <!-- "ПЕРФОРАЦИЯ" (Разделитель билета) -->
            <div class="position-relative w-100 my-2">
                <hr class="border-top border-secondary border-opacity-25 border-dashed m-0">
                <!-- Декоративные полукруги по бокам (имитация отрыва) -->
                <div class="position-absolute top-50 start-0 translate-middle bg-light rounded-circle" style="width: 16px; height: 16px; margin-left: -16px;"></div> <!-- margin-left отрицательный, чтобы "выесть" кусок -->
                <div class="position-absolute top-50 end-0 translate-middle bg-light rounded-circle" style="width: 16px; height: 16px; margin-right: -32px;"></div> <!-- Нужно корректировать под фон -->
            </div>

            <!-- НИЖНИЙ БЛОК: Статусы -->
            <div class="d-flex justify-content-between align-items-center mt-1">
                
                <!-- Просмотры -->
                <small class="text-muted" style="font-size: 0.75rem;">
                    <i class="fa-regular fa-eye me-1 opacity-50"></i> <?= $post['views'] ?>
                </small>

                <!-- Статус работы -->
                <?php if(!empty($post['worktime'])):
                    $status = getWorkStatus($post['worktime']);
                    $dotColor = $status['status'] === 'open' ? 'success' : 'danger';
                    $statusText = $status['status'] === 'open' ? 'Открыто' : 'Закрыто';
                ?>
                    <div class="d-flex align-items-center bg-light rounded-pill px-2 py-1 border border-light">
                        <span class="d-inline-block rounded-circle bg-<?= $dotColor ?> me-2" style="width: 6px; height: 6px;"></span>
                        <span class="small fw-medium text-secondary" style="font-size: 0.75rem;"><?= $statusText ?></span>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>
</div>