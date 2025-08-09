<?php
include_once 'conn.php';
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'constants.php';
?>