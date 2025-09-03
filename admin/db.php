<?php
$host = "localhost";
$user = "zuriktru_user";        // change this to your DB user
$pass = "Instruction1122@";            // change this to your DB password
$dbname = "zuriktru_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
?>
