<?php


require_once __DIR__ . "/init.php";

if (!$user) {
    http_response_code(403);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $comment_id = (int)($_POST['comment_id'] ?? 0);
    if ($comment_id <= 0) {
        http_response_code(400);
        die("Invalid comment ID.");
    }

    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user['id']]);

    $_SESSION['flash_messages'][] = "Comment deleted successfully.";
    $next = isset($_POST['artwork_id']) ? "artwork.php?id=" . $_POST['artwork_id'] : 'index.php';
    header("Location: " . $next);
} else {
    http_response_code(405);
    die("Method not allowed.");
}
