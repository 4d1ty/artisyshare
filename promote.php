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

    $user_id = $_POST['user_id'] ?? '';

    if (empty($user_id) || !is_numeric($user_id)) {
        $_SESSION['flash_messages'][] = "Invalid user ID.";
        header("Location: admin.php");
        exit;
    }
    // Promote user to moderator
    $stmt = $pdo->prepare("UPDATE users SET role = 'moderator' WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['flash_messages'][] = "User promoted to moderator successfully.";
    header("Location: admin.php");

} else {
    http_response_code(405);
    die("Method not allowed.");
}
