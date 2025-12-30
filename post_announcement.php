<?php

require_once __DIR__ . "/init.php";


if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $show_until = trim($_POST['show_until'] ?? '');
    $post_as = trim($_POST['post_as'] ?? '');

    if (empty($title) || empty($content)) {
        $_SESSION['flash_messages'][] = "Title and content are required.";
        header("Location: admin.php");
        exit;
    }

    $created_by_user_id = $user['id'];
    if ($post_as == "system") {
        // Verify that the system user to post as exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$post_as]);
        $post_as_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post_as_user) {
            $created_by_user_id = $post_as_user['id'];
        } else {
            $_SESSION['flash_messages'][] = "System User to post as not found.";
            header("Location: admin.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, show_until, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $content, $show_until ?: null, $created_by_user_id]);


    $_SESSION['flash_messages'][] = "Announcement posted successfully.";
    header("Location: admin.php");
} else {
    http_response_code(405);
    die("Method not allowed.");
}
