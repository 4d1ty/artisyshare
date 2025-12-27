<?php

require_once __DIR__ . "/init.php";

// Handle tag suggestion AJAX request
$input = trim($_GET['q'] ?? '');
$suggestions = [];

if ($input !== '') {
    $stmt = $pdo->prepare("
        SELECT t.name
        FROM tags t
        LEFT JOIN artwork_tags at ON t.id = at.tag_id
        GROUP BY t.id
        HAVING COUNT(at.artwork_id) > 0 AND t.name LIKE ?
        ORDER BY COUNT(at.artwork_id) DESC, t.name ASC
        LIMIT 10
    ");
    $likeInput = $input . '%';
    $stmt->execute([$likeInput]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

header('Content-Type: application/json');
echo json_encode($suggestions);
exit;
