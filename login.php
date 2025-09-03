<?php
require 'db.php';
session_start();

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email)) {
        $error = "Email is required";
    } elseif (empty($password)) {
        $error = "Password is required";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                $error = "Execution error: " . $stmt->error;
            } else {
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid email or password";
                    }
                } else {
                    $error = "Invalid email or password";
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
    <title>Truck Academy - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            max-width: 800px; 
            margin: auto; 
            background: #f9f9f9; 
        }
        h2 { 
            color: #222; 
            text-align: center;
            margin-bottom: 30px;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        label { 
            display: block; 
            margin-top: 15px; 
            font-weight: bold;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            margin-top: 5px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
        }
        button { 
            background-color: #007BFF; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background-color: #0069d9;
        }
        .footer { 
            text-align: center; 
            margin-top: 40px; 
            font-size: 0.9em; 
            color: #888; 
        }
        .logo { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .message { 
            padding: 10px; 
            background: #dff0d8; 
            color: #3c763d; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            text-align: center;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        .auth-links a {
            color: #007BFF;
            text-decoration: none;
            margin: 0 10px;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="logo">
    <img src="assets/logo.png" alt="Zurik Truck Academy" width="200">
</div>

<div class="login-container">
    <h2>Login to Your Account</h2>
    
    <?php if ($error): ?>
        <div class="message error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="login.php">
        <input type="hidden" name="login" value="1">
        
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Login</button>
        
        <div class="auth-links">
            <a href="register.php">Create an account</a> | 
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </form>
</div>

<div class="footer">&copy; <?= date('Y') ?> Zurik Truck Academy</div>

</body>
</html>