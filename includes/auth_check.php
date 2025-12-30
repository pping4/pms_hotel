<?php
// Authentication check - include this at the top of protected pages
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /pms_hotel/auth/login.php');
    exit;
}

// Get current user info
$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'full_name' => $_SESSION['full_name']
];
?>
