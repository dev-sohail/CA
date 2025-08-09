<?php include_once './../../config/config.php'; //connection and constants includes here ?>
<?php

session_start();
session_destroy(); // Destroy the session to log out the user
header('Location: ./../../'); // Redirect to the login page after logout
exit();
?>