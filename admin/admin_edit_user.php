<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_GET['id'] ?? 0);
$user = [];

if ($user_id) {
    $stmt = $conn->prepare("SELECT id, email, full_name, phone, created_at, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, is_admin = ? WHERE id = ?");
    $stmt->bind_param("ssii", $full_name, $phone, $is_admin, $user_id);
    
    if ($stmt->execute()) {
        // Log the action
        $changes = [];
        if ($user['full_name'] !== $full_name) $changes[] = "name from '{$user['full_name']}' to '$full_name'";
        if ($user['phone'] !== $phone) $changes[] = "phone from '{$user['phone']}' to '$phone'";
        if ($user['is_admin'] != $is_admin) $changes[] = "admin status to " . ($is_admin ? 'Admin' : 'User');
        
        if (!empty($changes)) {
            logAdminAction($_SESSION['user_id'], 'edit_user', $user_id, "Updated user #$user_id: " . implode(', ', $changes));
        }
        
        header("Location: admin_dashboard.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User | Truck Academy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: auto; background: #f9f9f9; }
        .logo { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"] { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; 
        }
        .admin-checkbox { margin: 15px 0; }
        .btn { 
            padding: 10px 20px; background: #007BFF; color: white; border: none; 
            border-radius: 4px; cursor: pointer; margin-right: 10px; 
        }
        .btn-cancel { background: #6c757d; }
    </style>
</head>
<body>

<div class="logo">
    <img src="assets/logo.png" alt="Zurik Truck Academy" width="200">
</div>

<h2>Edit User: <?= htmlspecialchars($user['full_name'] ?? 'New User') ?></h2>

<form method="POST">
    <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
    </div>
    
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
    </div>
    
    <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
    </div>
    
    <div class="admin-checkbox">
        <input type="checkbox" name="is_admin" id="is_admin" <?= ($user['is_admin'] ?? 0) ? 'checked' : '' ?>>
        <label for="is_admin">Administrator</label>
    </div>
    
    <button type="submit" class="btn">Save Changes</button>
    <a href="admin_dashboard.php" class="btn btn-cancel">Cancel</a>
</form>

</body>
</html>