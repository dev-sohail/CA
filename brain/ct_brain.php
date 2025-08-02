<?php
###########################################################
#    CyberTirah Custom Framework Based Web Application   #
###########################################################

/**
 * Bootstrap Script
 * 
 * Responsibilities:
 * - Load environment variables
 * - Set application constants
 * - Initialize sessions and PHP settings
 * - Define directory paths
 * - Load core framework and utility classes
 * - Initialize registry and services
 * - Load settings from DB or cache
 * - Setup global error and shutdown handlers
 * 
 * @version 1.1.0
 * @author CyberTirah
 */

// Prevent direct access
if (!defined('FRAMEWORK_ENTRY')) {
    define('FRAMEWORK_ENTRY', true);
}

// Define root path
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

/**
 * 1. Environment Variables Loader
 */
function loadEnvironmentVariables(): void
{
    $envFile = ROOT . '/.env';

    if (!file_exists($envFile)) {
        http_response_code(500);
        echo '<div style="text-align:center; font-family:Arial,sans-serif; margin-top:50px;">';
        echo '<h1 style="color:#e74c3c;"><code>.env file not found</code></h1>';
        echo '<p>Please create a .env file in the root directory.</p>';
        echo '<a href="https://docs.cybertirah.com/env-setup" style="color:#3498db;">See Documentation</a>';
        echo '</div>';
        exit(1);
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

loadEnvironmentVariables();

/**
 * 2. Application Constants
 */
define('APP_INSTANCE', 'Y');
define('ADMIN_PANEL', 'N');
define('VERSION', '1.0.0');
define('APP_START_TIME', microtime(true));
define('SITE_ICON', $_ENV['SITE_ICON'] ?? '/favicon.ico');

// Timezone setup
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// CORS Headers
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: ' . ($_ENV['CORS_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * 3. Session Configuration
 */
function initializeSession(): void
{
    $forceHttps = filter_var($_ENV['FORCE_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 0),
        'path'     => $_ENV['SESSION_PATH'] ?? '/',
        'domain'   => $_ENV['SESSION_DOMAIN'] ?? '',
        'secure'   => $forceHttps && $isHttps,
        'httponly' => true,
        'samesite' => $_ENV['SESSION_SAMESITE'] ?? 'Strict'
    ]);

    session_name($_ENV['SESSION_NAME'] ?? 'CAFSESSID');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

initializeSession();

/**
 * 4. PHP Configuration
 */
function configurePHP(): void
{
    // Version check
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        http_response_code(500);
        exit('This application requires PHP 7.4 or higher. Current version: ' . PHP_VERSION);
    }

    $devMode = filter_var($_ENV['DEV_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN);

    // Error reporting
    if ($devMode) {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        ini_set('display_errors', '1');
    } else {
        error_reporting(0);
        ini_set('display_errors', '0');
    }

    ini_set('log_errors', '1');
    ini_set('memory_limit', $_ENV['MEMORY_LIMIT'] ?? '256M');
    set_time_limit((int)($_ENV['TIME_LIMIT'] ?? 300));

    // Additional PHP settings
    ini_set('max_execution_time', $_ENV['MAX_EXECUTION_TIME'] ?? '300');
    ini_set('max_input_time', $_ENV['MAX_INPUT_TIME'] ?? '300');
    ini_set('post_max_size', $_ENV['POST_MAX_SIZE'] ?? '50M');
    ini_set('upload_max_filesize', $_ENV['UPLOAD_MAX_FILESIZE'] ?? '50M');
}

configurePHP();

/**
 * 5. Directory Path Constants
 */
define('HTTP_HOST', $_SERVER['HTTP_HOST'] ?? 'localhost');
define('HTTPS_HOST', $_SERVER['HTTP_HOST'] ?? 'localhost');
define('REQUEST_SCHEME', $_SERVER['REQUEST_SCHEME'] ?? 'http');


// CORE directories
define('DIR_CONFIG', ROOT . '/brain');
define('DIR_MODULES', ROOT . '/modules');
define('DIR_STORAGE', ROOT . '/storage');
define('DIR_CLASSES', DIR_CONFIG . '/classes');
define('DIR_CORE', DIR_CONFIG . '/core');
define('DIR_CONFIG_CONTROLLERS', DIR_CONFIG . '/controllers');
define('DIR_CONFIG_MODELS', DIR_CONFIG . '/models');
define('DIR_CONFIG_VIEWS', DIR_CONFIG . '/views');
define('DIR_CONFIG_MIDDLEWARE', DIR_CONFIG . '/middleware');


// ROLES directories
define('DIR_APP', DIR_MODULES . '/app');
define('DIR_ADMIN', DIR_MODULES . '/admin');
define('DIR_AI', DIR_MODULES . '/ai');
define('DIR_API', DIR_MODULES . '/api');


// Public and storage directories
define('DIR_CSS', DIR_STORAGE . '/css');
define('DIR_JS', DIR_STORAGE . '/js');
define('DIR_UPLOADS', DIR_STORAGE . '/uploads');
define('DIR_CACHE', DIR_STORAGE . '/cache');
define('DIR_AUTOMATE', DIR_STORAGE . '/automate');
define('DIR_SCRIPTS', DIR_AUTOMATE . '/scripts');
define('DIR_LOGS', DIR_STORAGE . '/logs');
// Language and configuration
define('DIR_LANGUAGES', DIR_STORAGE . '/lang');


/**
 * 6. Database Configuration
 */
define('DB_DRIVER', $_ENV['DB_DRIVER'] ?? 'mysql');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'ct_frame');
define('DB_PREFIX', $_ENV['DB_PREFIX'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
define('DB_COLLATION', $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci');

/**
 * 7. Mail Configuration
 */
define('MAIL_DRIVER', $_ENV['MAIL_DRIVER'] ?? 'smtp');
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'localhost');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? '587');
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'CyberTirah Framework');

/**
 * 8. Safe File Inclusion Helper
 */
function safeRequire(string $file, bool $required = true): bool
{
    if (!file_exists($file)) {
        if ($required) {
            $error = "Critical file not found: $file";
            error_log($error);
            http_response_code(500);
            die("Framework Error: Missing required file.");
        }
        return false;
    }

    require_once $file;
    return true;
}

/**
 * 9. Load Core Framework Files
 */
$coreFiles = [
    DIR_CORE . '/controller.php',
    DIR_CORE . '/middleware.php',
    DIR_CORE . '/model.php',
    DIR_CORE . '/registry.php',
    DIR_CORE . '/router.php'
];

foreach ($coreFiles as $file) {
    safeRequire($file);
}

/**
 * 10. Load Classes
 */

// Simple function to find and load utility classes
function loadUtilityClass(string $className): bool
{
    $searchDirs = [
        'api',
        'auth',
        'business',
        'cache',
        'commerce',
        'console',
        'console/commands',
        'core',
        'database',
        'security',
        'events',
        'exceptions',
        'helpers',
        'http',
        'localization',
        'logging',
        'media',
        'providers',
        'services',
        'support',
        'system',
        'testing',
        'view',
    ];

    // Check root
    $rootFile = DIR_CLASSES . "/{$className}.php";
    if (file_exists($rootFile)) {
        safeRequire($rootFile, false);
        return true;
    }

    // Check subdirectories
    foreach (array_unique($searchDirs) as $dir) {
        $file = DIR_CLASSES . "/{$dir}/{$className}.php";
        if (file_exists($file)) {
            safeRequire($file, false);
            return true;
        }
    }

    return false;
}



/**
 * Automatically scan and return all PHP file base names (without .php) inside DIR_CLASSES
 */
function getAllUtilityClassNames(): array
{
    $classNames = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(DIR_CLASSES, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $classNames[] = $file->getBasename('.php');
        }
    }

    return array_unique($classNames);
}

// Auto-scan utility class names
$utilityClasses = getAllUtilityClassNames();

// echo '<pre>';print_r($utilityClasses);exit;
// Load all utility classes
foreach ($utilityClasses as $class) {
    loadUtilityClass($class);
}

/**
 * 12. Initialize Registry & Core Services
 */
function initializeServices(): Registry
{
    $registry = Registry::getInstance();

    // Core services
    $registry->set('request', new Request());
    $registry->set('response', new Response());
    $registry->set('session', new Session());
    $registry->set('config', new Config());

    // Security: Encryption
    if (class_exists('Encryption')) {
        $encryptionKey = $_ENV['APP_KEY'] ?? bin2hex(random_bytes(32));
        $registry->set('encryption', new Encryption($encryptionKey));
    }

    // Database
    if (class_exists('Database')) {
        $registry->set('db', new Database([
            'hostname' => DB_HOST,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'database' => DB_DATABASE,
            'port'     => DB_PORT,
        ]));
    }

    // Auto-discover utility classes
    $utilityClasses = getAllUtilityClassNames();
    $loaded = [];

    foreach ($utilityClasses as $className) {
        $key = strtolower($className);

        // Avoid duplicates or existing services
        if ($registry->has($key) || isset($loaded[$key])) {
            continue;
        }

        // Load the class file if not yet declared
        if (!class_exists($className)) {
            loadUtilityClass($className);
        }

        // Now instantiate
        if (class_exists($className)) {
            try {
                switch ($className) {
                    case 'Logger':
                        $instance = new Logger('app.log');
                        break;

                    case 'User':
                    case 'Auth':
                        $instance = new $className($registry);
                        break;

                    case 'Debugger':
                        $debugMode = filter_var($_ENV['DEV_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN);
                        $instance = new Debugger(
                            $debugMode ? 'development' : 'production',
                            DIR_LOGS . '/debug.log'
                        );
                        break;

                    default:
                        $instance = new $className(); // generic constructor
                        break;
                }

                $registry->set($key, $instance);
                $loaded[$key] = true;

            } catch (Throwable $e) {
                // Skip silently on constructor failure
                continue;
            }
        }
    }

    return $registry;
}

$registry = initializeServices();

/**
 * 13. Load Application Settings
 */
function loadApplicationSettings(Registry $registry): void
{
    $cacheFile = DIR_CACHE . '/settings.cache.php';
    $settings = [];

    try {
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
            // Load from cache if less than 1 hour old
            $settings = include $cacheFile;
        } else {
            // Load from database
            $db = $registry->get('db');
            if ($db && method_exists($db, 'query')) {
                $result = $db->query("SELECT `key`, `value` FROM " . DB_PREFIX . "settings WHERE status = 1");
                $settings = $result->rows ?? [];

                // Cache the settings
                if (!empty($settings)) {
                    $cacheDir = dirname($cacheFile);
                    if (!is_dir($cacheDir)) {
                        mkdir($cacheDir, 0755, true);
                    }
                    file_put_contents($cacheFile, '<?php return ' . var_export($settings, true) . ';');
                }
            }
        }

        // Set configuration values
        $config = $registry->get('config');
        if ($config) {
            foreach ($settings as $setting) {
                if (isset($setting['key']) && isset($setting['value'])) {
                    $config->set($setting['key'], $setting['value']);
                }
            }
        }
    } catch (Throwable $e) {
        if ($registry->get('logger')) {
            $registry->get('logger')->error('Failed to load settings: ' . $e->getMessage());
        }
    }
}

loadApplicationSettings($registry);

/**
 * 14. Global Error Handler
 */
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($registry) {
    $errorTypes = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "{$errorType}: {$errstr} in {$errfile} on line {$errline}";

    // Log error
    if ($registry->get('logger')) {
        $registry->get('logger')->error($message);
    } else {
        error_log($message);
    }

    // Display error in development mode
    $devMode = filter_var($_ENV['DEV_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN);
    if ($devMode && $registry->get('debugger')) {
        $registry->get('debugger')->displayError($message, $errno);
    }

    // Don't execute PHP internal error handler
    return true;
});

/**
 * 15. Exception Handler
 */
set_exception_handler(function ($exception) use ($registry) {
    $message = "Uncaught Exception: " . $exception->getMessage() .
        " in " . $exception->getFile() .
        " on line " . $exception->getLine();

    if ($registry->get('logger')) {
        $registry->get('logger')->error($message);
        $registry->get('logger')->error("Stack trace: \n" . $exception->getTraceAsString());
    }

    $devMode = filter_var($_ENV['DEV_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN);
    if ($devMode) {
        echo "<h1>Uncaught Exception</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        echo "Internal Server Error";
    }
});

/**
 * 16. Shutdown Handler
 */
register_shutdown_function(function () use ($registry) {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";

        if ($registry->get('logger')) {
            $registry->get('logger')->error($message);
        }

        $devMode = filter_var($_ENV['DEV_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if ($devMode && $registry->get('debugger')) {
            $registry->get('debugger')->displayError($message, $error['type']);
        }
    }

    // Log execution time
    $executionTime = microtime(true) - APP_START_TIME;
    if ($registry->get('logger') && $executionTime > 1.0) {
        $registry->get('logger')->info("Slow request detected: {$executionTime}s");
    }
});

/**
 * 17. Helper Functions
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

function config(string $key, $default = null)
{
    global $registry;
    $config = $registry->get('config');
    return $config ? $config->get($key, $default) : $default;
}

function app(string $service)
{
    global $registry;
    return $service ? $registry->get($service) : $registry;
}

function cache(string $key, $value = null, int $ttl = 3600)
{
    $cache = app('cache');
    if (!$cache) return null;

    if ($key === null) return $cache;
    if ($value === null) return $cache->get($key);

    return $cache->set($key, $value, $ttl);
}

function logger(string $level, string $message)
{
    $log = app('logger');
    if (!$log) return null;

    if ($level === null) return $log;
    if ($message === null) return $log;

    return $log->log($level, $message);
}

define('FRAMEWORK_LOADED', true);
