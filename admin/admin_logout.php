<?php
session_start();

require 'db.php';

// Log the logout action if admin was logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $log_stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("isss", $_SESSION['user_id'], 'logout', 'Admin logged out', $_SERVER['REMOTE_ADDR']);
    $log_stmt->execute();
}

// Clear all session data
$_SESSION = array();
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>