<?php
// category.php - Страница категории (Full Version)
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

// Параметры запроса
$slug = $_GET['slug'] ?? '';
$sort = $_GET['sort'] ?? 'rating'; // rating | date | title
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Постов на странице
$offset = ($page - 1) * $limit;

echo '<div class="col-md-9">';

// 1. Получаем текущую категорию
$stmt = $pdo->prepare("SELECT * FROM categories WHERE cat_slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    echo "<div class='alert alert-warning'>Категория не найдена. <a href='index.php'>На главную</a></div>";
    echo "</div>";
    require_once 'templates/footer.php';
    exit;
}

// 2. Логика родительских и дочерних категорий
$cat_ids = [$category['cat_id']]; // Массив ID категорий для поиска
$subcategories = [];
$parent = null;

// Если это родительская категория -> ищем детей и добавляем их ID в поиск
if ($category['cat_parent_id'] === NULL) {
    $stmtChildren = $pdo->prepare("SELECT * FROM categories WHERE cat_parent_id = ? ORDER BY cat_name");
    $stmtChildren->execute([$category['cat_id']]);
    $subcategories = $stmtChildren->fetchAll();
    
    foreach ($subcategories as $sub) {
        $cat_ids[] = $sub['cat_id'];
    }
} else {
    // Если это дочерняя категория -> получаем родителя для хлебных крошек
    $stmtParent = $pdo->prepare("SELECT * FROM categories WHERE cat_id = ?");
    $stmtParent->execute([$category['cat_parent_id']]);
    $parent = $stmtParent->fetch();
}

// 3. Формируем SQL запрос для постов
// Используем IN (?,?,?) для поиска по всем подкатегориям сразу
$placeholders = str_repeat('?,', count($cat_ids) - 1) . '?';

// Базовый SQL
$sql = "SELECT p.* FROM post p 
        JOIN s_categories sc ON p.post_id = sc.post_id 
        WHERE sc.cat_id IN ($placeholders) AND p.status = 1 ";

// Группируем, чтобы избежать дублей (если пост в нескольких подкатегориях)
$sql .= "GROUP BY p.post_id ";

// Сортировка
switch ($sort) {
    case 'date': $sql .= "ORDER BY p.created_at DESC "; break;
    case 'title': $sql .= "ORDER BY p.title ASC "; break;
    default: $sql .= "ORDER BY p.rating_avg DESC, p.rating_count DESC "; break; // rating
}

// Пагинация
$sql .= "LIMIT $limit OFFSET $offset";

// Выполняем запрос
$stmtPosts = $pdo->prepare($sql);
$stmtPosts->execute($cat_ids);
$posts = $stmtPosts->fetchAll();

// 4. Считаем общее кол-во для пагинации (упрощенно, без учета группировки в count)
// Для точного подсчета с GROUP BY нужен подзапрос, но для простоты сделаем DISTINCT
$sqlCount = "SELECT COUNT(DISTINCT p.post_id) FROM post p 
             JOIN s_categories sc ON p.post_id = sc.post_id 
             WHERE sc.cat_id IN ($placeholders) AND p.status = 1";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($cat_ids);
$total_posts = $stmtCount->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// --- ВЕРСТКА ---
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-light p-2 rounded">
    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Главная</a></li>
    <?php if ($parent): ?>
        <li class="breadcrumb-item">
            <a href="category.php?slug=<?= h($parent['cat_slug']) ?>" class="text-decoration-none"><?= h($parent['cat_name']) ?></a>
        </li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?= h($category['cat_name']) ?></li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold mb-0"><?= h($category['cat_name']) ?> <span class="text-muted fs-5">(<?= $total_posts ?>)</span></h2>
    
    <form method="GET" class="d-flex align-items-center">
        <input type="hidden" name="slug" value="<?= h($slug) ?>">
        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; min-width: 150px;">
            <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>⭐ По рейтингу</option>
            <option value="date" <?= $sort == 'date' ? 'selected' : '' ?>>🆕 Сначала новые</option>
            <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>🔤 По названию</option>
        </select>
    </form>
</div>

<?php if (!empty($subcategories)): ?>
    <div class="mb-4">
        <?php foreach ($subcategories as $sub): ?>
            <a href="category.php?slug=<?= h($sub['cat_slug']) ?>" class="btn btn-outline-primary rounded-pill btn-sm me-1 mb-2">
                <?= h($sub['cat_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($posts)): ?>
        <div class="col-12 py-5 text-center text-muted">
            <i class="fa-solid fa-store-slash fa-3x mb-3"></i>
            <p>В этой категории пока нет заведений.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php include 'templates/card.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?slug=<?= $slug ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>">Назад</a>
            </li>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?slug=<?= $slug ?>&sort=<?= $sort ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?slug=<?= $slug ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>">Вперед</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php
echo '</div>'; // Закрываем col-md-9
require_once 'templates/footer.php';
?>