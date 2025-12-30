<?php
$artwork_id = $artwork['id'];

$depth_limit = 3; // Limit nesting depth for replies

// Fetch comments for the artwork, and their authors but only inner join till the depth limit
$stmt = $pdo->prepare("
    SELECT c.id, c.content, c.created_at, c.parent_comment_id, u.username, c.user_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.artwork_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$artwork_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$commentTree = [];

foreach ($comments as $comment) {
    $parentId = $comment['parent_comment_id'] ?? 0;
    $commentTree[$parentId][] = $comment;
}


function renderComments(array $tree, int $parentId = 0, int $depth = 0, int $maxDepth = 3)
{
    if (!isset($tree[$parentId]) || $depth >= $maxDepth) {
        return;
    }

    echo '<ul>';

    foreach ($tree[$parentId] as $comment) {
        echo '<li class="comment-item">';
        echo '<strong>' . htmlspecialchars($comment['username']) . '</strong> ';
        echo '<em>on ' . htmlspecialchars($comment['created_at']) . '</em>';
        echo '<p>' . nl2br(htmlspecialchars($comment['content'])) . '</p>';
        echo '</li>';
        // delete button for own comments could be added here
        if ($comment['user_id'] === $GLOBALS['user']['id'] ?? null) {
            echo '<form method="POST" action="delete_comment.php" style="display:inline;">';
            echo csrf_tag();
            echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
            // hidden input to redirect back to the artwork page
            echo '<input type="hidden" name="artwork_id" value="' . htmlspecialchars($GLOBALS['artwork_id']) . '">';
            echo '<button type="submit" onclick="return confirm(\'Are you sure you want to delete this comment?\')">Delete</button>';
            echo '</form>';
        }
        if ($depth + 1 < $maxDepth)
            echo '<button class="reply-button" data-comment-id="' . $comment['id'] . '">Reply</button>';

        // Report button link
        echo '<form method="GET" action="report_comment.php" style="display:inline;">';
        echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
        echo '<input type="hidden" name="artwork_id" value="' . htmlspecialchars($GLOBALS['artwork_id']) . '">';
        echo '<button type="submit">Report</button>';
        echo '</form>';


        renderComments($tree, $comment['id'], $depth + 1, $maxDepth);

        echo '</li>';
    }

    echo '</ul>';
}

?>

<div class="comments-section">
    <h3>Comments</h3>
    <?php if (count($comments) === 0): ?>
        <p>No comments yet. Be the first to comment!</p>
    <?php else: ?>
        <ul>
            <?php renderComments($commentTree, 0, 0, 3); ?>

        </ul>
    <?php endif; ?>

    <script>
        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('reply-button')) return;

            const parentId = e.target.dataset.commentId;
            const commentText = prompt('Enter your reply:');
            if (!commentText) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'post_comment.php';

            form.innerHTML = `
        <?= csrf_tag() ?>
        <input type="hidden" name="artwork_id" value="<?= htmlspecialchars($artwork_id) ?>">
        <input type="hidden" name="parent_comment_id" value="${parentId}">
        <textarea name="comment_text" style="display:none;"></textarea>
    `;

            form.querySelector('textarea').value = commentText;

            document.body.appendChild(form);
            form.submit();
        });
    </script>

    <?php if ($user): ?>
        <form action="post_comment.php" method="post">
            <?= csrf_tag() ?>
            <input type="hidden" name="artwork_id" value="<?= htmlspecialchars($artwork_id) ?>">
            <textarea name="comment_text" rows="4" cols="50" required></textarea><br>
            <button type="submit">Post Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> to post a comment.</p>
    <?php endif; ?>
</div>