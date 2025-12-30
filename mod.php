<?php

require_once __DIR__ . "/init.php";

$title = "Moderator Panel";

include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";


if (!$user || $user['role'] !== 'moderator' && $user['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied.");
}

// Get all the artworks from the database

$stmt = $pdo->query("
SELECT 
        a.id,
        a.title,
        a.status,
        a.created_at,
        a.thumbnail_path,
        u.username,
        GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') AS tags
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN artwork_tags at ON at.artwork_id = a.id
    LEFT JOIN tags t ON t.id = at.tag_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
    ");
$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all tags from the database
$stmt = $pdo->query("SELECT * FROM tags ORDER BY name ASC");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Artwork and comments reporting

$stmt = $pdo->query("
    SELECT r.*, u.username AS reported_by_username, a.title AS artwork_title
    FROM artwork_reports r
    JOIN users u ON r.reporter_id = u.id
    JOIN artworks a ON r.artwork_id = a.id
    ORDER BY r.created_at DESC
");
$artwork_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT r.*, u.username AS reported_by_username, c.content AS comment_content
    FROM comment_reports r
    JOIN users u ON r.reporter_id = u.id
    JOIN comments c ON r.comment_id = c.id
    ORDER BY r.created_at DESC
");

$comment_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>

<h1>Moderator Panel</h1>
<p>Welcome to the moderator panel, <?= htmlspecialchars($user['username']) ?>.</p>
<!-- Moderator functionalities go here -->
<h2>All Artworks</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Thumbnail</th>
        <th>Title</th>
        <th>Submitted By</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Tags</th>

        <th>Action</th>
    </tr>
    <?php foreach ($artworks as $art): ?>
        <tr>
            <td><?= htmlspecialchars($art['id']) ?></td>
            <td>
                <?php if ($art['thumbnail_path']): ?>
                    <!-- Crop into square -->
                    <img src="<?= htmlspecialchars($art['thumbnail_path']) ?>" alt="Thumbnail" style="width:50px; height:50px; object-fit:cover; vertical-align:middle; margin-right:10px;">
                <?php endif; ?>
            </td>
            <td><a href="artwork.php?id=<?= urlencode($art['id']) ?>"><?= htmlspecialchars($art['title']) ?></a></td>
            <td><?= htmlspecialchars($art['username']) ?></td>
            <td><?= htmlspecialchars(ucfirst($art['status'])) ?></td>
            <td><?= htmlspecialchars($art['created_at']) ?></td>
            <td>
                <?php if (!empty($art['tags'])): ?>
                    <?php foreach (explode(', ', $art['tags']) as $tag): ?>
                        <span style="padding:2px 6px; border:1px solid #ccc; margin-right:4px;">
                            <?= htmlspecialchars($tag) ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <em>No tags</em>
                <?php endif; ?>
            </td>

            <td>
                <form action="art_status.php" method="post" id="status-form-<?= htmlspecialchars($art['id']) ?>">
                    <?= csrf_tag() ?>
                    <input type="hidden" name="artwork_id" value="<?= htmlspecialchars($art['id']) ?>">
                    <!-- Show all the tags -->

                    <select name="new_status" required>
                        <option value="approved" <?= $art['status'] === 'approved' ? 'selected' : '' ?>>Approve</option>
                        <option value="rejected" <?= $art['status'] === 'rejected' ? 'selected' : '' ?>>Reject</option>
                        <option value="pending" <?= $art['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                    <!-- Rejection reason, show only if rejected is selected -->
                    <input type="text" name="rejection_reason" id="rejection_reason" placeholder="Rejection Reason" style="display:none;">
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Show all the tags, with action on them -->
<h2>All Tags</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Action</th>
    </tr>
    <?php foreach ($tags as $tag): ?>
        <tr>
            <td><?= htmlspecialchars($tag['id']) ?></td>
            <td><a href="index.php?tag=<?= urlencode(htmlspecialchars($tag['name'])) ?>"><?= htmlspecialchars($tag['name']) ?></a></td>
            <td>
                <form action="delete_tag.php" method="post" onsubmit="return confirm('Are you sure you want to delete this tag?');">
                    <?= csrf_tag() ?>
                    <input type="hidden" name="tag_id" value="<?= htmlspecialchars($tag['id']) ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>


<h2>Reported Artworks</h2>
<?php if (empty($artwork_reports)): ?>
    <p>No reported artworks.</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Report ID</th>
            <th>Artwork ID</th>
            <th>Artwork Title</th>
            <th>Reported By</th>
            <th>Reason</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php foreach ($artwork_reports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report['id']) ?></td>
                <td><a href="artwork.php?id=<?= urlencode($report['artwork_id']) ?>"><?= htmlspecialchars($report['artwork_id']) ?></a></td>
                <td><?= htmlspecialchars($report['artwork_title']) ?></td>
                <td><?= htmlspecialchars($report['reported_by_username']) ?></td>
                <td><?= nl2br(htmlspecialchars($report['reason'])) ?></td>
                <td><?= htmlspecialchars($report['created_at']) ?></td>
                <td>
                    <form method="post" action="resolve_report.php" style="display:inline;">
                        <?= csrf_tag() ?>
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['id']) ?>">
                        <button type="submit" onclick="return confirm('Mark this report as resolved?');">Resolve</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

<?php endif; ?>
<h2>Reported Comments</h2>
<?php if (empty($comment_reports)): ?>
    <p>No reported comments.</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Report ID</th>
            <th>Comment ID</th>
            <th>Comment Content</th>
            <th>Reported By</th>
            <th>Reason</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php foreach ($comment_reports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report['id']) ?></td>
                <td><?= htmlspecialchars($report['comment_id']) ?></td>
                <td><?= nl2br(htmlspecialchars($report['comment_content'])) ?></td>
                <td><?= htmlspecialchars($report['reported_by_username']) ?></td>
                <td><?= nl2br(htmlspecialchars($report['reason'])) ?></td>
                <td><?= htmlspecialchars($report['created_at']) ?></td>
                <td>
                    <form method="post" action="resolve_comment_report.php" style="display:inline;">
                        <?= csrf_tag() ?>
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['id']) ?>">
                        <button type="submit" onclick="return confirm('Mark this comment report as resolved?');">Resolve</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<script>
    document.querySelectorAll('select[name="new_status"]').forEach(function(select) {
        select.addEventListener('change', function() {
            var rejectionReasonInput = this.parentElement.querySelector('input[name="rejection_reason"]');
            if (this.value === 'rejected') {
                rejectionReasonInput.style.display = 'inline-block';
                rejectionReasonInput.required = true;
            } else {
                rejectionReasonInput.style.display = 'none';
                rejectionReasonInput.required = false;
            }
        });
    });
</script>

<?php include __DIR__ . "/templates/footer.php"; ?>