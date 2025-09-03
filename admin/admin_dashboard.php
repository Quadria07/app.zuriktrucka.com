<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
require 'db.php';
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Log this page view
$log_sql = "INSERT INTO admin_actions (admin_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)";
$log_stmt = $conn->prepare($log_sql);

if ($log_stmt) {
    $action_type = 'page_view';
    $description = 'Accessed admin dashboard';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $log_stmt->bind_param("isss", $_SESSION['user_id'], $action_type, $description, $ip_address);
    $log_stmt->execute();
    $log_stmt->close();
} else {
    error_log("Failed to prepare log statement: " . $conn->error);
}

// Get all users
$users = [];
$user_sql = "SELECT id, email,  created_at, is_admin FROM users ORDER BY created_at DESC";
$user_stmt = $conn->prepare($user_sql);

if ($user_stmt) {
    $user_stmt->execute();
    $result = $user_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $user_stmt->close();
} else {
    error_log("Failed to prepare user query: " . $conn->error);
    $users = []; // Empty array if query fails
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Truck Academy</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .logo {
            max-width: 180px;
        }
        .welcome {
            text-align: right;
        }
        nav {
            background: #007BFF;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e9e9e9;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
        }
        .btn-edit {
            background-color: #28a745;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-admin {
            background-color: #6c757d;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="assets/logo.png" alt="Zurik Truck Academy" class="logo">
            <div class="welcome">
                Welcome, Admin!<br>
                <small><?= date('F j, Y, g:i a') ?></small>
            </div>
        </header>

        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">User Management</a>
            <a href="admin_activity.php">Activity Log</a>
            <a href="admin_logout.php">Logout</a>
        </nav>

        <h2>User Management</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['full_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                        <td><?= $user['is_admin'] ? 'Admin' : 'User' ?></td>
                        <td>
                            <button class="btn btn-edit" onclick="location.href='admin_edit_user.php?id=<?= $user['id'] ?>'">Edit</button>
                            <button class="btn btn-admin" onclick="toggleAdmin(<?= $user['id'] ?>, <?= $user['is_admin'] ?>)">
                                <?= $user['is_admin'] ? 'Demote' : 'Promote' ?>
                            </button>
                            <button class="btn btn-delete" onclick="confirmDelete(<?= $user['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            &copy; <?= date('Y') ?> Zurik Truck Academy. All rights reserved.
        </div>
    </div>

    <script>
    function toggleAdmin(userId, isAdmin) {
        if (confirm(`Are you sure you want to ${isAdmin ? 'remove admin privileges from' : 'make admin'} this user?`)) {
            fetch('admin_toggle_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}&action=${isAdmin ? 'remove' : 'add'}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('User status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    }

    function confirmDelete(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            fetch('admin_delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    }
    </script>
</body>
</html>