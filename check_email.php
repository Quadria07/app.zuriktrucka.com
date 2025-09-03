<?php
include 'db.php';

if (isset($_GET['email'])) {
    $email = $_GET['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    echo ($stmt->num_rows > 0) ? 'exists' : 'ok';
}
?>
