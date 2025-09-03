<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all admin actions
$actions = [];
$stmt = $conn->prepare("SELECT a.*, u.email as admin_email 
                       FROM admin_actions a 
                       JOIN users u ON a.admin_id = u.id 
                       ORDER BY a.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $actions[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Log | Truck Academy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: auto; background: #f9f9f9; }
        .logo { text-align: center; margin-bottom: 30px; }
        .actions-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .actions-table th, .actions-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .actions-table th { background-color: #007BFF; color: white; }
        .actions-table tr:nth-child(even) { background-color: #f2f2f2; }
        .footer { text-align: center; margin-top: 40px; font-size: 0.9em; color: #888; }
    </style>
</head>
<body>

<div class="logo">
    <img src="assets/logo.png" alt="Zurik Truck Academy" width="200">
</div>

<h2>Admin Activity Log</h2>

<table class="actions-table">
    <thead>
        <tr>
            <th>Date/Time</th>
            <th>Admin</th>
            <th>Action</th>
            <th>Description</th>
            <th>IP Address</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($actions as $action): ?>
        <tr>
            <td><?= date('M j, Y H:i', strtotime($action['created_at'])) ?></td>
            <td><?= htmlspecialchars($action['admin_email']) ?></td>
            <td><?= htmlspecialchars($action['action_type']) ?></td>
            <td><?= htmlspecialchars($action['description']) ?></td>
            <td><?= htmlspecialchars($action['ip_address']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="footer">&copy; <?= date('Y') ?> Zurik Truck Academy</div>

</body>
</html>