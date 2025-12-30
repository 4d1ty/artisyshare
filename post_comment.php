<?php

require_once __DIR__ . "/init.php";

if (!$user) {
    http_response_code(403);
    die("You must be logged in to post comments.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $artwork_id = (int)($_POST['artwork_id'] ?? 0);
    $comment_text = trim($_POST['comment_text'] ?? '');
    $parent_comment_id = (int)($_POST['parent_comment_id'] ?? 0);

    if ($artwork_id <= 0 || empty($comment_text)) {
        $_SESSION['flash_messages'][] = "Invalid artwork ID or empty comment.";
        header("Location: artwork.php?id=" . $artwork_id);
        exit;
    }

    if ($parent_comment_id > 0) {
        // Verify that the parent comment exists
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND artwork_id = ?");
        $stmt->execute([$parent_comment_id, $artwork_id]);
        $parent_comment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$parent_comment) {
            $_SESSION['flash_messages'][] = "Parent comment not found.";
            header("Location: artwork.php?id=" . $artwork_id);
            exit;
        }
    }

    $parent_comment = $parent_comment_id > 0 ? $parent_comment_id : null;

    $stmt = $pdo->prepare("INSERT INTO comments (artwork_id, user_id, parent_comment_id, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$artwork_id, $user['id'], $parent_comment, $comment_text]);

    $_SESSION['flash_messages'][] = "Comment posted successfully.";
    header("Location: artwork.php?id=" . $artwork_id);
} else {
    http_response_code(405);
    die("Method not allowed.");
}
