
   <?php
function sendVerificationEmail($email, $token) {
    // CONFIG
    $RENDER_URL = "https://zurik-email-sender.onrender.com/sendmail.php"; // change to your Render deployed endpoint
    $RENDER_API_KEY = "07d80fee1945701219a99f04fb0313d7bd6629812a65d3b0"; // must match Render environment variable

    $payload = json_encode([
        'email' => $email,
        'token' => $token
    ]);

    // ✅ Retry mechanism
    $maxAttempts = 2;
    $success = false;

    for ($i = 1; $i <= $maxAttempts; $i++) {
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
            $success = true;
            break; // ✅ stop loop if success
        } else {
            sleep(2); // ✅ small delay before retry
        }
    }

    if ($success) {
        return true;
    } else {
        error_log("Render mail error HTTP:$httpcode Resp:$response CurlErr:$curlErr");
        return false;
    }
}
