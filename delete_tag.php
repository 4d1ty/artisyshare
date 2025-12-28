<?php

require_once __DIR__ . "/init.php";


if (!$user || $user['role'] !== 'moderator' && $user['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }
    $tag_id = $_POST['tag_id'] ?? '';
    if (empty($tag_id) || !is_numeric($tag_id)) {
        $_SESSION['flash_messages'][] = "Invalid Tag ID.";
        header("Location: mod.php");
        exit;
    }
    // Delete tag
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$tag_id]);
    $_SESSION['flash_messages'][] = "Tag deleted successfully.";
    header("Location: mod.php");
} else {
    http_response_code(405);
    die("Method not allowed.");
}