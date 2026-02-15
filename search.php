<?php
// search.php
require_once 'templates/header.php';

$q = trim($_GET['q'] ?? '');
$cat_id = isset($_GET['cat_id']) && is_numeric($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$sort = $_GET['sort'] ?? 'rating';

// Сайдбар для поиска (фильтры)
?>
<div class="col-lg-3 mb-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px;">
        <h5 class="fw-bold mb-4">Фильтры</h5>
        <form action="search.php" method="GET">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Поиск</label>
                <input type="text" name="q" class="form-control bg-light border-0" value="<?= h($q) ?>" placeholder="Название...">
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Категория</label>
                <select name="cat_id" class="form-select bg-light border-0">
                    <option value="0">Все категории</option>
                    <?php
                    $catsStmt = $pdo->query("SELECT * FROM categories ORDER BY cat_name");
                    while($c = $catsStmt->fetch()) {
                        $selected = ($c['cat_id'] == $cat_id) ? 'selected' : '';
                        echo "<option value='{$c['cat_id']}' $selected>" . h($c['cat_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Сортировка</label>
                <select name="sort" class="form-select bg-light border-0">
                    <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                    <option value="date" <?= $sort == 'date' ? 'selected' : '' ?>>Сначала новые</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Применить</button>
            <a href="search.php" class="btn btn-link text-muted w-100 btn-sm text-decoration-none mt-2">Сбросить</a>
        </form>
    </div>
</div>

<div class="col-lg-9">
<?php
if (mb_strlen($q) < 2 && $cat_id == 0) {
    echo "<div class='alert alert-light border text-center py-5'>Введите запрос или выберите категорию для поиска.</div>";
} else {
    $sql = "SELECT p.* FROM post p ";
    $params = [];
    if ($cat_id > 0) $sql .= "JOIN s_categories sc ON p.post_id = sc.post_id ";
    $sql .= "WHERE p.status = 1 ";

    if (!empty($q)) {
        $cleanQ = preg_replace('/[^\p{L}\p{N}\s]/u', '', $q);
        $words = explode(' ', $cleanQ);
        $words = array_filter($words, function($w) { return mb_strlen(trim($w)) > 1; });
        if (!empty($words)) {
            $booleanQuery = '';
            foreach ($words as $word) $booleanQuery .= '+' . trim($word) . '* ';
            $sql .= "AND MATCH(p.title, p.psevdonim) AGAINST(? IN BOOLEAN MODE) ";
            $params[] = trim($booleanQuery);
        }
    }
    if ($cat_id > 0) {
        $sql .= "AND sc.cat_id = ? ";
        $params[] = $cat_id;
    }
    $sql .= "GROUP BY p.post_id ";
    if ($sort === 'date') $sql .= "ORDER BY p.created_at DESC";
    else $sql .= "ORDER BY p.rating_avg DESC, p.rating_count DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    echo "<h4 class='mb-4 fw-bold'>Результаты поиска <span class='text-muted fw-normal'>(".count($posts).")</span></h4>";

    if (empty($posts)) {
        echo "<div class='text-center py-5 text-muted'>Ничего не найдено. Попробуйте другой запрос.</div>";
    } else {
        echo '<div class="row gx-4">';
        foreach ($posts as $post) include 'templates/card.php';
        echo '</div>';
    }
}
?>
</div>

<?php require_once 'templates/footer.php'; ?>