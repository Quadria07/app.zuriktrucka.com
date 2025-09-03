<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

session_start();

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);

    try {
        // Server settings (using your working configuration)
        $mail->isSMTP();
        $mail->Host       = 'smtp.mail.yahoo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'petmykola@yahoo.com';
        $mail->Password   = 'Princegeorge@79';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('info@zuriktrucka.com', 'ZURIK TRUCK ACADEMY');
        $mail->addAddress($email);

        // Content
        $reset_link = "https://app.zuriktrucka.com/reset_password.php?token=$token";
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "Click the link below to reset your password:<br><br>
                          <a href='$reset_link'>$reset_link</a><br><br>
                          This link will expire in 1 hour.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = "Email is required";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                $error = "Database error. Please try again.";
            } else {
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $user_id = $user['id'];
                    
                    // Generate unique token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in database
                    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) 
                                          ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
                    if (!$stmt) {
                        $error = "Database error. Please try again.";
                    } else {
                        $stmt->bind_param("iss", $user_id, $token, $expires);
                        if ($stmt->execute()) {
                            if (sendPasswordResetEmail($email, $token)) {
                                $success = "Password reset link has been sent to your email";
                            } else {
                                $error = "Failed to send reset email. Please try again later.";
                            }
                        } else {
                            $error = "Database error. Please try again.";
                        }
                    }
                } else {
                    // For security, don't reveal if email doesn't exist
                    $success = "If this email exists in our system, you'll receive a reset link";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Truck Academy - Forgot Password</title>
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

<h2>Forgot Password</h2>

<?php if ($error): ?>
    <div class="message error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="message">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-section">
        <label>Email Address</label>
        <input type="email" name="email" required>
    </div>

    <button type="submit">Send Reset Link</button>
</form>

<div style="text-align: center; margin-top: 20px;">
    <a href="login.php">Back to Login</a>
</div>

<div class="footer">&copy; <?= date('Y') ?> Zurik Truck Academy</div>

</body>
</html>