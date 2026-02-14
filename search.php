<?php
// search.php - Продвинутый поиск
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

// Получаем параметры из URL
$q = trim($_GET['q'] ?? '');
$cat_id = isset($_GET['cat_id']) && is_numeric($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$sort = $_GET['sort'] ?? 'rating'; // rating | date

echo '<div class="col-md-9">';

// --- 1. БЛОК ФИЛЬТРОВ И ПОИСКА ---
?>
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form action="search.php" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Поиск (название, описание...)" value="<?= h($q) ?>">
            </div>
            
            <div class="col-md-3">
                <select name="cat_id" class="form-select">
                    <option value="0">Все категории</option>
                    <?php
                    // Получаем все категории для фильтра (можно оптимизировать, но пока так)
                    $catsStmt = $pdo->query("SELECT * FROM categories ORDER BY cat_name");
                    while($c = $catsStmt->fetch()) {
                        $selected = ($c['cat_id'] == $cat_id) ? 'selected' : '';
                        echo "<option value='{$c['cat_id']}' $selected>" . h($c['cat_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>⭐ По рейтингу</option>
                    <option value="date" <?= $sort == 'date' ? 'selected' : '' ?>>🆕 Сначала новые</option>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Найти</button>
            </div>
        </form>
    </div>
</div>

<?php
// --- 2. ЛОГИКА ПОИСКА ---

if (mb_strlen($q) < 2 && $cat_id == 0) {
    echo "<div class='alert alert-info'><i class='fa-solid fa-circle-info'></i> Введите поисковый запрос или выберите категорию.</div>";
} else {
    // Строим динамический запрос
    $sql = "SELECT p.* FROM post p ";
    $params = [];

    // Если выбрана категория, джойним таблицу связей
    if ($cat_id > 0) {
        $sql .= "JOIN s_categories sc ON p.post_id = sc.post_id ";
    }

    $sql .= "WHERE p.status = 1 ";

    // Условие по тексту (если есть)
    if (!empty($q)) {
        // Разбиваем запрос на слова для поиска совпадений по частям (AND логика)
        $words = explode(' ', $q);
        $words = array_filter($words, function($w) { return trim($w) !== ''; });

        foreach ($words as $word) {
            $word = trim($word);
            // Каждое слово должно встречаться или в названии, или в псевдониме
            $sql .= "AND (p.title LIKE ? OR p.psevdonim LIKE ?) ";
            $params[] = "%$word%";
            $params[] = "%$word%";
        }
    }

    // Условие по категории (если есть)
    if ($cat_id > 0) {
        $sql .= "AND sc.cat_id = ? ";
        $params[] = $cat_id;
    }

    // Группировка (на случай дублей при джойнах, хотя тут не должно быть)
    $sql .= "GROUP BY p.post_id ";

    // Сортировка
    if ($sort === 'date') {
        $sql .= "ORDER BY p.created_at DESC";
    } else {
        $sql .= "ORDER BY p.rating_avg DESC, p.rating_count DESC";
    }

    // Выполнение
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // --- 3. ВЫВОД РЕЗУЛЬТАТОВ ---
    
    echo "<h4 class='mb-4'>Найдено результатов: " . count($posts) . "</h4>";

    if (empty($posts)) {
        echo "<div class='text-center py-5'>";
        echo "<i class='fa-solid fa-magnifying-glass fa-3x text-muted mb-3'></i>";
        echo "<p class='text-muted'>К сожалению, по вашему запросу ничего не найдено.</p>";
        echo "<p>Попробуйте изменить параметры поиска или категорию.</p>";
        echo "</div>";
    } else {
        echo '<div class="row">';
        foreach ($posts as $post) {
            // Используем твой улучшенный card.php
            include 'templates/card.php';
        }
        echo '</div>';
    }
}

echo '</div>'; // col-md-9
require_once 'templates/footer.php';
?>