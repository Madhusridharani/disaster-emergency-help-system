<?php
// logout.php - Destroy session and redirect to login
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>
