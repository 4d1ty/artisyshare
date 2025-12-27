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

$stmt = $pdo->query("SELECT a.id, a.title, a.status, u.username, a.created_at, a.thumbnail_path FROM artworks a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <form action="art_status.php" method="post" id="status-form-<?= htmlspecialchars($art['id']) ?>">
                    <?= csrf_tag() ?>
                    <input type="hidden" name="artwork_id" value="<?= htmlspecialchars($art['id']) ?>">
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