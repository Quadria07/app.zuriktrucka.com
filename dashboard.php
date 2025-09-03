<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Unauthorized access");

// Get application status
$status_sql = "SELECT status FROM application_status WHERE user_id = ?";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$app_status = $result->fetch_assoc()['status'] ?? 'none';

// Get user passport photo safely
$photo_sql = "SELECT passport_photo FROM user_documents WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($photo_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!empty($row['passport_photo']) && file_exists($row['passport_photo'])) {
    $passport_photo = $row['passport_photo'] . "?v=" . time();
} else {
    $passport_photo = "default-avatar.png";
}

// Handle messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

//kjjjjjj
// Get first name from registration table
// Default fallback
$full_name = "User";

// Query registration for full_name
$reg_sql = "SELECT full_name FROM registrations WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($reg_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reg = $result->fetch_assoc();

if ($reg && !empty($reg['full_name'])) {
    $full_name = $reg['full_name'];
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard - Zurik Academy</title>
  <style>
    :root{
      --bg: #f3f6fb;
      --card: #ffffff;
      --muted: #6b7280;
      --primary: #4f46e5;
      --accent: #7c3aed;
      --soft: #e8ecf8;
      --shadow: 0 10px 25px rgba(0,0,0,.06);
      --radius: 20px;
    }
    *{box-sizing: border-box}
    body{
      margin:0;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
      background: radial-gradient(circle at 20% -10%, rgba(124,58,237,.15), transparent 40%),
                  radial-gradient(circle at 100% 0%, rgba(0,150,255,.15), transparent 40%),
                  var(--bg);
      color:#0f172a;
    }
    .layout{display:flex;min-height:100vh;gap:20px;padding:24px}
    .sidebar{width:86px;background:#f0f4ff;border-radius:28px;padding:14px 10px;
      display:flex;flex-direction:column;align-items:center;gap:14px;}
    .sidebar .round{width:54px;height:54px;border-radius:50%;background:#fff;
      display:flex;align-items:center;justify-content:center;font-size:20px;color:#64748b;cursor:pointer;}
    .avatar{width:46px;height:46px;border-radius:50%;overflow:hidden;border:2px solid #fff;}
    .avatar img{width:100%;height:100%;object-fit:cover}
    .content{flex:1;min-width:0}
    .hero{background:#0b1021;border-radius:24px;padding:28px;color:white;display:flex;gap:20px;align-items:center;box-shadow:var(--shadow);}
    .hero .avatar-lg{width:72px;height:72px;border-radius:50%;border:4px solid rgba(255,255,255,.6);overflow:hidden;background:#1f2a4a;}
    .hero .avatar-lg img{width:100%;height:100%;object-fit:cover}
    .hero .name{font-size:22px;font-weight:700;margin-bottom:6px}
    .hero .subtitle{font-size:12px;opacity:.9}
    .hero .stats{margin-left:auto;display:flex;gap:20px;align-items:center}
    .stat{text-align:center}
    .stat .big{font-size:24px;font-weight:700}
    .stat .lab{font-size:11px;opacity:.8}
    .hero .badge{background:rgba(255,255,255,.15);padding:6px 10px;border-radius:999px;font-size:12px}
    .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-top:18px}
    .card{background:var(--card);border-radius:18px;padding:20px;box-shadow:var(--shadow)}
    .card h3{margin:0 0 12px 0;font-size:16px}
    .message{padding:12px;margin:12px 0;border-radius:12px}
    .success{background:#d4edda;color:#155724;}
    .error{background:#f8d7da;color:#721c24;}
    .timetable{display:flex;gap:12px;overflow:auto;padding-bottom:6px}
    .day{min-width:160px;background:#fff;border-radius:14px;padding:12px 14px;border:1px solid #eef2f7}
    .day h5{margin:0 0 8px;font-size:12px;color:#374151}
    .day .date{font-size:20px;font-weight:700}
    @media(max-width:1100px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="round" title="Home">🏠</div>
    <div class="round" title="Documents">📄</div>
    <div class="round" title="Bank">🏦</div>
    <div class="round" title="Payments">💳</div>
    <form action="logout.php" method="POST" style="margin-top:auto;">
      <button type="submit" class="round" title="Logout">🚪</button>
    </form>
    <div class="avatar"><img src="<?= htmlspecialchars($passport_photo) ?>" alt="Profile"></div>
  </aside>

  <!-- Main content -->
  <main class="content">
    <!-- Hero -->
    <section class="hero">
      <div class="avatar-lg"><img src="<?= htmlspecialchars($passport_photo) ?>" alt="Profile Picture"></div>
      <div style="margin-left:12px;">
        <div class="name">Hello, <?= htmlspecialchars($full_name) ?> <span class="badge"><?= ucfirst($app_status) ?></span></div>
        <div class="subtitle">Welcome to Zurik Truck Academy </div>
      </div>
      
    </section>

    <!-- Messages -->
    <?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Cards Grid -->
    <section class="grid">
      <article class="card">
        <h3>📄 Upload Required Documents</h3>
        <form action="upload_documents.php" method="POST" enctype="multipart/form-data">
          
          <label>Education</label><br><input type="file" name="education" required><br>
          <label>Driver’s License</label><br><input type="file" name="drivers_license"><br>
          <label>Language Certificate</label><br><input type="file" name="language_certificate"><br>
          <label>Motivation Letter</label><br><input type="file" name="motivation_letter"><br>
          <label>Birth Certificate</label><br><input type="file" name="birth_certificate"><br>
          <button type="submit">Submit All</button>
        </form>
      </article>

      <article class="card">
        <h3>🏦 Seba Bank Account Form</h3>
        <p>Download, fill, and email to <a href="mailto:mm@zuriktrucka.com">mm@zuriktrucka.com</a></p>
        <button onclick="window.location.href='forms/seba_account_form.pdf'">Download Form</button>
        <form action="upload_bankform.php" method="POST" enctype="multipart/form-data">
          <label>Upload Filled Form</label><br>
          <input type="file" name="bank_form"><br>
          <button type="submit">Upload Form</button>
        </form>
      </article>

      <article class="card">
        <h3>💳 Training Program Fee</h3>
        <button onclick="alert('Wire Transfer not available in your region. Contact admin@zuriktrucka.com')">Wire Transfer</button>
        <button onclick="alert('Contact admin@zuriktrucka.com for crypto guidance.')">Crypto Payment</button>
      </article>

      <article class="card">
        <h3>💰 Living Expenses</h3>
        <button onclick="alert('Wire Transfer not available in your region. Contact admin@zuriktrucka.com')">Wire Transfer</button>
        <button onclick="alert('Contact admin@zuriktrucka.com for crypto guidance.')">Crypto Payment</button>
        <form action="upload_payment_proof.php" method="POST" enctype="multipart/form-data">
          <label>Upload Proof</label><br>
          <input type="file" name="payment_proof"><br>
          <button type="submit">Upload</button>
        </form>
      </article>

      <article class="card">
        <h3>🖼 Passport Photo Upload</h3>
        <form action="upload_passport.php" method="POST" enctype="multipart/form-data">
          <input type="file" name="passport_photo" required><br>
          <button type="submit">Upload Photo</button>
        </form>
      </article>

      <article class="card">
        <h3>📞 Support</h3>
        <p>Need help? <a href="mailto:admin@zuriktrucka.com">Contact Support</a></p>
      </article>
    </section>

    <!-- Application Progress -->
    <section class="card" style="margin-top:14px;">
      <h3>Application Progress</h3>
      <div class="timetable">
        <div class="day"><h5>Step 1</h5><div class="date">Documents</div></div>
        <div class="day"><h5>Step 2</h5><div class="date">Fees</div></div>
        <div class="day"><h5>Step 3</h5><div class="date">Review</div></div>
        <div class="day"><h5>Step 4</h5><div class="date"><?= ucfirst($app_status) ?></div></div>
      </div>
    </section>
  </main>
</div>

</body>
</html>
