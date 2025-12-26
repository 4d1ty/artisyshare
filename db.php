<?php include __DIR__ . "/_secrets.php";

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
$user = $db_user;
$pass = $db_pass;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = new PDO($dsn, $user, $pass, $options);