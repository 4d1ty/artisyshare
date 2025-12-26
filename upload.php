<?php
require_once __DIR__ . "/init.php";
require_once __DIR__ . "/utils/image.php";


$title = "Upload";

include __DIR__ . "/templates/header.php";
include_once __DIR__ . "/templates/navbar.php";

if (!$user) {
    $_SESSION['flash_messages'][] = "You must be logged in to upload your artwork.";
    header("Location: login.php?next=upload.php");
    exit;
}



$errors = [];
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $art_title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags  =  array_filter(
        array_map(
            'strtolower',
            array_map(
                'trim',
                explode(',', $_POST['tags'] ?? '')
            )
        )
    );

    $nsfw = isset($_POST['nsfw']) ? 1 : 0;


    if ($art_title == '') {
        $errors[] = "Title is required.";
    }

    if (!empty($tags) && count(array_unique($tags)) != count($tags)) {
        $errors[] = "Duplicate tags are not allowed.";
    }

    if (!empty($tags) && preg_grep('/[^a-z0-9_\-]/', $tags)) {
        $errors[] = "Tags can only contain letters, numbers, underscores, and hyphens.";
    }

    // if (!empty($tags) && preg_grep('/^.{1,30}$/', $tags)) {
    //     $errors[] = "Each tag must be between 1 and 30 characters.";
    // }

    if ($nsfw) {
        $tags[] = "nsfw"; // Automatically add nsfw tag
    }

    if (count($tags) > 30) {
        $errors[] = "You can only add up to 30 tags.";
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Image upload failed.";
    } else {
        $tmpPath = $_FILES['image']['tmp_name'];
        if (!is_valid_image($tmpPath)) {
            $errors[] = "Uploaded file is not a valid image.";
        }
    }

    if (empty($errors)) {
        require_once __DIR__ . "/db.php";

        $uploadDir = "uploads/";
        $thumbDir = $uploadDir . "thumbs/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . "." . $ext;
        $destPath = $uploadDir . $filename;

        if (isset($_POST['noexif'])) {
            // TODO: Strip EXIF data
        }
        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors[] = "Failed to save uploaded image.";
        } else {
            // Create thumbnail
            $thumbPath = $thumbDir . "thumb_" . $filename;
            if (!generate_thumbnail($destPath, $thumbPath)) {
                $errors[] = "Failed to create thumbnail.";
            }
        }
    }

    if (empty($errors)) {



        $stmt = $pdo->prepare(
            "INSERT INTO artworks (user_id, title, description, image_path, thumbnail_path)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $user['id'],
            $art_title,
            $description,
            $destPath,
            $thumbPath
        ]);
        $artwork_id = $pdo->lastInsertId();

        if (!empty($tags)) {
            // Insert tags and link to artwork
            foreach ($tags as $tag) {
                $stmt = $pdo->prepare(
                    "INSERT IGNORE INTO tags (name) VALUES (?)"
                );
                $stmt->execute([$tag]);
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE name=?");
                $stmt->execute([$tag]);
                $tag_id = $stmt->fetchColumn();

                // Link artwork to tag
                $stmt = $pdo->prepare("INSERT INTO artwork_tags (artwork_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$artwork_id, $tag_id]);
            }
        }

        $_SESSION['flash_messages'][] = "Artwork uploaded successfully, awaiting approval.";
        header("Location: artwork.php?id=" . $artwork_id);
        exit;
    }
}

?>

<h3>Upload</h3>

<?php if ($errors): ?>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form action="upload.php" method="POST" enctype="multipart/form-data">
    <label for="title">Title</label>
    <input type="text" name="title"
        id="title"
        value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES) ?>"
        required>
    <br><br>
    <label for="description">Description</label><br>
    <textarea name="description" rows="4" cols="50" id="description"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES) ?></textarea>
    <br>
    <br>
    <label for="tags">Tags (comma separated)</label>
    <input type="text" name="tags" id="tags"
        value="<?= htmlspecialchars($_POST['tags'] ?? '', ENT_QUOTES) ?>">
    <br>
    <br>
    <label for="noexif">Strip EXIF Data</label>
    <input type="checkbox" name="noexif" id="noexif" <?= isset($_POST['noexif']) ? 'checked' : '' ?>>
    <br>
    <br>

    <label for="nsfw">Mark as NSFW</label>
    <input type="checkbox" name="nsfw" id="nsfw" <?= isset($_POST['nsfw']) ? 'checked' : '' ?>>
    <br>
    <br>
    <label for="image">Image</label>
    <input type="file" name="image" accept="image/*" required id="image">
    <br>
    <br>

    <?= csrf_tag() ?>
    <button type="submit">Upload</button>
</form>
<?php include __DIR__ . "/templates/footer.php"; ?>