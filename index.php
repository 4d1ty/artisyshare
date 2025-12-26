<?php
require_once __DIR__ . "/init.php";

$title = "Home";

$query = trim($_GET['q'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$tags_filter = $_GET['tags'] ?? [];
// $tags_filter = array_filter(array_map('trim', $tags_filter));
if($tag !== '') {
    $tags_filter[] = $tag;
}


// Build the SQL query
$sql = "
    SELECT a.*, u.username, a.thumbnail_path
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    WHERE a.status = 'approved'
";
$params = [];
if ($query !== '') {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $likeQuery = '%' . $query . '%';
    $params[] = $likeQuery;
    $params[] = $likeQuery;
}

if (!empty($tags_filter)) {
    $placeholders = implode(',', array_fill(0, count($tags_filter), '?'));
    $sql .= "
        AND a.id IN (
            SELECT at.artwork_id
            FROM artwork_tags at
            JOIN tags t ON at.tag_id = t.id
            WHERE t.name IN ($placeholders)
            GROUP BY at.artwork_id
            HAVING COUNT(DISTINCT t.name) = ?
        )
    ";
    $params = array_merge($params, $tags_filter);
    $params[] = count($tags_filter);
}

$sort = $_GET['sort'] ?? 'newest';
if ($sort === 'oldest') {
    $sql .= " ORDER BY a.created_at ASC";
} else {
    $sql .= " ORDER BY a.created_at DESC";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);


include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";
?>

<h3>Welcome <?= htmlspecialchars($user['username'] ?? "Guest") ?></h3>


<form id="filter-form" action="index.php" method="get">
    <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($_GET['q'] ?? "", ENT_QUOTES) ?>">

    <div class="tags-filter">
        <?php foreach ($all_tags as $tag): ?>
            <label>
                <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tag, ENT_QUOTES) ?>"
                    <?= ((isset($_GET['tag']) && $_GET['tag'] == $tag) || (isset($_GET['tags']) && in_array($tag, $_GET['tags']))) ? 'checked' : '' ?>>
                <?= htmlspecialchars($tag, ENT_QUOTES) ?>
            </label>
        <?php endforeach; ?>
    </div>
    <select name="sort">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
    </select>

    <button type="submit">Search</button>
</form>

<?php if (empty($artworks)): ?>
    <p>No artworks yet. Be the first to upload!</p>
<?php else: ?>
    <div class="gallery">
        <?php foreach ($artworks as $art): ?>
            <div class="art-card">
                <a href="artwork.php?id=<?= $art['id'] ?>">
                    <img
                        src="/<?= htmlspecialchars($art['thumbnail_path']) ?>"
                        alt="<?= htmlspecialchars($art['title']) ?>"
                        loading="lazy">
                </a>

                <div class="art-meta">
                    <strong><?= htmlspecialchars($art['title']) ?></strong><br>
                    by
                    <a href="profile.php?u=<?= urlencode($art['username']) ?>">
                        <?= htmlspecialchars($art['username']) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include __DIR__ . "/templates/footer.php"; ?>