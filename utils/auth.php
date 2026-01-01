<?php

require_once __DIR__ . "/../db.php";


$user_id = $_SESSION['user_id'] ?? null;
$user = null;
if (!is_null($user_id)) {
    $stmt = $pdo->prepare("SELECT id, username, role, per_day_upload_limit, per_artwork_size_limit FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if($result) {
        $user = $result;
    } else {
        // User not found, clear invalid user_id from session
        unset($_SESSION['user_id']);
    }
}
