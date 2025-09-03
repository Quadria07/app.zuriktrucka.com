<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start();

$error = '';
$success = '';
$show_form = false;

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Validate token with prepared statement
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    if (!$stmt) {
        $error = "Database error. Please try again.";
    } else {
        $stmt->bind_param("s", $token);
        if (!$stmt->execute()) {
            $error = "Database error. Please try again.";
        } else {
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $reset = $result->fetch_assoc();
                
                if (strtotime($reset['expires_at']) > time()) {
                    $_SESSION['reset_user_id'] = $reset['user_id'];
                    $_SESSION['reset_token'] = $token;
                    $show_form = true;
                } else {
                    $error = "Reset link has expired";
                    // Clean up expired token
                    $conn->query("DELETE FROM password_resets WHERE token = '$token'");
                }
            } else {
                $error = "Invalid reset token";
            }
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $token = $_SESSION['reset_token'] ?? '';
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Both password fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Verify token again with user_id check
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND user_id = ?");
        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("si", $token, $user_id);
            if (!$stmt->execute()) {
                $error = "Database error. Please try again.";
            } else {
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    // Update password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $hashed_password, $user_id);
                        if ($update_stmt->execute()) {
                            // Delete the used token
                            $conn->query("DELETE FROM password_resets WHERE token = '$token'");
                            
                            // Clear session
                            unset($_SESSION['reset_token']);
                            unset($_SESSION['reset_user_id']);
                            
                            $success = "Password has been reset successfully!";
                            $show_form = false;
                        } else {
                            $error = "Failed to update password. Please try again.";
                        }
                        $update_stmt->close();
                    } else {
                        $error = "Database error. Please try again.";
                    }
                } else {
                    $error = "Invalid or expired reset token";
                    $show_form = false;
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Truck Academy - Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: auto; background: #f9f9f9; }
        h2 { color: #222; }
        label { display: block; margin-top: 15px; }
        input, select, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; }
        .form-section { margin-bottom: 30px; }
        .footer { text-align: center; margin-top: 40px; font-size: 0.9em; color: #888; }
        .logo { text-align: center; margin-bottom: 30px; }
        button { background-color: #007BFF; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .message { padding: 10px; background: #dff0d8; color: #3c763d; margin-bottom: 20px; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="logo">
    <img src="assets/logo.png" alt="Zurik Truck Academy" width="200">
</div>

<h2>Reset Password</h2>

<?php if ($error): ?>
    <div class="message error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="message">✅ <?= htmlspecialchars($success) ?></div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="login.php">Login with your new password</a>
    </div>
<?php elseif ($show_form): ?>
    <form method="POST">
        <div class="form-section">
            <label>New Password</label>
            <input type="password" name="password" required minlength="8">
            
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required minlength="8">
        </div>

        <button type="submit" name="reset">Reset Password</button>
    </form>
<?php else: ?>
    <div class="message error">❌ Invalid or expired password reset link</div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="forgot_password.php">Request a new reset link</a>
    </div>
<?php endif; ?>

<div class="footer">&copy; <?= date('Y') ?> Zurik Truck Academy</div>

</body>
</html>