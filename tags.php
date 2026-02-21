<?php
require_once 'headers.php';

try {
    $stmt = $pdo->query("SELECT attr_id, attr_name, attr_icon FROM tags ORDER BY attr_name ASC");
    $tags = $stmt->fetchAll();

    response(true, 'Tags list', $tags);

} catch (Exception $e) {
    response(false, $e->getMessage());
}
?>
