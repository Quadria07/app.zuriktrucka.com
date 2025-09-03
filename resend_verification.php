<?php
session_start();
include 'db.php';

// make sure we saved user email/token after signup
if (!isset($_SESSION['pending_email']) || !isset($_SESSION['pending_token'])) {
    echo json_encode(["status" => "error", "message" => "No verification request available."]);
    exit;
}

$email = $_SESSION['pending_email'];
$token = $_SESSION['pending_token'];

// Render API
$RENDER_URL = "https://zurik-email-sender.onrender.com/sendmail.php";
$RENDER_API_KEY = "07d80fee1945701219a99f04fb0313d7bd6629812a65d3b0";

$payload = json_encode([
    'email' => $email,
    'token' => $token
]);

$ch = curl_init($RENDER_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-KEY: ' . $RENDER_API_KEY
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($httpcode === 200 && trim($response) === "OK") {
    echo json_encode(["status" => "success", "message" => "Verification email re-sent successfully. Check inbox/spam."]);
} else {
    echo json_encode(["status" => "error", "message" => "Retry failed. Please try again later."]);
    error_log("Retry mail error HTTP:$httpcode Resp:$response CurlErr:$curlErr");
}
