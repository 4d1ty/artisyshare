<?php
require_once __DIR__ . "/init.php";

$title = "Login";

include __DIR__ . "/templates/header.php";
include_once __DIR__ . "/templates/navbar.php";

$errors = [];


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Forbidden");
    }

    $username  = trim($_POST['username']  ?? '');
    $password  = $_POST['password']  ?? '';
    if ($username == "") {
        $errors[] = "Username is empty";
    }
    if ($password == "") {
        $errors[] = "Password is empty";
    }
    if (empty($errors)) {
        require_once __DIR__ . "/db.php";



        $stmt = $pdo->prepare("SELECT id, username, is_banned, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash']) || $user['is_banned']) {
            $errors[] = "Invalid Username or Password or account is banned.";
        }

    }

    if (empty($errors)) {
        $_SESSION['user_id'] = $user['id'];
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        rotate_csrf_token();
        $next = $_POST['next'] ?? 'index.php';
        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+\.php$/', $next)) {
            $next = 'index.php';
        }
        header("Location: $next");
        exit;
    }
}

?>

<h3>Login</h3>
<?php if ($errors): ?>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<form action="login.php" method="post">
    <label for="username">Username</label>
    <input type="text" name="username" id="username"
        placeholder="Username"
        required
        autocomplete="username"
        value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, 'UTF-8') ?>">
    <br>
    <br>
    <label for="password">Password</label>
    <input type="password"
        autocomplete="current-password"
        placeholder="Password"
        name="password" id="password">
    <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?= csrf_tag() ?>
    <br>
    <br>

    <button type="submit">Login</button>
</form>

<?php include __DIR__ . "/templates/footer.php"; ?>