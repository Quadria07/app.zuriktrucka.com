<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user_id = intval($_POST['user_id']);
$action = $_POST['action'] === 'add' ? 1 : 0;

// Don't allow modifying your own admin status
if ($user_id === $_SESSION['user_id']) {
    exit(json_encode(['success' => false, 'message' => 'You cannot modify your own admin status']));
}

$stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
$stmt->bind_param("ii", $action, $user_id);

if ($stmt->execute()) {
    // Log the action
    $description = $action ? "Promoted user #$user_id to admin" : "Demoted user #$user_id from admin";
    logAdminAction($_SESSION['user_id'], 'admin_toggle', $user_id, $description);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

function logAdminAction($admin_id, $action_type, $target_user_id, $description) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_user_id, description, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $admin_id, $action_type, $target_user_id, $description, $ip);
    $stmt->execute();
}
?>