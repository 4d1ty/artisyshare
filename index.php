<?php
require_once __DIR__ . "/init.php";

$title = "Home";

$query = trim($_GET['q'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$tags_filter = $_GET['tags'] ?? [];
$user_filter = $_GET['u'] ?? null;
$params = [];

// $tags_filter = array_filter(array_map('trim', $tags_filter));
if ($tag !== '') {
    $tags_filter[] = $tag;
}

// If query includes user:username, extract it, the username pattern allows alphanumeric and underscores and dots
$user_pattern = '/user:([a-zA-Z0-9_.]+)/';
if (preg_match($user_pattern, $query, $matches)) {
    $user_filter = $matches[1];
    $query = trim(preg_replace($user_pattern, '', $query));
}

$user_filter_user = null;
if (isset($user_filter) && $user_filter !== '') {
    $stmt = $pdo->prepare("SELECT id, username, bio FROM users WHERE username = ?");
    $stmt->execute([$user_filter]);
    $user_filter_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Build the SQL query
$sql = "
SELECT a.*, u.username, a.thumbnail_path
FROM artworks a
JOIN users u ON a.user_id = u.id
WHERE a.status = 'approved'
";


if (isset($user_filter) && $user_filter !== '') {
    // $query .= ' user:' . $user_filter;
    $sql .= " AND u.username = ? ";
    $params[] = $user_filter;
}


if ($query !== '') {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR u.username LIKE ?) ";
    $likeQuery = '%' . $query . '%';
    $params[] = $likeQuery;
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

echo "<!-- SQL Query: " . htmlspecialchars($sql) . " -->";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);



$title_parts = [];
if ($query !== '') {
    $title_parts[] = "Search: " . htmlspecialchars($query);
}
if ($user_filter_user) {
    $title_parts[] = "by " . htmlspecialchars($user_filter_user['username']);
}
if (!empty($tags_filter)) {
    $title_parts[] = "Tags: " . htmlspecialchars(implode(", ", $tags_filter));
}
if (!empty($title_parts)) {
    $title .= " - " . implode(" | ", $title_parts);
}

$title .= " - ArtisyShare";


include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";


?>

<h3>Welcome <?= htmlspecialchars($user['username'] ?? "Guest") ?></h3>

<form id="filter-form" action="index.php" method="get">
    <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($_GET['q'] ?? $query ?? "", ENT_QUOTES) ?>">
    <select name="sort" id="sort-select">
        <option value="newest" <?=
                                (isset($_GET['sort']) && $_GET['sort'] === 'newest') || !isset($_GET['sort']) ? 'selected' : '' ?>>Newest</option>
        <option value="oldest"
            <?= (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'selected' : '' ?>>Oldest</option>
    </select>
    <?php if (isset($user_filter_user) && !preg_filter($user_pattern, $query, $matches)): ?>
        <input type="hidden" name="u" value="<?= htmlspecialchars($user_filter_user['username'] ?? "", ENT_QUOTES) ?>">
    <?php endif; ?>
    <button type="button" id="tag-filter-toggle">Tags ▼</button>
    <div class="tags-filter" style="margin: 10px 0; display: none; flex-wrap: wrap; gap: 10px;">

        <div>
            <input type="text" placeholder="Filter tags…">
        </div>
        <?php foreach ($all_tags as $tag): ?>
            <label>
                <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tag['name'], ENT_QUOTES) ?>"
                    class="tag-checkbox"
                    <?= ((isset($_GET['tag']) && $_GET['tag'] == $tag['name']) || (isset($_GET['tags']) && in_array($tag['name'], $_GET['tags']))) ? 'checked' : '' ?>>
                <?= htmlspecialchars($tag['name'], ENT_QUOTES) ?>
            </label>
        <?php endforeach; ?>
    </div>
    <button type="submit" style="margin-top: 5px;">Search</button>
</form>

<script>
    document.getElementById('tag-filter-toggle').addEventListener('click', function() {
        var filterDiv = document.querySelector('.tags-filter');
        if (filterDiv.style.display === 'none' || filterDiv.style.display === '') {
            filterDiv.style.display = 'flex';
        } else {
            filterDiv.style.display = 'none';
        }
    });
</script>

<?php if (empty($artworks)): ?>
    <p>No artworks yet. Be the first to upload!</p>
<?php else: ?>
    <?php if ($user_filter) {
        echo "<h2>Artworks by " . htmlspecialchars($user_filter) . "</h2>";
        echo "<p>About: " . htmlspecialchars($user_filter_user['bio'] ?? '') . "</p>";
    } ?>
    <div class="gallery">
        <?php foreach ($artworks as $art): ?>
            <div class="art-card">
                <a href="artwork.php?id=<?= $art['id'] ?>">
                    <img
                        src="<?= htmlspecialchars($art['thumbnail_path']) ?>"
                        alt="<?= htmlspecialchars($art['title']) ?>"
                        decoding="async"
                        loading="lazy">
                </a>

                <div class="art-meta">
                    <strong><?= htmlspecialchars($art['title']) ?></strong><br>
                    <small>Views: <?= htmlspecialchars($art['view_count']) ?></small>
                    by
                    <a href="index.php?u=<?= urlencode($art['username']) ?>">
                        <?= htmlspecialchars($art['username']) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script src="masonry.pkgd.min.js"></script>
    <script src="imagesloaded.pkgd.min.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var grid = document.querySelector('.gallery');

            imagesLoaded(grid, function() {
                var msnry = new Masonry(grid, {
                    itemSelector: '.art-card',
                    columnWidth: '.art-card',
                    gutter: 10,
                    fitWidth: true,
                    originLeft: true
                });
            });
        });
    </script>

<?php endif; ?>
<?php include __DIR__ . "/templates/footer.php"; ?>