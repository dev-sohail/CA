<?php

/**
 * Application Index File
 * Main entry point for the application
*/

// Define core constants
$try1 = realpath(__DIR__);
$try2 = realpath(__DIR__ . '../../');
if ($try1 && is_dir($try1) && file_exists($try1 . '/brain/installation.php') && is_dir($try1 . '/brain')) {
    define('ROOT', $try1);
} elseif ($try2 && is_dir($try2) && file_exists($try2 . '/brain/installation.php') && is_dir($try2 . '/brain')) {
    define('ROOT', $try2);
} else {
    die("❌ Unable to determine ROOT path.");
}
define('DS', DIRECTORY_SEPARATOR);
define('INSTALL_LOCK_FILE', ROOT . DS . 'installed.lock');
define('BRAIN_DIR', ROOT . DS . 'brain');
define('BRAIN_FILENAME', 'ctbrain');
define('BRAIN_FILE', BRAIN_DIR . DS . BRAIN_FILENAME . '.php');

/**
 * Check if application is installed
 * If not, redirect to installation process
 */

// print($installationFile = BRAIN_DIR . DS . 'installation.php');exit;
if (!file_exists(INSTALL_LOCK_FILE)) {
    $installationFile = BRAIN_DIR . DS . 'installation.php';

    if (file_exists($installationFile)) {
        require_once($installationFile);
    } else {
        die('Installation file not found. Please ensure the installation system is properly configured.');
    }
    exit;
}

/**
 * Verify core brain file exists
 */
if (!file_exists(BRAIN_FILE)) {
    die('Core application file not found: ' . BRAIN_FILE);
}

/**
 * Load the core brain system
 */
try {
    require_once(BRAIN_FILE);

    // Initialize the router with registry
    if (!class_exists('router')) {
        throw new Exception('Router class not found in brain file');
    }

    if (!isset($registry)) {
        throw new Exception('Registry object not available');
    }

    // Create and run the core system
    $cores = new router($registry);
    $cores->run();

    // Get response handler and output
    $response = $registry->get('response');

    if (!$response) {
        throw new Exception('Response handler not available');
    }

    $response->output();
} catch (Exception $e) {
    // Basic error handling
    http_response_code(500);
    echo "Application Error: " . htmlspecialchars($e->getMessage());

    // Log error if logging is available
    if (function_exists('error_log')) {
        error_log("Bootstrap Error: " . $e->getMessage());
    }
}

/**
 * Development/Debugging Section
 * Uncomment as needed for development purposes
 */
/*
// Password hashing utilities
// echo password_hash('admin2025', PASSWORD_DEFAULT);
// echo md5('admin2025'); // Note: MD5 is not recommended for passwords

// Path debugging
// echo "ROOT: " . ROOT . "\n";
// var_dump(BRAIN_FILE);

// Environment testing
// if (function_exists('env')) {
//     print("APP_NAME: " . env('APP_NAME'));
// }

// Memory and performance info
// echo "Memory Usage: " . memory_get_usage(true) . " bytes\n";
// echo "Peak Memory: " . memory_get_peak_usage(true) . " bytes\n";
*/