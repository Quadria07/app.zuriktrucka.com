<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user_id = intval($_POST['user_id']);

// Don't allow deleting yourself
if ($user_id === $_SESSION['user_id']) {
    exit(json_encode(['success' => false, 'message' => 'You cannot delete your own account']));
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete from password_resets first (foreign key constraint)
    $conn->query("DELETE FROM password_resets WHERE user_id = $user_id");
    
    // Delete from registrations if exists
    $conn->query("DELETE FROM registrations WHERE user_id = $user_id");
    
    // Delete from admin_actions
    $conn->query("DELETE FROM admin_actions WHERE target_user_id = $user_id OR admin_id = $user_id");
    
    // Finally delete the user
    $conn->query("DELETE FROM users WHERE id = $user_id");
    
    // Log the action
    logAdminAction($_SESSION['user_id'], 'delete_user', $user_id, "Deleted user #$user_id");
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>