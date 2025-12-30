<?php

// Just query and display all announcements and only those that are still valid via show_until

$stmt = $pdo->prepare("
    SELECT a.*, u.username AS created_by
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    WHERE a.show_until IS NULL OR a.show_until > NOW()
    ORDER BY a.created_at DESC
");
$stmt->execute();

$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!-- This file is a template block to be used -->
<div class="announcement-panel">
    <h3>Announcements</h3>
    <?php if (count($announcements) === 0): ?>
        <p>No announcements available.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($announcements as $announcement): ?>
                <li>
                    <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                    <em>by <?= htmlspecialchars($announcement['created_by']) ?> on <?= htmlspecialchars($announcement['created_at']) ?></em>
                    <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>