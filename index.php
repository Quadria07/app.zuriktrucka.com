<?php
session_start();
include 'db.php';
include 'send_verification.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(50));

            $insert = $conn->prepare("INSERT INTO users (email, phone, password, verification_token) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $email, $phone, $hashed_password, $token);
            $insert->execute();

            // Send verification email
            if (sendVerificationEmail($email, $token)) {
                $success = "A verification email has been sent to you, check your inbox or spam.";
            } else {
                $error = "Signup successful, but failed to send verification email.";
                $show_retry = true; // ✅ trigger retry button
                $_SESSION['pending_email'] = $email;
                $_SESSION['pending_token'] = $token;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - ZURIK TRUCK ACADEMY</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6 bg-white p-4 rounded shadow-sm">
      <div class="text-center mb-4">
        <img src="assets/logo.png" alt="ZURIK Truck Academy Logo" width="100">
        <h4 class="mt-3">Create an Account</h4>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" required class="form-control" onblur="checkEmail(this.value)">
          <small id="email-status" class="text-danger"></small>
        </div>
        <div class="mb-3">
          <label>Phone Number</label>
          <input type="text" name="phone" required class="form-control">
        </div>
        <div class="mb-3">
          <label>Password</label>
          <input type="password" name="password" required class="form-control">
        </div>
        <div class="mb-3">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" required class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
    
      </form>
<?php if (!empty($show_retry)): ?>
  <button id="retryBtn" class="btn btn-warning mt-2">Retry Sending Verification Email</button>
  <div id="retryStatus" class="mt-2"></div>
<?php endif; ?>

      <div class="text-center mt-3">
        Already have an account? <a href="login.php">Login here</a>
      </div>
    </div>
  </div>

  <footer class="text-center mt-5 text-muted">
    &copy; <?= date("Y") ?> ZURIK TRUCK ACADEMY. All rights reserved.
  </footer>
</div>

<script>
function checkEmail(email) {
  const status = document.getElementById("email-status");
  fetch('check_email.php?email=' + encodeURIComponen_
  
  
  
document.addEventListener("DOMContentLoaded", function() {
  const retryBtn = document.getElementById("retryBtn");
  const retryStatus = document.getElementById("retryStatus");

  if (retryBtn) {
    retryBtn.addEventListener("click", function() {
      retryStatus.innerHTML = "<div class='text-info'>Retrying... Please wait</div>";

      fetch("resend_verification.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === "success") {
          retryStatus.innerHTML = "<div class='alert alert-success'>" + data.message + "</div>";
          retryBtn.style.display = "none"; // hide button after success
        } else {
          retryStatus.innerHTML = "<div class='alert alert-danger'>" + data.message + "</div>";
        }
      })
      .catch(err => {
        retryStatus.innerHTML = "<div class='alert alert-danger'>Network error. Try again later.</div>";
      });
    });
  }
});
</script>

