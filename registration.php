<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Unauthorized. Please verify your email first.");
}

$submitted = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging: Count the fields
    $fields = [
        'user_id', 'surname','first_name','middle_name', 'dob', 'gender', 'nationality', 'address_street', 
        'address_city', 'address_state', 'address_postal', 'address_country', 'email', 
        'phone', 'has_license', 'license_number', 'license_country', 'license_expiry',
        'has_prior_training', 'training_name', 'training_year', 'training_certification',
        'involved_in_accident', 'accident_details', 'is_physically_fit', 'medical_conditions',
        'allergies', 'primary_language', 'understands_languages', 'willing_language_course',
        'preferred_start_date', 'training_location', 'payment_method', 'requires_payment_plan',
        'payment_plan_details', 'referral_source', 'additional_info', 'signed_name'
    ];
    
    if (count($fields) !== 38) {
        die("Field count mismatch. Expected 38, got " . count($fields));
    }

    $sql = "INSERT INTO registrations (" . implode(', ', $fields) . ") VALUES (" . 
           rtrim(str_repeat('?, ', count($fields)), ', ') . ")";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Prepare all values
    $values = [
        $user_id,
        $_POST['surname'] ?? '',
        $_POST['first_name'] ?? '',
        $_POST['middle_name'] ?? '',
        $_POST['dob'] ?? '',
        $_POST['gender'] ?? '',
        $_POST['nationality'] ?? '',
        $_POST['address_street'] ?? '',
        $_POST['address_city'] ?? '',
        $_POST['address_state'] ?? null,
        $_POST['address_postal'] ?? null,
        $_POST['address_country'] ?? '',
        $_POST['email'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['has_license'] ?? 'no',
        !empty($_POST['license_number']) ? $_POST['license_number'] : null,
        !empty($_POST['license_country']) ? $_POST['license_country'] : null,
        !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
        $_POST['has_prior_training'] ?? 'no',
        !empty($_POST['training_name']) ? $_POST['training_name'] : null,
        !empty($_POST['training_year']) ? $_POST['training_year'] : null,
        !empty($_POST['training_certification']) ? $_POST['training_certification'] : null,
        $_POST['involved_in_accident'] ?? 'no',
        !empty($_POST['accident_details']) ? $_POST['accident_details'] : null,
        $_POST['is_physically_fit'] ?? 'no',
        !empty($_POST['medical_conditions']) ? $_POST['medical_conditions'] : null,
        !empty($_POST['allergies']) ? $_POST['allergies'] : null,
        $_POST['primary_language'] ?? '',
        !empty($_POST['understands_languages']) ? $_POST['understands_languages'] : null,
        $_POST['willing_language_course'] ?? 'no',
        $_POST['preferred_start_date'] ?? '',
        $_POST['training_location'] ?? '',
        $_POST['payment_method'] ?? '',
        $_POST['requires_payment_plan'] ?? 'no',
        !empty($_POST['payment_plan_details']) ? $_POST['payment_plan_details'] : null,
        !empty($_POST['referral_source']) ? $_POST['referral_source'] : null,
        !empty($_POST['additional_info']) ? $_POST['additional_info'] : null,
        $_POST['signed_name'] ?? ''
    ];

    if (count($values) !== 38) {
        die("Value count mismatch. Expected 38, got " . count($values));
    }

    $types = str_repeat('s', 38);
    $types[0] = 'i'; // First parameter is user_id (integer)
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $submitted = true;

        // ✅ Send "Registration Received" email through Render (with retry)
        $regEmail   = $_POST['email'] ?? '';
        $fullName   = $_POST['full_name'] ?? '';

        $RENDER_URL = "https://zurik-email-sender.onrender.com/send_registration_mail.php";
        $RENDER_API_KEY = "07d80fee1945701219a99f04fb0313d7bd6629812a65d3b0"; // set this on your hosting

        $payload = json_encode([
            "email"     => $regEmail,
            "full_name" => $fullName,
            "subject"   => "Registration Received - Zurik Truck Academy"
        ]);

        $maxAttempts = 3;
        $ok = false;
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $ch = curl_init($RENDER_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-KEY: ' . $RENDER_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($code === 200 && trim($resp) === "OK") { 
                $ok = true; 
                break; 
            }
            sleep(2);
        }

        if (!$ok) {
            error_log("Registration mail failed for $regEmail. HTTP:$code Resp:$resp CurlErr:$cerr");
            $show_retry = true;
            $_SESSION['pending_email'] = $regEmail;
            $_SESSION['pending_fullname'] = $fullName;
            $_SESSION['pending_mode'] = 'registration';
        }

    } else {
        $error = "Execute failed: " . $stmt->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truck Academy Registration</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: auto;
            background: #f9f9f9;
            color: #333;
        }
        h2 {
            color: #007BFF;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .conditional {
            display: none;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9em;
            color: #888;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 10px;
            background: #dff0d8;
            color: #3c763d;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
    <script>
        function toggleConditional(id, trigger) {
            const el = document.getElementById(id);
            el.style.display = (trigger.value === 'yes') ? 'block' : 'none';
        }
    </script>
</head>
<body>

<div class="logo">
    <img src="assets/logo.png" alt="Zurik Truck Academy" width="200">
</div>

<h2>Register Here</h2>

<?php if ($submitted): ?>
    <div class="message">✅ Your application has been submitted successfully! Kindly check your mail for further instructions </div>
<?php elseif ($error): ?>
    <div class="message error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!$submitted): ?>
<form method="POST">
    <div class="form-section">
        <h3>Personal Information</h3>
        <label>Surname</label>
        <input type="text" name="surname" required>
        <label>First Name</label>
        <input type="text" name="first_name" required>
        <label>Middle Name</label>
        <input type="text" name="middle_name" required>
        <label>Date of Birth</label>
        <input type="date" name="dob" required>
        <label>Gender</label>
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
        <label>Nationality</label>
        <input type="text" name="nationality" required>
        <label>Street Address</label>
        <input type="text" name="address_street" required>
        <label>City</label>
        <input type="text" name="address_city" required>
        <label>State/Province</label>
        <input type="text" name="address_state">
        <label>Postal Code</label>
        <input type="text" name="address_postal">
        <label>Country</label>
        <input type="text" name="address_country" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Phone</label>
        <input type="text" name="phone" required>
    </div>

    <div class="form-section">
        <h3>Driving Experience</h3>
        <label>Do you have a valid license?</label>
        <select name="has_license" onchange="toggleConditional('license-details', this)" required>
            <option value="no">No</option>
            <option value="yes">Yes</option>
        </select>
        <div id="license-details" class="conditional">
            <label>License Number</label>
            <input type="text" name="license_number">
            <label>Issuing Country</label>
            <input type="text" name="license_country">
            <label>Expiry Date</label>
            <input type="date" name="license_expiry">
        </div>

        <label>Any prior training?</label>
        <select name="has_prior_training" onchange="toggleConditional('training-details', this)" required>
            <option value="no">No</option>
            <option value="yes">Yes</option>
        </select>
        <div id="training-details" class="conditional">
            <label>Institution/Program</label>
            <input type="text" name="training_name">
            <label>Year</label>
            <input type="text" name="training_year">
            <label>Certifications</label>
            <input type="text" name="training_certification">
        </div>

        <label>Involved in accident/violation?</label>
        <select name="involved_in_accident" onchange="toggleConditional('accident-details', this)" required>
            <option value="no">No</option>
            <option value="yes">Yes</option>
        </select>
        <div id="accident-details" class="conditional">
            <label>Details</label>
            <textarea name="accident_details"></textarea>
        </div>
    </div>

    <div class="form-section">
        <h3>Health & Language</h3>
        <label>Are you physically fit?</label>
        <select name="is_physically_fit" required>
            <option value="yes">Yes</option>
            <option value="no">No</option>
        </select>
        <label>Medical Conditions</label>
        <textarea name="medical_conditions"></textarea>
        <label>Allergies</label>
        <textarea name="allergies"></textarea>
        <label>Primary Language</label>
        <input type="text" name="primary_language" required>
        <label>Other Languages</label>
        <input type="text" name="understands_languages">
        <label>Any language test certificate?</label>
        <select name="willing_language_course" required>
            <option value="yes">Yes</option>
            <option value="no">No</option>
        </select>
    </div>

    <div class="form-section">
        <h3>Program Details</h3>
        <label>Start Date</label>
        <input type="date" name="preferred_start_date" required>
        <p>Note that  admission into our program training becomes valid only when your proof of fund has been duly verified by Seba Bank Switzerland</p>
        <label>Location</label>
        <select name="training_location" required>
            <option value="">Select Location</option>
            <option value="Portugal">Portugal</option>
            <option value="Switzerland">Switzerland</option>
            <option value="Lithuania">Lithuania</option>
        </select>
        <label>Payment Method</label>
        <select name="payment_method" required>
            <option value="">Select Payment Method</option>
            <option value="Self-funded">Self-funded</option>
            <option value="Employer-funded">Employer-funded</option>
            <option value="Loan">Loan</option>
            <option value="Other">Other</option>
        </select>
        <label>Need Payment Plan?</label>
        <select name="requires_payment_plan" onchange="toggleConditional('payment-plan', this)" required>
            <option value="no">No</option>
            <option value="yes">Yes</option>
        </select>
        <div id="payment-plan" class="conditional">
            <label>Payment Plan Details</label>
            <textarea name="payment_plan_details"></textarea>
        </div>
    </div>

    <div class="form-section">
        <label>How did you hear about us?</label>
        <input type="text" name="referral_source">
        <label>Additional Info</label>
        <textarea name="additional_info"></textarea>
        <label>Signature (type your name)</label>
        <input type="text" name="signed_name" required>
    </div>

    <button type="submit">Submit Registration</button>
</form>
<?php endif; ?>

<div class="footer">&copy; <?= date('Y') ?> Zurik Truck Academy</div>

<script>
    document.querySelectorAll('select').forEach(s => { if (s.onchange) s.onchange(); });
</script>
</body>
</html>
