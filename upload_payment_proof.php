<?php
require 'db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Unauthorized");

$upload_dir = "uploads/payments/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
    $filename = time() . "_" . basename($_FILES['payment_proof']['name']);
    $path = $upload_dir . $filename;
    move_uploaded_file($_FILES['payment_proof']['tmp_name'], $path);

    $stmt = $conn->prepare("INSERT INTO user_payments (user_id, payment_type, method, proof_path) VALUES (?,?,?,?)");
    $payment_type = "living_expenses"; // could be dynamic
    $method = "crypto"; // default
    $stmt->bind_param("isss", $user_id, $payment_type, $method, $path);
    $stmt->execute();

    // Set application status to processing
    $conn->query("UPDATE application_status SET status='processing' WHERE user_id=$user_id");
}
header("Location: dashboard.php");
exit;
