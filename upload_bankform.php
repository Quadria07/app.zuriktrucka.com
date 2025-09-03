<?php
require 'db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Unauthorized");

$upload_dir = "uploads/bank_forms/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_FILES['bank_form']['error'] === UPLOAD_ERR_OK) {
    $filename = time() . "_" . basename($_FILES['bank_form']['name']);
    $path = $upload_dir . $filename;
    move_uploaded_file($_FILES['bank_form']['tmp_name'], $path);

    $stmt = $conn->prepare("INSERT INTO user_documents (user_id, doc_type, file_path) VALUES (?,?,?)");
    $doc_type = "bank_form";
    $stmt->bind_param("iss", $user_id, $doc_type, $path);
    $stmt->execute();
}
header("Location: dashboard.php");
exit;
