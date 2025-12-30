<?php
require_once __DIR__ . "/init.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed.");
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die("Forbidden");
}
require_once __DIR__ . "/utils/auth.php";

$artwork_id = (int)($_POST['artwork_id'] ?? 0);
if ($artwork_id <= 0) {
    http_response_code(400);
    die("Invalid artwork ID.");
}
// Fetch artwork to verify ownership
$stmt = $pdo->prepare("SELECT * FROM artworks WHERE id = ? AND user_id = ?");
$stmt->execute([$artwork_id, $user['id']]);
$artwork = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$artwork) {
    http_response_code(403);
    die("You do not have permission to delete this artwork.");
}
// Delete artwork
$stmt = $pdo->prepare("DELETE FROM artworks WHERE id = ?");
$stmt->execute([$artwork_id]);
// Optionally, delete associated tags and files here
unlink(__DIR__ . '/' . $artwork['image_path']);
unlink(__DIR__ . '/' . $artwork['thumbnail_path']);
$_SESSION['flash_messages'][] = "Artwork deleted successfully.";
header("Location: index.php");
exit;