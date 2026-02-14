<div class="col-md-6 col-lg-4 mb-4">
    <div class="card post-card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
        
        <div class="position-relative">
            <a href="post.php?slug=<?= h($post['slug']) ?>" class="d-block">
                <img src="<?= h($post['photo']) ?>" class="card-img-top" alt="<?= h($post['title']) ?>" style="height: 220px; object-fit: cover;">
            </a>
            
            <div class="position-absolute top-0 start-0 m-3">
                <?php 
                $ratingColor = 'bg-secondary';
                if ($post['rating_avg'] >= 4.5) $ratingColor = 'bg-success';
                elseif ($post['rating_avg'] >= 3.5) $ratingColor = 'bg-warning text-dark';
                elseif ($post['rating_avg'] > 0) $ratingColor = 'bg-danger';
                ?>
                <span class="badge <?= $ratingColor ?> shadow-sm">
                    <?= number_format($post['rating_avg'], 1) ?> <i class="fa-solid fa-star"></i>
                </span>
            </div>

            <?php if(is_logged_in() && $_SESSION['user_type'] === 'user'): ?>
            <button class="btn btn-light btn-sm position-absolute top-0 end-0 m-3 rounded-circle shadow-sm text-danger border-0" title="В избранное">
                <i class="fa-regular fa-heart"></i>
            </button>
            <?php endif; ?>
        </div>

        <div class="card-body d-flex flex-column p-4">
            
            <h5 class="card-title fw-bold mb-1">
                <a href="post.php?slug=<?= h($post['slug']) ?>" class="text-dark text-decoration-none stretched-link">
                    <?= h($post['title']) ?>
                </a>
            </h5>

            <p class="text-muted small mb-2">
                <i class="fa-solid fa-location-dot text-primary me-1"></i>
                <?= h(mb_strimwidth($post['address'], 0, 40, "...")) ?>
            </p>

            <!-- Статус работы в карточке -->
            <?php if(!empty($post['worktime'])):
                $status = getWorkStatus($post['worktime']);
            ?>
                <div class="mb-2 small">
                    <span class="badge bg-<?= $status['color'] ?> bg-opacity-10 text-<?= $status['color'] ?> border border-<?= $status['color'] ?>">
                        <?= $status['text'] ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="mb-3 text-warning small">
                <?php 
                $rating = round($post['rating_avg']); 
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        echo '<i class="fa-solid fa-star"></i>';
                    } else {
                        echo '<i class="fa-regular fa-star text-secondary opacity-25"></i>';
                    }
                }
                ?>
                <span class="text-muted ms-1 small">(<?= $post['rating_count'] ?>)</span>
            </div>

            <p class="card-text text-secondary small flex-grow-1" style="line-height: 1.6;">
                <?= h(mb_strimwidth($post['description'], 0, 100, "...")) ?>
            </p>
        </div>

        <div class="card-footer bg-white border-0 px-4 pb-4 pt-0">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fa-regular fa-eye"></i> <?= $post['views'] ?>
                </small>
                <span class="text-primary fw-bold small text-uppercase">
                    Подробнее <i class="fa-solid fa-arrow-right ms-1"></i>
                </span>
            </div>
        </div>

    </div>
</div>