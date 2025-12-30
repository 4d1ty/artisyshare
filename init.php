<?php

session_start();

require_once __DIR__ . "/utils/auth.php";
require_once __DIR__ . "/db.php";

$_SESSION['show_announcement'] = $_SESSION['show_announcement'] ?? true;


if (empty($_SESSION["csrf_token"])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function rotate_csrf_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validate_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_tag()
{
    if (!isset($_SESSION['csrf_token'])) {
        return '';
    }
    $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}


function csrf_token()
{
    return $_SESSION['csrf_token'] ?? '';
}