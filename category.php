<?php
// category.php
require_once 'templates/header.php';

$slug = $_GET['slug'] ?? '';
$sort = $_GET['sort'] ?? 'rating'; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Больше постов на странице для Full Width
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE cat_slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    echo "<div class='col-12 text-center py-5'>Категория не найдена.</div>";
    require_once 'templates/footer.php';
    exit;
}

// Сайдбар с категориями
?>
<div class="col-lg-2 d-none d-lg-block mb-4">
    <div class="sticky-top" style="top: 100px;">
        <h6 class="fw-bold text-uppercase text-muted small mb-3 ls-1">Категории</h6>
        <?php require 'templates/sidebar.php'; ?>
    </div>
</div>

<div class="col-lg-10 col-12">
    <?php
    // Логика получения постов
    $cat_ids = [$category['cat_id']];
    $subcategories = [];
    $parent = null;

    if ($category['cat_parent_id'] === NULL) {
        $stmtChildren = $pdo->prepare("SELECT * FROM categories WHERE cat_parent_id = ? ORDER BY cat_name");
        $stmtChildren->execute([$category['cat_id']]);
        $subcategories = $stmtChildren->fetchAll();
        foreach ($subcategories as $sub) $cat_ids[] = $sub['cat_id'];
    } else {
        $stmtParent = $pdo->prepare("SELECT * FROM categories WHERE cat_id = ?");
        $stmtParent->execute([$category['cat_parent_id']]);
        $parent = $stmtParent->fetch();
    }

    $placeholders = str_repeat('?,', count($cat_ids) - 1) . '?';
    $sql = "SELECT p.* FROM post p JOIN s_categories sc ON p.post_id = sc.post_id WHERE sc.cat_id IN ($placeholders) AND p.status = 1 GROUP BY p.post_id ";
    switch ($sort) {
        case 'date': $sql .= "ORDER BY p.created_at DESC "; break;
        case 'title': $sql .= "ORDER BY p.title ASC "; break;
        default: $sql .= "ORDER BY p.rating_avg DESC, p.rating_count DESC "; break;
    }
    $sql .= "LIMIT $limit OFFSET $offset";

    $stmtPosts = $pdo->prepare($sql);
    $stmtPosts->execute($cat_ids);
    $posts = $stmtPosts->fetchAll();
    
    // Подсчет страниц
    $sqlCount = "SELECT COUNT(DISTINCT p.post_id) FROM post p JOIN s_categories sc ON p.post_id = sc.post_id WHERE sc.cat_id IN ($placeholders) AND p.status = 1";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($cat_ids);
    $total_posts = $stmtCount->fetchColumn();
    $total_pages = ceil($total_posts / $limit);
    ?>

    <!-- Заголовок и фильтры -->
    <div class="bg-white p-4 rounded-4 shadow-sm mb-4 d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <!-- Исправлено: href="/" -->
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Главная</a></li>
                    <?php if ($parent): ?>
                        <!-- Исправлено: ЧПУ -->
                        <li class="breadcrumb-item"><a href="/category/<?= h($parent['cat_slug']) ?>" class="text-decoration-none"><?= h($parent['cat_name']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?= h($category['cat_name']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-0"><?= h($category['cat_name']) ?> <sup class="text-muted fw-normal fs-6"><?= $total_posts ?></sup></h1>
        </div>

        <form method="GET" class="d-flex align-items-center mt-3 mt-md-0">
            <!-- Здесь slug не нужен в input, он в URL. Но для сохранения параметров при смене сортировки через GET форму,
                 браузер перезагрузит страницу как ?slug=...&sort=... 
                 Поскольку у нас RewriteRule ^category/(.*) -> category.php?slug=$1, это допустимо, 
                 но лучше сделать JS reload или ссылки. Оставим форму рабочей, .htaccess это обработает. 
            -->
            <input type="hidden" name="slug" value="<?= h($slug) ?>">
            <select name="sort" class="form-select border-0 bg-light fw-medium" onchange="this.form.submit()">
                <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                <option value="date" <?= $sort == 'date' ? 'selected' : '' ?>>Сначала новые</option>
                <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>По названию</option>
            </select>
        </form>
    </div>

    <!-- Подкатегории -->
    <?php if (!empty($subcategories)): ?>
        <div class="mb-4 d-flex flex-wrap gap-2">
            <?php foreach ($subcategories as $sub): ?>
                <!-- Исправлено: ЧПУ -->
                <a href="/category/<?= h($sub['cat_slug']) ?>" class="btn btn-white border shadow-sm rounded-pill btn-sm px-3 hover-shadow">
                    <?= h($sub['cat_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Сетка постов -->
    <div class="row gx-4">
        <?php if (empty($posts)): ?>
            <div class="col-12 py-5 text-center text-muted">
                <div class="bg-light rounded-circle d-inline-flex p-4 mb-3"><i class="fa-solid fa-store-slash fa-2x"></i></div>
                <h5>Здесь пока пусто</h5>
                <p>В этой категории еще нет заведений.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include 'templates/card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Пагинация -->
    <?php if ($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <!-- Исправлены ссылки пагинации на абсолютные ЧПУ, чтобы не терять текущий путь -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link rounded-pill px-3 me-2" href="/category/<?= $slug ?>?sort=<?= $sort ?>&page=<?= $page - 1 ?>">Назад</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link rounded-circle mx-1" href="/category/<?= $slug ?>?sort=<?= $sort ?>&page=<?= $i ?>" style="width: 40px; height: 40px; text-align: center; line-height: 25px;"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link rounded-pill px-3 ms-2" href="/category/<?= $slug ?>?sort=<?= $sort ?>&page=<?= $page + 1 ?>">Вперед</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>