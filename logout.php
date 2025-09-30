<?php
include 'includes/config.php';

session_destroy();
$_SESSION['message'] = "You have been logged out successfully!";
$_SESSION['message_type'] = 'success';
header("Location: login.php");
exit();
?>