<?php
$site_name = "ArtisyShare";

$user = $user ?? null;

$flash_messages = $_SESSION['flash_messages'] ?? [];
unset($_SESSION['flash_messages']);

// Get all the tags and order by the number of artworks associated with each tag
$stmt = $pdo->prepare("
    SELECT t.name, COUNT(at.artwork_id) AS artwork_count
    FROM tags t
    LEFT JOIN artwork_tags at ON t.id = at.tag_id
    JOIN artworks a ON at.artwork_id = a.id AND a.status = 'approved'
    GROUP BY t.id
    HAVING artwork_count > 0
    ORDER BY artwork_count DESC, t.name ASC
");
$stmt->execute();
$all_tags =  $stmt->fetchAll(PDO::FETCH_ASSOC);



?>

<div class="flex">

    <?php foreach ($all_tags as $tag): ?>
        <a href="index.php?tag=<?= urlencode($tag['name']) ?>" class="tag-link">
            <?= htmlspecialchars($tag['name']) ?>
            <span class="tag-count">(<?= (int) $tag['artwork_count'] ?>)</span>
        </a>
    <?php endforeach; ?>
    
</div>

<nav>
    <h2><?= $site_name ?></h2>
    <ul>
        <li><a href="index.php">Home</a></li>
        <?php if ($user) : ?>
            <li><a href="upload.php">Upload your Art</a></li>
            <li><a href="profile.php?u=<?= urlencode($user['username'] ?? '') ?>">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>


<?php if ($flash_messages): ?>
    <br>
    <div class="flash-messages">
        <?php foreach ($flash_messages as $message): ?>
            <div class="flash-message"><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>
    </div>
    <br>
<?php endif; ?>
