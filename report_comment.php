<?php

include_once __DIR__ . "/init.php";

if (!$user) {
    http_response_code(403);
    die("Access denied.");
    exit;
}

$comment_id = (int)($_GET['comment_id'] ?? 0);
if ($comment_id <= 0) {
    http_response_code(400);
    die("Invalid comment ID.");
}

// Fetch comment details
$stmt = $pdo->prepare("SELECT c.id, c.content, c.created_at, u.username, a.id AS artwork_id, a.title AS artwork_title FROM comments c JOIN users u ON c.user_id = u.id JOIN artworks a ON c.artwork_id = a.id WHERE c.id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$comment) {
    http_response_code(404);
    die("Comment not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $reason = trim($_POST['reason'] ?? '');
    if (empty($reason)) {
        $_SESSION['flash_messages'][] = "Please provide a reason for reporting the comment.";
        header("Location: report_comment.php?comment_id=" . $comment_id);
        exit;
    }

    // Insert report into database
    $stmt = $pdo->prepare("INSERT INTO comment_reports (comment_id, reporter_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $user['id'], $reason]);

    // get all the distinct reporter ids for this comment to flag it if more than 3 unique reporters
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT reporter_id) FROM comment_reports WHERE comment_id = ?");
    $stmt->execute([$comment_id]);

    $report_count = $stmt->fetchColumn();
    if ($report_count >= 3) {
        // Flag the comment as reviewed for moderation
        $stmt = $pdo->prepare("UPDATE comments SET is_hidden = 1 WHERE id = ?");
        $stmt->execute([$comment_id]);
    }

    $_SESSION['flash_messages'][] = "Comment reported successfully.";
    header("Location: artwork.php?id=" . $comment['artwork_id']);
    exit;
}

$title = "Report Comment - " . htmlspecialchars($comment['artwork_title']);
include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";
?>
<h2>Report Comment</h2>
<p>You are reporting the following comment on artwork "<strong><?= htmlspecialchars($comment['artwork_title']) ?></strong>":</p>
<blockquote>
    <p><strong><?= htmlspecialchars($comment['username']) ?></strong> on <?= htmlspecialchars($comment['created_at']) ?></p>
    <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
</blockquote>
<form method="POST">
    <?= csrf_tag() ?>
    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
    <label for="reason">Reason for reporting:</label><br>
    <textarea name="reason" id="reason" rows="4" cols="50" required></textarea><br><br>
    <button type="submit">Submit Report</button>
</form>
<?php
include __DIR__ . "/templates/footer.php";
?>