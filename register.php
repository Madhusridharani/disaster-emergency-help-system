<?php
// register.php - User Registration Page
session_start();
require_once 'db.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors  = [];
$success = '';
$old     = []; // repopulate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize inputs
    $name    = sanitize($conn, $_POST['name']    ?? '');
    $email   = sanitize($conn, $_POST['email']   ?? '');
    $phone   = sanitize($conn, $_POST['phone']   ?? '');
    $password        = $_POST['password']         ?? '';
    $confirm_password= $_POST['confirm_password'] ?? '';
    $old = compact('name', 'email', 'phone');

    // Validation rules
    if (empty($name))  $errors[] = 'Full name is required.';
    if (strlen($name) < 3) $errors[] = 'Name must be at least 3 characters.';

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9\+\-\s\(\)]{7,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'This email address is already registered.';
        }
        $stmt->close();
    }

    // Insert new user
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, phone, password, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("ssss", $name, $email, $phone, $hashed);

        if ($stmt->execute()) {
            setFlash('success', 'Account created successfully! Please log in.');
            header("Location: index.php");
            exit();
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Disaster Emergency Help System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:520px">

    <div class="auth-logo">
      <span class="icon">🆘</span>
      <h1>CREATE ACCOUNT</h1>
      <p>Join the Emergency Response Network</p>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <div>
          <strong>⚠ Please fix the following:</strong>
          <ul style="margin:0.5rem 0 0 1.2rem;padding:0">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php" novalidate>

      <!-- Full Name -->
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name"
               placeholder="John Doe"
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               required>
      </div>

      <!-- Email + Phone -->
      <div class="form-row">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                 required>
        </div>
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone"
                 placeholder="+91 9876543210"
                 value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                 required>
        </div>
      </div>

      <!-- Passwords -->
      <div class="form-row">
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Min. 6 characters"
                 required>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 placeholder="Repeat password"
                 required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:0.5rem">
        ✅ Create Account
      </button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="index.php">Sign in here</a>
    </div>

  </div>
</div>
</body>
</html>
