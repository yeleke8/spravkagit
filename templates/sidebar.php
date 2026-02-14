<div class="col-md-3 mb-4">
    <div class="sidebar">
        
        <?php 
        // --- БЛОК ПОЛЬЗОВАТЕЛЯ (Только для авторизованных) ---
        if (is_logged_in()): 
        ?>
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Вы вошли как:</small>
                        <div class="fw-bold text-truncate" style="max-width: 140px;">
                            <?= h($_SESSION['user_name']) ?>
                        </div>
                    </div>
                </div>
                
                <div class="list-group list-group-flush bg-transparent small">
                    <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent px-0 py-2 border-bottom">
                        <i class="fa-solid fa-gauge me-2 text-primary"></i> Личный кабинет
                    </a>

                    <?php if($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'owner'): ?>
                        <a href="add.php" class="list-group-item list-group-item-action bg-transparent px-0 py-2 border-bottom">
                            <i class="fa-solid fa-plus-circle me-2 text-success"></i> Добавить место
                        </a>
                    <?php endif; ?>

                    <a href="login.php?logout=1" class="list-group-item list-group-item-action bg-transparent px-0 py-2 text-muted">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Выход
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <h5 class="mb-3 fw-bold">Категории</h5>
        
        <?php 
        // 1. Получаем все категории сразу
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY cat_id ASC");
        $allCats = $stmt->fetchAll();

        // 2. Строим дерево категорий (Родитель -> Подкатегории)
        $tree = [];
        $children = [];

        foreach ($allCats as $cat) {
            if ($cat['cat_parent_id'] === NULL) {
                $tree[$cat['cat_id']] = $cat;
                $tree[$cat['cat_id']]['subs'] = [];
            } else {
                $children[] = $cat;
            }
        }

        foreach ($children as $child) {
            if (isset($tree[$child['cat_parent_id']])) {
                $tree[$child['cat_parent_id']]['subs'][] = $child;
            }
        }
        ?>

        <div class="accordion accordion-flush" id="accordionSidebar">
            <?php foreach($tree as $parent): ?>
                <div class="accordion-item bg-transparent border-0 border-bottom">
                    <h2 class="accordion-header" id="heading-<?= $parent['cat_id'] ?>">
                        <?php if(empty($parent['subs'])): ?>
                            <a href="category.php?slug=<?= h($parent['cat_slug']) ?>" class="accordion-button collapsed shadow-none bg-transparent text-decoration-none text-dark fw-bold px-0 d-block">
                                <?= h($parent['cat_name']) ?>
                            </a>
                        <?php else: ?>
                            <button class="accordion-button collapsed shadow-none bg-transparent px-0 text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $parent['cat_id'] ?>" aria-expanded="false">
                                <?= h($parent['cat_name']) ?>
                            </button>
                        <?php endif; ?>
                    </h2>
                    
                    <?php if(!empty($parent['subs'])): ?>
                        <div id="collapse-<?= $parent['cat_id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionSidebar">
                            <div class="accordion-body py-2 ps-3">
                                <ul class="list-unstyled mb-0 border-start border-2 ps-2 border-primary border-opacity-25">
                                    <li class="mb-1">
                                        <a href="category.php?slug=<?= h($parent['cat_slug']) ?>" class="text-decoration-none text-secondary small d-block py-1">
                                            Все в категории «<?= h($parent['cat_name']) ?>»
                                        </a>
                                    </li>
                                    <?php foreach($parent['subs'] as $sub): ?>
                                        <li>
                                            <a href="category.php?slug=<?= h($sub['cat_slug']) ?>" class="text-decoration-none text-dark d-block py-1">
                                                <?= h($sub['cat_name']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>