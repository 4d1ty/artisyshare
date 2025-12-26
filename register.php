<?php
require_once __DIR__ . "/init.php";

$title = "Register";

include __DIR__ . "/templates/header.php";
include_once __DIR__ . "/templates/navbar.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    require_once __DIR__ . "/db.php";
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username == "") {
        $errors[] = "Username is required";
    }
    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if ($password === "" || $password2 == "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id from users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = "Username or Email already exists";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password_hash)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$username, $email, $hash]);
        $_SESSION["user_id"] = $pdo->lastInsertId();
        rotate_csrf_token();
        header("Location: index.php");
        exit;
    }
}
?>

<h3>Register</h3>
<?php if ($errors): ?>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form action="register.php" method="post">
    <label for="email">Email</label>
    <input type="email"
        name="email"
        id="email"
        placeholder="Email"
        required
        value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, 'UTF-8') ?>">
    <label for="username">Username</label>
    <input type="text"
        name="username"
        id="username"
        placeholder="Username"
        required
        value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, 'UTF-8') ?>">
    <label for="password">Password</label>
    <input type="password"
        name="password"
        id="password"
        placeholder="Password"
        required>
    <label for="password2">Confirm Password</label>
    <input type="password"
        placeholder="Confirm Password"
        name="password2"
        id="password2"
        required>
    <?= csrf_tag() ?>
    <button type="submit">Register</button>
</form>
<?php include __DIR__ . "/templates/footer.php"; ?>