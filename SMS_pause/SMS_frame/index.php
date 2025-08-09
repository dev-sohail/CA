<?php
/**
 * SMS Framework - Main Entry Point
 * 
 * This file serves as the front controller for the MVC framework.
 * All requests are routed through this file.
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the base path
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('SYSTEM_PATH', BASE_PATH . '/system');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('CONFIG_PATH', BASE_PATH . '/config');

// Load the autoloader
require_once SYSTEM_PATH . '/core/Autoloader.php';

// Load configuration
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/database.php';

// Initialize the application
$app = new \System\Core\Application();

// Run the application
$app->run(); 