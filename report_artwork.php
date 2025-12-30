<?php


require_once __DIR__ . "/init.php";
if (!$user) {
    http_response_code(403);
    die("Access denied. You must be logged in to report artwork.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $artwork_id = (int)($_POST['artwork_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($artwork_id <= 0 || empty($reason)) {
        $_SESSION['flash_messages'][] = "Invalid artwork ID or empty reason.";
        header("Location: artwork.php?id=" . $artwork_id);
        exit;
    }

    // if user has already reported this artwork, do not allow duplicate reports
    $stmt = $pdo->prepare("SELECT id FROM artwork_reports WHERE artwork_id = ? AND reporter_id = ?");
    $stmt->execute([$artwork_id, $user['id']]);
    $existing_report = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing_report) {
        $_SESSION['flash_messages'][] = "You have already reported this artwork.";
        header("Location: artwork.php?id=" . $artwork_id);
        exit;
    }

    // Insert report into the database
    $stmt = $pdo->prepare("INSERT INTO artwork_reports (artwork_id, reporter_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$artwork_id, $user['id'], $reason]);

    // Get all distinct reports count for this artwork

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT reporter_id) FROM artwork_reports WHERE artwork_id = ?");
    $stmt->execute([$artwork_id]);
    $report_count = $stmt->fetchColumn();
    if ($report_count >= 3) {
        // Flag the artwork as reviewed for moderation
        $stmt = $pdo->prepare("UPDATE artworks SET is_hidden = 1 WHERE id = ?");
        $stmt->execute([$artwork_id]);
    }


    $_SESSION['flash_messages'][] = "Artwork reported successfully.";
    header("Location: artwork.php?id=" . $artwork_id);
}

$title = "Report Artwork";
include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";
?>

<h2>Report Artwork</h2>
<form method="POST" action="report_artwork.php">
    <?php echo csrf_tag(); ?>
    <input type="hidden" name="artwork_id" value="<?php echo htmlspecialchars($_GET['artwork_id'] ?? ''); ?>">
    <div>
        <label for="reason">Reason for reporting:</label><br>
        <textarea name="reason" id="reason" rows="4" cols="50" required></textarea>
    </div>
    <div>
        <button type="submit">Submit Report</button>
    </div>

</form>
<?php
include __DIR__ . "/templates/footer.php";
?>