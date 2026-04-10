<?php
// db.php - Database Connection File
// Configure your MySQL credentials below

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'disaster_db');

// Create connection using MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("
    <div style='font-family:monospace;background:#1a0000;color:#ff4444;padding:2rem;border-radius:8px;margin:2rem auto;max-width:600px;border:1px solid #ff4444'>
        <strong>⚠ Database Connection Failed</strong><br><br>
        " . $conn->connect_error . "<br><br>
        <small>Please check your credentials in db.php and ensure MySQL is running.</small>
    </div>");
}

// Set charset to UTF-8 for security
$conn->set_charset("utf8mb4");

// Helper: Sanitize user input to prevent XSS
function sanitize($conn, $data) {
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

// Helper: Flash message system
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>
