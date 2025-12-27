<?php

require_once __DIR__ . "/init.php";


if (!$user || $user['role'] !== 'admin' && $user['role'] !== 'moderator') {
    http_response_code(403);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }
    $artwork_id = $_POST['artwork_id'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    $next = $_POST['next'] ?? 'mod.php';

    if (empty($artwork_id) || !is_numeric($artwork_id)) {
        $_SESSION['flash_messages'][] = "Invalid artwork ID.";
        header("Location: mod.php");
        exit;
    }

    $valid_statuses = ['approved', 'rejected', 'pending'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['flash_messages'][] = "Invalid status.";
        header("Location: mod.php");
        exit;
    }
    // Update artwork status
    $stmt = $pdo->prepare("UPDATE artworks SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $artwork_id]);
    $_SESSION['flash_messages'][] = "Artwork status updated successfully.";
    header("Location: $next");
} else {
    http_response_code(405);
    die("Method not allowed.");
}
