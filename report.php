<?php
// report.php - Emergency Reporting Form
session_start();
require_once 'db.php';

// Must be logged in (not admin)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if (!empty($_SESSION['is_admin'])) {
    header("Location: admin.php");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize
    $disaster_type = sanitize($conn, $_POST['disaster_type'] ?? '');
    $location      = sanitize($conn, $_POST['location']      ?? '');
    $description   = sanitize($conn, $_POST['description']   ?? '');
    $reported_at   = sanitize($conn, $_POST['reported_at']   ?? '');
    $old = compact('disaster_type','location','description','reported_at');

    // Validate
    $valid_types = ['fire','flood','accident','medical','other'];
    if (!in_array($disaster_type, $valid_types)) {
        $errors[] = 'Please select a valid disaster type.';
    }

    if (empty($location) || strlen($location) < 3) {
        $errors[] = 'Location is required (min. 3 characters).';
    }

    if (empty($description) || strlen($description) < 10) {
        $errors[] = 'Description is required (min. 10 characters).';
    }

    if (empty($reported_at)) {
        $errors[] = 'Date and time are required.';
    }

    // Insert report
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO emergency_reports
             (user_id, disaster_type, location, description, status, reported_at, created_at)
             VALUES (?, ?, ?, ?, 'Pending', ?, NOW())"
        );
        $stmt->bind_param("issss", $user_id, $disaster_type, $location, $description, $reported_at);

        if ($stmt->execute()) {
            setFlash('success', 'Emergency report submitted successfully! Authorities have been notified.');
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = 'Failed to submit report. Please try again.';
        }
        $stmt->close();
    }
}

// Default datetime (now) for the form
$default_dt = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Emergency — Disaster Emergency Help System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-wrapper">

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      🚨 DEHS
      <span class="badge blink">ALERT</span>
    </div>
    <ul class="navbar-nav">
      <li><a href="dashboard.php">📊 Dashboard</a></li>
      <li><a href="report.php" class="active">🆘 Report Emergency</a></li>
      <li class="user-info">👤 <?= $user_name ?></li>
      <li><a href="logout.php" class="btn-danger">⏻ Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<div class="main-content">
  <div class="container">

    <div class="page-header" style="max-width:680px;margin:0 auto 1.5rem">
      <div>
        <h2>🆘 Report Emergency</h2>
        <p>Fill in the details below. Authorities will be notified immediately.</p>
      </div>
    </div>

    <div class="report-card">

      <!-- Errors -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <div>
            <strong>⚠ Please fix:</strong>
            <ul style="margin:0.5rem 0 0 1.2rem;padding:0">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Emergency info banner -->
      <div class="alert alert-danger" style="margin-bottom:1.5rem">
        <strong>🔴 LIFE-THREATENING EMERGENCY?</strong>
        &nbsp;Call <strong>112</strong> immediately. This form is for non-critical or follow-up reporting.
      </div>

      <form method="POST" action="report.php" novalidate>

        <!-- Disaster Type -->
        <div class="form-group">
          <label for="disaster_type">Type of Emergency *</label>
          <select id="disaster_type" name="disaster_type" required>
            <option value="" disabled <?= empty($old['disaster_type'])?'selected':'' ?>>
              — Select emergency type —
            </option>
            <?php
            $types = [
                'fire'     => '🔥 Fire',
                'flood'    => '🌊 Flood',
                'accident' => '🚗 Accident',
                'medical'  => '🏥 Medical Emergency',
                'other'    => '⚠️ Other',
            ];
            foreach ($types as $val => $label):
                $sel = (($old['disaster_type'] ?? '') === $val) ? 'selected' : '';
            ?>
              <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Location -->
        <div class="form-group">
          <label for="location">Location / Address *</label>
          <input type="text" id="location" name="location"
                 placeholder="e.g. 45 Main Street, Chennai, Tamil Nadu"
                 value="<?= htmlspecialchars($old['location'] ?? '') ?>"
                 required>
        </div>

        <!-- Date & Time -->
        <div class="form-group">
          <label for="reported_at">Date & Time of Incident *</label>
          <input type="datetime-local" id="reported_at" name="reported_at"
                 value="<?= htmlspecialchars($old['reported_at'] ?? $default_dt) ?>"
                 max="<?= date('Y-m-d\TH:i') ?>"
                 required>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label for="description">Description *</label>
          <textarea id="description" name="description"
                    placeholder="Describe the emergency in detail — number of people affected, severity, any hazards present..."
                    required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
          <small class="text-muted" style="font-size:0.75rem;margin-top:0.3rem;display:block">
            Minimum 10 characters. Be as specific as possible.
          </small>
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:0.5rem">
          <button type="submit" class="btn btn-primary" style="flex:1;min-width:160px">
            🚨 Submit Report
          </button>
          <a href="dashboard.php" class="btn btn-secondary" style="flex:1;min-width:120px">
            ← Cancel
          </a>
        </div>

      </form>
    </div>

  </div>
</div>

<footer class="footer">
  Disaster Emergency Help System &copy; <?= date('Y') ?> &nbsp;|&nbsp;
  Emergency Helpline: <strong>112</strong>
</footer>

</body>
</html>
