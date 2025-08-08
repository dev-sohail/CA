<?php

/**
 * Application Index File
 * Main entry point for the application
 */

// Define core constants
$rootCandidate = dirname(__DIR__);
if ($rootCandidate && is_dir($rootCandidate) && is_dir($rootCandidate . DIRECTORY_SEPARATOR . 'brain')) {
    define('ROOT', $rootCandidate);
} else {
    die("❌ Unable to determine ROOT path.");
}

define('DS', DIRECTORY_SEPARATOR);
define('INSTALL_LOCK_FILE', ROOT . DS . 'installed.lock');
define('BRAIN_DIR', ROOT . DS . 'brain');
define('BOOT_FILENAME', 'ct_brain');
define('BOOT_FILE', BRAIN_DIR . DS . BOOT_FILENAME . '.php');

/**
 * Check if application is installed
 * If not, redirect to installation process
 */
// If installation lock is missing, attempt to load the installer (optional)
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
if (!file_exists(BOOT_FILE)) {
    die('Core application file not found: ' . BOOT_FILE);
}

/**
 * Load the core brain system and dispatch routes
 */
try {
    require_once(BOOT_FILE);

    // Initialize and dispatch application routes
    if (class_exists('Routes')) {
        Routes::init(DIR_MODULES);
        Routes::loadRoutes();
        Routes::dispatch();
        exit;
    }

    // Fallback
    http_response_code(500);
    echo "Routing subsystem not available.";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Application Error: " . htmlspecialchars($e->getMessage());
    if (function_exists('error_log')) {
        error_log("Bootstrap Error: " . $e->getMessage());
    }
}

$allClasses = get_declared_classes();
$myClasses = [];

foreach ($allClasses as $class) {
    $reflect = new ReflectionClass($class);
    if ($reflect->isUserDefined()) {
        $myClasses[] = $class;
    }
}

echo '<pre>';
print_r($myClasses);

exit;
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