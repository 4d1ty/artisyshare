<?php
require_once __DIR__ . "/init.php";
require_once __DIR__ . "/utils/auth.php";

$artwork_id = (int)($_GET['id'] ?? 0);
if ($artwork_id <= 0) {
    http_response_code(404);
    die("Artwork not found.");
}

require_once __DIR__ . "/db.php";

/* Fetch artwork */
$stmt = $pdo->prepare("
    SELECT a.*, u.username
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$artwork_id]);
$artwork = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artwork) {
    http_response_code(404);
    die("Artwork not found.");
}

/* Access control */
if (
    $artwork['status'] !== 'approved'
    && (!$user || $user['id'] !== $artwork['user_id'])
) {
    http_response_code(403);
    die("This artwork is not public.");
}

/* Fetch tags */
$stmt = $pdo->prepare("
    SELECT t.name
    FROM tags t
    JOIN artwork_tags at ON at.tag_id = t.id
    WHERE at.artwork_id = ?
    ORDER BY t.name
");
$stmt->execute([$artwork_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

$title = htmlspecialchars($artwork['title']);

include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";
?>

<h2><?= htmlspecialchars($artwork['title']) ?></h2>

<p>
    by
    <a href="profile.php?u=<?= urlencode($artwork['username']) ?>">
        <?= htmlspecialchars($artwork['username']) ?>
    </a>
</p>

<p>
    Status: <?= htmlspecialchars(ucfirst($artwork['status'])) ?>
</p>

<?php if (in_array("nsfw", $tags)): ?>
    <p><strong>⚠ NSFW</strong></p>
<?php endif; ?>

<img
    src="<?= htmlspecialchars($artwork['image_path']) ?>"
    alt="<?= htmlspecialchars($artwork['title']) ?>"
    style="max-width: 100%; height: auto;"
>

<?php if (!empty($artwork['description'])): ?>
    <p><?= nl2br(htmlspecialchars($artwork['description'])) ?></p>
<?php endif; ?>

<?php if ($tags): ?>
    <p>
        Tags:
        <?php foreach ($tags as $tag): ?>
            <a href="index.php?tag=<?= urlencode($tag) ?>">
                <?= htmlspecialchars($tag) ?>
            </a> 
        <?php endforeach; ?>
    </p>
<?php endif; ?>

<p>
    Uploaded on <?= htmlspecialchars($artwork['created_at']) ?>
</p>

<?php if ($artwork['status'] === 'rejected'): ?>
    <div class="rejection">
        <strong>Rejected:</strong>
        <?= htmlspecialchars($artwork['rejection_reason']) ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . "/templates/footer.php"; ?>
