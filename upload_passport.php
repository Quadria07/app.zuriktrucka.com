<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: dashboard.php");
    exit;
}

if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['passport_photo']['tmp_name'];
    $fileName = time() . "_" . basename($_FILES['passport_photo']['name']);
    $uploadDir = "uploads/passports/";
    $dest_path = $uploadDir . $fileName;

    // Make sure directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // Save path to DB
        $sql = "INSERT INTO user_documents (user_id, passport_photo) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE passport_photo = VALUES(passport_photo)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $dest_path);
        $stmt->execute();

        $_SESSION['success'] = "Passport photo uploaded successfully!";
    } else {
        $_SESSION['error'] = "Error moving uploaded file.";
    }
} else {
    $_SESSION['error'] = "No file uploaded or upload error.";
}

header("Location: dashboard.php");
exit;
