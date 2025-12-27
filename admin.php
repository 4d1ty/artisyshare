<?php

require_once __DIR__ . "/init.php";

$title = "Admin Panel";

include __DIR__ . "/templates/header.php";
include __DIR__ . "/templates/navbar.php";

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied.");
} 

// If the user is an admin, show the admin panel

// Get all the users from the database

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$order = $_GET['order'] ?? 'newest';


if(!empty($search) || !empty($role_filter) || !empty($order)) {
    $query = "SELECT id, username, role, created_at FROM users WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND username LIKE ?";
        $params[] = '%' . $search . '%';
    }

    if (!empty($role_filter)) {
        $query .= " AND role = ?";
        $params[] = $role_filter;
    }

    if ($order === 'oldest') {
        $query .= " ORDER BY created_at ASC";
    } else {
        $query .= " ORDER BY created_at DESC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}



?>


<h1>Admin Panel</h1>
<p>Welcome to the admin panel, <?= htmlspecialchars($user['username']) ?>.</p>
<!-- Admin functionalities go here -->
 <!-- Simple search -->
<form action="admin.php" method="get">
    <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <!-- Role -->
    
    <select name="role" id="role">
        <option value="">All Roles</option>
        <option value="user" <?= (isset($_GET['role']) && $_GET['role'] === 'user') ? 'selected' : '' ?>>User</option>
        <option value="moderator" <?= (isset($_GET['role']) && $_GET['role'] === 'moderator') ? 'selected' : '' ?>>Moderator</option>
        <option value="admin" <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
    </select>
    <select name="order" id="order">
        <option value="newest" <?= (isset($_GET['order']) && $_GET['order'] === 'newest') ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= (isset($_GET['order']) && $_GET['order'] === 'oldest') ? 'selected' : '' ?>>Oldest</option>
    </select>
    <button type="submit">Search</button>
</form>
<h2>Registered Users</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Role</th>
        <th>Registered At</th>
        <th>Action</th>
    </tr>
    <?php foreach ($users as $usr): ?>
    <tr>
        <td><?= htmlspecialchars($usr['id']) ?></td>
        <td><?= htmlspecialchars($usr['username']) ?></td>
        <td><?= htmlspecialchars($usr['role']) ?></td>
        <td><?= htmlspecialchars($usr['created_at']) ?></td>
        <td>
            <!-- Promote or demote to moderator, no admin -->
            <?php if ($usr['role'] === 'user'): ?>
                <form method="post" action="promote.php" style="display:inline;">
                    <?= csrf_tag() ?>
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($usr['id']) ?>">
                    <button type="submit">Promote to Moderator</button>
                </form>
            <?php elseif ($usr['role'] === 'moderator'): ?>
                <form method="post" action="demote.php" style="display:inline;">
                    <?= csrf_tag() ?>
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($usr['id']) ?>">
                    <button type="submit">Demote to User</button>
                </form>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>


<?php include __DIR__ . "/templates/footer.php"; ?>