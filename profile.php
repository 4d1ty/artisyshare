<?php

require_once __DIR__ . "/init.php";

$title = "Edit Profile";

include __DIR__ . "/templates/header.php";
include_once __DIR__ . "/templates/navbar.php";


if (!$user) {
    $_SESSION['flash_messages'][] = "You must be logged in to access this page.";
    header("Location: login.php?next=profile.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');


    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }


    if (strlen($bio) > 500) {
        $errors[] = "Bio cannot exceed 500 characters.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$email, $bio, $user['id']]);
        $_SESSION['flash_messages'][] = "Profile updated successfully.";
        header("Location: profile.php");
        exit;
    }
} else {
    // Load current profile data
    $stmt = $pdo->prepare("SELECT email, bio FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
    $email = $profile['email'];
    $bio = $profile['bio'];
}
?>

<h2>Edit Profile</h2>
<?php if (!empty($errors)): ?>
    <div class="errors">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="profile.php">
    <?= csrf_tag() ?>
    <div>
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username"
            readonly disabled
            value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>" required>
    </div>
    <br>
    <div>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"
            value="<?= htmlspecialchars($email ?? '', ENT_QUOTES) ?>" required>
    </div>
    <br>
    <div>
        <label for="bio">Bio:</label><br>
        <textarea id="bio" name="bio" rows="5" cols="40"
            maxlength="500"><?= htmlspecialchars($bio ?? '', ENT_QUOTES) ?></textarea>
    </div>
    <br>
    <div>
        <button type="submit">Update Profile</button>
    </div>