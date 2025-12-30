<?php
session_start();

$_SESSION['show_announcement'] = !$_SESSION['show_announcement'] ?? true;

header("Location: index.php");
exit;