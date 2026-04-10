<?php
// index.php - Login Page
session_start();
require_once 'db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Check if it's admin login
        if ($email === 'admin@disaster.gov' && $password === 'admin123') {
            $_SESSION['user_id']   = 0;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['user_email']= $email;
            $_SESSION['is_admin']  = true;
            setFlash('success', 'Welcome back, Administrator!');
            header("Location: admin.php");
            exit();
        }

        // Check regular user in database
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $email;
                $_SESSION['is_admin']  = false;
                setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
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
  <title>Login — Disaster Emergency Help System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <!-- Logo / Branding -->
    <div class="auth-logo">
      <span class="icon">🚨</span>
      <h1>EMERGENCY HELP</h1>
      <p>Disaster Response System</p>
    </div>

    <!-- Flash message -->
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= $flash['type'] === 'success' ? '✓' : '⚠' ?> <?= $flash['message'] ?>
      </div>
    <?php endif; ?>

    <!-- Error message -->
    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="index.php" novalidate>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autocomplete="email">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Enter your password"
               required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary">
        🔐 Sign In
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php">Register here</a>
      <br><br>
      <small class="text-muted">
        Admin: admin@disaster.gov / admin123
      </small>
    </div>

  </div>
</div>
</body>
</html>
