<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); // ✅ Start the session here

require 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Step 1: Look for the token in the DB
    $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_verified'] == 1) {
            echo "✅ Your email is already verified.";
            exit;
        }

        // Step 2: Update is_verified and clear token
        $user_id = $user['id'];
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("i", $user_id);

        if ($update->execute()) {
            // ✅ Store user ID in session so registration.php knows who's filling the form
            $_SESSION['user_id'] = $user_id;

            // ✅ Redirect to registration page
            header("Location: https://app.zuriktrucka.com/registration.php");
            exit();
        } else {
            echo "❌ Failed to update verification status.";
        }
    } else {
        echo "❌ Invalid or expired verification link.";
    }
} else {
    echo "❌ No token provided in the link.";
}
?>
