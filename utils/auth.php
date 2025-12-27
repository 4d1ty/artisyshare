<?php

require_once __DIR__ . "/../db.php";


$user_id = $_SESSION['user_id'] ?? null;
$user = null;
if (!is_null($user_id)) {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
