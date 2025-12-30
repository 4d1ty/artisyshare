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

    $announcement_id = (int)($_POST['announcement_id'] ?? 0);
    if ($announcement_id <= 0) {
        http_response_code(400);
        die("Invalid announcement ID.");
    }

    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$announcement_id]);
        
    $_SESSION['flash_messages'][] = "Announcement deleted successfully.";
    header("Location: admin.php");
} else {
    http_response_code(405);
    die("Method not allowed.");
}
