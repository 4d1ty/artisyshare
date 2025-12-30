<?php
require_once __DIR__ . "/init.php";
require_once __DIR__ . "/utils/image.php";


$title = "Upload - ArtisyShare";

include __DIR__ . "/templates/header.php";
include_once __DIR__ . "/templates/navbar.php";

if (!$user) {
    $_SESSION['flash_messages'][] = "You must be logged in to upload your artwork.";
    header("Location: login.php?next=upload.php");
    exit;
}


// Last 24 hours upload count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM artworks WHERE user_id = ? AND created_at >= NOW() - INTERVAL 1 DAY");
$stmt->execute([$user['id']]);
$upload_count_24h = (int) $stmt->fetchColumn();

$per_day_upload_limit = $user['per_day_upload_limit'] ?? 10;
$per_artwork_size_limit = $user['per_artwork_size_limit'] ?? (10 * 1024 * 1024); // 10 MB

if ($upload_count_24h >= $per_day_upload_limit) {
    $_SESSION['flash_messages'][] = "You have reached your daily upload limit of {$per_day_upload_limit} artworks. Please try again later.";
    header("Location: index.php");
    exit;
}


$errors = [];
$max_file_size = $per_artwork_size_limit; // e.g., 10 MB
$max_resolution = 3000; // 3000 pixels
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

    if($_FILES['image']['size'] > $max_file_size) {
        $errors[] = "File size exceeds the maximum limit of 10 MB.";
    }

    if($_FILES['image']['size'] == 0) {
        $errors[] = "File size is zero bytes.";
    }
    

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Image upload failed.";
    } else {
        $tmpPath = $_FILES['image']['tmp_name'];
        if (!is_valid_image($tmpPath)) {
            $errors[] = "Uploaded file is not a valid image.";
        }
        $resolution = get_image_resolution($tmpPath);
        if ($resolution['width'] > $max_resolution || $resolution['height'] > $max_resolution) {
            $errors[] = "Image resolution exceeds the maximum limit of {$max_resolution}x{$max_resolution} pixels.";
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
    <label for="description">Description (Optional)</label><br>
    <textarea name="description" rows="4" cols="50" id="description"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES) ?></textarea>
    <br>
    <br>
    <label for="tags">Tags (comma separated), (Max 30 Tags, no space and special characters)</label>
    <br>
    <input type="text" name="tags" id="tags"
        value="<?= htmlspecialchars($_POST['tags'] ?? '', ENT_QUOTES) ?>">
    <br>
    <div class="tag-suggestions" id="tag-suggestions" style="display: none;"></div>
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
<p>
    Before posting, please make sure to read our <a href="rules.php">rules</a>
</p>

<script>
    const tagsInput = document.getElementById('tags');
    const tagSuggestions = document.getElementById('tag-suggestions');

    tagsInput.addEventListener('input', function() {
        const input = this.value;
        const parts = input.split(',');
        const currentTag = parts[parts.length - 1].trim();

        if (!currentTag) {
            tagSuggestions.style.display = 'none';
            tagSuggestions.innerHTML = '';
            return;
        }

        fetch(`suggest_tag.php?q=${encodeURIComponent(currentTag)}`)
            .then(res => res.json())
            .then(suggestions => {
                tagSuggestions.innerHTML = '';
                if (suggestions.length === 0) {
                    tagSuggestions.style.display = 'none';
                    return;
                }
                tagSuggestions.style.display = 'block';
                suggestions.forEach(tag => {
                    const div = document.createElement('div');
                    div.textContent = tag;
                    div.className = 'tag-suggestion-item';
                    div.style.cursor = 'pointer';
                    div.style.padding = '5px';
                    div.style.borderBottom = '1px solid #ccc';
                    tagSuggestions.style.display = 'block';

                    div.addEventListener('click', () => {
                        const existingTags = parts
                            .slice(0, -1)
                            .map(t => t.trim())
                            .filter(Boolean);

                        // Prevent duplicates
                        if (existingTags.map(t => t.toLowerCase()).includes(tag.toLowerCase())) {
                            tagSuggestions.innerHTML = '';
                            return;
                        }

                        existingTags.push(tag);

                        tagsInput.value = existingTags.join(', ') + ', ';
                        tagSuggestions.innerHTML = '';
                    });

                    tagSuggestions.appendChild(div);
                });
            });
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', e => {
        if (!tagSuggestions.contains(e.target) && e.target !== tagsInput) {
            tagSuggestions.innerHTML = '';
            tagSuggestions.style.display = 'none';
        }
    });
</script>

<?php include __DIR__ . "/templates/footer.php"; ?>