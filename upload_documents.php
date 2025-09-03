<?php
require 'db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Unauthorized");

$upload_dir = "uploads/documents/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

foreach ($_FILES as $doc_type => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = time() . "_" . basename($file['name']);
        $path = $upload_dir . $filename;
        move_uploaded_file($file['tmp_name'], $path);

        $stmt = $conn->prepare("INSERT INTO user_documents (user_id, doc_type, file_path) VALUES (?,?,?)");
        $stmt->bind_param("iss", $user_id, $doc_type, $path);
        $stmt->execute();
    }
}

// Update status to "under_review"
$conn->query("INSERT INTO application_status (user_id, status) VALUES ($user_id, 'under_review')
              ON DUPLICATE KEY UPDATE status='under_review'");

header("Location: dashboard.php");
exit;
