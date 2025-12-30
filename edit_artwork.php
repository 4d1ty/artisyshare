<?php

require_once __DIR__ . "/init.php";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }
    $artwork_id = (int)($_POST['artwork_id'] ?? 0);
    if ($artwork_id <= 0) {
        http_response_code(404);
        die("Artwork not found.");
    }
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags_input = trim($_POST['tags'] ?? '');
    $tags_array = array_filter(array_map('trim', explode(',', $tags_input)));

    // Update artwork
    $stmt = $pdo->prepare("UPDATE artworks SET title = ?, description = ? WHERE id = ?");
    $stmt->execute([$title, $description, $artwork_id]);

    // Update tags
    // First, delete existing tags
    $stmt = $pdo->prepare("DELETE FROM artwork_tags WHERE artwork_id = ?");
    $stmt->execute([$artwork_id]);

    // Then, insert new tags
    foreach ($tags_array as $tag_name) {
        // Insert tag if not exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
        $stmt->execute([$tag_name]);
        // Get tag ID
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$tag_name]);
        $tag_id = $stmt->fetchColumn();
        // Associate tag with artwork
        $stmt = $pdo->prepare("INSERT INTO artwork_tags (artwork_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$artwork_id, $tag_id]);
    }

    $_SESSION['flash_messages'][] = "Artwork updated successfully.";
    rotate_csrf_token();
    header("Location: artwork.php?id=" . $artwork_id);
    exit;
}

// Get artwork ID
$artwork_id = (int)($_GET['id'] ?? 0);
if ($artwork_id <= 0) {
    http_response_code(404);
    die("Artwork not found.");
}
// Fetch artwork
$stmt = $pdo->prepare("SELECT * FROM artworks WHERE id = ? AND user_id = ?");
$stmt->execute([$artwork_id, $user['id']]);
$artwork = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$artwork) {
    http_response_code(403);
    die("You do not have permission to edit this artwork.");
}
$title = "Edit Artwork - " . htmlspecialchars($artwork['title']);
// Fetch tags
$stmt = $pdo->prepare("
    SELECT t.name
    FROM tags t
    JOIN artwork_tags at ON at.tag_id = t.id
    WHERE at.artwork_id = ?
    ORDER BY t.name
");
$stmt->execute([$artwork_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);



include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";
?>
<h2>Edit Artwork</h2>

<form action="edit_artwork.php" method="POST" enctype="multipart/form-data">
    <?= csrf_tag() ?>
    <input type="hidden" name="artwork_id" value="<?= (int)$artwork['id'] ?>">
    <label for="title">Title:</label><br>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($artwork['title']) ?>" required><br><br>

    <label for="description">Description:</label><br>
    <textarea id="description" name="description" rows="4" cols="50"><?= htmlspecialchars($artwork['description']) ?></textarea><br><br>

    <label for="tags">Tags (comma separated):</label><br>
    <input type="text" id="tags" name="tags" value="<?= htmlspecialchars(implode(", ", $tags)) ?>"><br><br>
    <img src="<?= htmlspecialchars($artwork['thumbnail_path']) ?>" alt="">
    <p>Can't change image file, please delete and re-upload it</p>
    <button type="submit">Save</button>
</form>
<?php
include __DIR__ . "/templates/footer.php";
?>