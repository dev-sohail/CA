<?php

/**
 * Enhanced Bootstrap Script - Production Ready Version
 * * Features:
 * - Comprehensive environment validation
 * - Advanced security headers and CSRF protection
 * - Starup Configuration stored in .env
 * - OOPs to faster its work
 * - Performance monitoring and optimization
 * - Dependency injection container
 * - Configuration management with validation
 * - Classes Autoload with APCu cache machenism
 * - Startup Global Logger & Debuger & Cache
 * - Registry core class to avoid namespaces or manualy load of class
 * - Session security enhancements
 * - Memory and execution time optimization
 *
 * @version 1.4.0
 * @author CyberTirah Development Team
 * @license MIT
 * @since PHP 8.0+
 */

// A simple utility class to handle .env file loading.
// This makes configuration management much more automated.
class Configurations
{
    private array $config = [];

    public function __construct(string $envFile = ROOT . '/.env')
    {
        $this->phpversion();
        if (file_exists($envFile)) {
            $this->loadEnv($envFile);
            error_log(".env file Loaded Successfully");
        } else {
            // Log an error if the .env file is missing, but don't stop execution.
            var_dump(file_exists($envFile));
            exit('<center style="margin-top: 8rem; color: red;">' . ".env file not found at:" . '</br>' . $envFile . '</br>' . " Using default values." . '</center>');
        }
    }

    private function phpversion()
    {
        // Version check
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            http_response_code(500);
            exit('<center style="margin-top: 8rem;">This application requires at least PHP 8.0 or higher. Current version: ' . PHP_VERSION . '</center>');
        }
    }

    private function loadEnv(string $file): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip quotes from the value
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            }

            $this->config[$key] = $value;
            // Set it in the global $_ENV for a traditional approach.
            $_ENV[$key] = $value;
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}

class Bootstrap extends Configurations
{
    /** @var float Stores the start time of the application for performance tracking. */
    private float $startTime;

    /** @var bool Flag to prevent multiple initializations. */
    private bool $isInitialized = false;

    /** @var array Stores the directory paths for the framework. */
    private array $paths = [];

    /** @var array Stores database configuration settings. */
    private array $dbConfig = [];

    /** @var array Stores email configuration settings. */
    private array $mailConfig = [];

    /** @var array Tracks loaded class files. */
    private array $loadedClasses = [];

    /** @var array Defines the core framework files to be loaded. */
    private array $coreFiles = [];

    /** @var Configurations The configuration object for handling .env variables. */
    private Configurations $config;

    /**
     * chunk1 constructor.
     * @param Configurations $config The configuration object.
     */
    private function __construct(Configurations $config)
    {
        $this->startTime = microtime(true);
        $this->config = $config;
        $this->initialize();
    }

    /**
     * The main entry point for the framework bootstrap.
     * This static method orchestrates the entire initialization process.
     */
    public static function boot(): self
    {
        // Define ROOT path and application start time early.
        if (!defined('ROOT')) {
            define('ROOT', dirname(__DIR__));
        }
        if (!defined('APP_START_TIME')) {
            define('APP_START_TIME', microtime(true));
        }

        // Automatically load environment variables from the .env file.
        $config = new Configurations();

        // Return a new instance of the class.
        $instance = new self($config);


        // Load the core framework files.
        try {
            $instance->loadCoreFiles();
            // $registry = new Registry();
        } catch (RuntimeException $e) {
            error_log("Failed to load critical core files: " . $e->getMessage());
            exit('<center style="margin-top: 8rem; color: red;">' . "Critical framework files failed to load. Please check the logs." . '</center>');
        }

        // Register a PSR-4 compliant autoloader.
        if (isset($instance)) {
            spl_autoload_register(function ($className) use ($instance) {
                // First try framework classes
                if ($instance->loadUtilityClass($className)) {
                    return;
                }
                // Then try Modules PSR-4-ish: Modules/Role/Module/Controllers/Class.php
                $classPath = ROOT . '/modules/' . str_replace('\\', '/', ltrim($className, '\\')) . '.php';
                if (is_file($classPath)) {
                    require_once $classPath;
                    return;
                }
            });
        } else {
            throw new Exception("Instance not set before autoloader registration.");
        }
        // spl_autoload_register(function ($className) use ($instance) {
        //     $instance->loadUtilityClass($className);
        // });
        
        return $instance;
    }

    /**
     * Initializes the framework by calling a series of setup methods.
     * This is the main entry point for the bootstrap logic in this chunk.
     */
    private function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->preventDirectAccess();
        $this->initializePaths();
        $this->initializeConfigurations();
        $this->defineApplicationConstants();
        $this->initializeSession();
        $this->configurePHP();
        $this->defineCoreFiles();

        $this->isInitialized = true;
    }

    /**
     * Prevents the script from being accessed directly by checking a predefined constant.
     */
    private function preventDirectAccess(): void
    {
        if (!defined('FRAMEWORK_ENTRY')) {
            define('FRAMEWORK_ENTRY', true);
        }
    }

    /**
     * Initializes all directory path constants for the framework.
     */
    private function initializePaths(): void
    {
        $dirs = ['brain', 'modules', 'storage'];

        foreach ($dirs as $dir) {
            $constName = 'DIR_' . strtoupper($dir);
            $bapaths = ROOT . DIRECTORY_SEPARATOR . $dir;

            if (!defined($constName)) {
                define($constName, $bapaths);
                $this->paths[$constName] = $bapaths;
            }
        }

        foreach ($dirs as $dir) {
            $basePath = ROOT . DIRECTORY_SEPARATOR . $dir;

            if (!is_dir($basePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $dirItem) {
                if (!$dirItem->isDir()) continue;

                $realPath = $dirItem->getRealPath();
                if (!$realPath) continue;

                $const = 'DIR_' . strtoupper(
                    str_replace([ROOT . DIRECTORY_SEPARATOR, '/', '\\'], ['', '_', '_'], $realPath)
                );

                // // Get path relative to ROOT
                // $relativePath = str_replace(ROOT . DIRECTORY_SEPARATOR, '', $realPath);
                // $relativePathParts = preg_split('~[\\/\\\\]+~', $relativePath);

                // // Use only the last folder name for constant
                // $shortName = strtoupper(end($relativePathParts));
                // $const = 'DIR_' . $shortName;

                if (!defined($const)) {
                    define($const, $realPath);
                    $this->paths[$const] = $realPath;
                }
            }
        }

        // Save to JSON file
        file_put_contents(
            DIR_STORAGE_CACHE . '/paths.json',
            json_encode($this->paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }


    /**
     * Initializes database and mail configurations from the new Configuration class.
     */
    private function initializeConfigurations(): void
    {
        // Database Configuration
        $this->dbConfig = [
            'DB_DRIVER' => $this->config->get('DB_DRIVER', 'mysql'),
            'DB_HOST' => $this->config->get('DB_HOST', 'localhost'),
            'DB_PORT' => $this->config->get('DB_PORT', '3306'),
            'DB_USERNAME' => $this->config->get('DB_USERNAME', 'root'),
            'DB_PASSWORD' => $this->config->get('DB_PASSWORD', ''),
            'DB_DATABASE' => $this->config->get('DB_DATABASE', 'ct_frame'),
            'DB_PREFIX' => $this->config->get('DB_PREFIX', ''),
            'DB_CHARSET' => $this->config->get('DB_CHARSET', 'utf8mb4'),
            'DB_COLLATION' => $this->config->get('DB_COLLATION', 'utf8mb4_unicode_ci')
        ];

        // Mail Configuration
        $this->mailConfig = [
            'MAIL_DRIVER' => $this->config->get('MAIL_DRIVER', 'smtp'),
            'MAIL_HOST' => $this->config->get('MAIL_HOST', 'localhost'),
            'MAIL_PORT' => $this->config->get('MAIL_PORT', '587'),
            'MAIL_USERNAME' => $this->config->get('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => $this->config->get('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => $this->config->get('MAIL_ENCRYPTION', 'tls'),
            'MAIL_FROM_ADDRESS' => $this->config->get('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'MAIL_FROM_NAME' => $this->config->get('MAIL_FROM_NAME', 'CyberTirah Framework')
        ];

        // Define configuration constants
        $this->defineConfigConstants();
    }

    /**
     * Defines configuration constants for both database and mail settings.
     */
    private function defineConfigConstants(): void
    {
        foreach (array_merge($this->dbConfig, $this->mailConfig) as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    /**
     * Defines core framework files that need to be loaded.
     */
    private function defineCoreFiles(): void
    {
        // Use glob to find all PHP files in the DIR_BRAIN_CORE directory
        $files = glob(DIR_BRAIN_CORE . '/*.php');

        // Create an associative array from the file paths
        $this->coreFiles = [];
        foreach ($files as $file) {
            // Extract the filename without the extension to use as the key
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->coreFiles[$key] = $file;
        }
        // Save to JSON file
        file_put_contents(
            DIR_STORAGE_CACHE . '/core_paths.json',
            json_encode($this->coreFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Safe file inclusion with error handling.
     * @param string $file The path to the file to include.
     * @param bool $required If true, an exception is thrown if the file is not found.
     * @return bool
     */
    public function safeRequire(string $file, bool $required = true): bool
    {
        if (!file_exists($file)) {
            if ($required) {
                $error = "Critical file not found: $file";
                error_log($error);
                http_response_code(500);
                throw new RuntimeException("Framework Error: Missing required file: " . basename($file));
            }
            return false;
        }

        try {
            require_once $file;
            return true;
        } catch (Throwable $e) {
            if ($required) {
                error_log("Error loading file $file: " . $e->getMessage());
                throw new RuntimeException("Framework Error: Failed to load required file: " . basename($file));
            }
            return false;
        }
    }

    /**
     * Load all core framework files.
     * @return array
     */
    public function loadCoreFiles(): array
    {
        $loadedFiles = [];
        $failedFiles = [];

        foreach ($this->coreFiles as $name => $file) {
            try {
                if ($this->safeRequire($file, true)) {
                    $loadedFiles[] = $name;
                }
            } catch (RuntimeException $e) {
                $failedFiles[] = $name;
                error_log("Failed to load core file: $name - " . $e->getMessage());
            }
        }

        if (!empty($failedFiles)) {
            throw new RuntimeException("Critical core files failed to load: " . implode(', ', $failedFiles));
        }

        return $loadedFiles;
    }

    /**
     * Load a utility class by name.
     * @param string $className The name of the class to load.
     * @return bool
     */
    public function loadUtilityClass(string $className): bool
    {
        $searchDirs = glob(DIR_BRAIN_CLASSES . '/*', GLOB_ONLYDIR);
        foreach (array_unique($searchDirs) as $dir) {
            $base = basename(str_replace('\\', '/', $className));
            $file = $dir . '/' . $base . '.php';
            if (file_exists($file)) {
                $this->safeRequire($file, true);
                $this->loadedClasses[$className] = $file;
                return true;
            }
            // Case-insensitive fallback (for files like response.php, template.php)
            $fileLower = $dir . '/' . strtolower($base) . '.php';
            if (file_exists($fileLower)) {
                $this->safeRequire($fileLower, true);
                $this->loadedClasses[$className] = $fileLower;
                return true;
            }
        }
        return false;
    }

    /**
     * Defines core application constants and sets the timezone.
     */
    public function defineApplicationConstants(): void
    {
        // Application constants
        if (!defined('APP_INSTANCE')) define('APP_INSTANCE', 'Y');
        if (!defined('ADMIN_PANEL')) define('ADMIN_PANEL', 'N');
        if (!defined('VERSION')) define('VERSION', '1.0.0');
        if (!defined('SITE_ICON')) define('SITE_ICON', $this->config->get('SITE_ICON', '/favicon.ico'));

        // Timezone setup
        date_default_timezone_set($this->config->get('APP_TIMEZONE', 'UTC'));

        // Set CORS headers
        $this->setCorsHeaders();
    }

    /**
     * Sets the Cross-Origin Resource Sharing (CORS) headers based on environment variables.
     */
    private function setCorsHeaders(): void
    {
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: ' . $this->config->get('CORS_ORIGIN', '*'));
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

            // Handle preflight OPTIONS requests
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit();
            }
        }
    }

    /**
     * Initializes and configures the session with secure settings from the .env file.
     */
    public function initializeSession(): void
    {
        $useSession = filter_var($this->config->get('USE_SESSION', true), FILTER_VALIDATE_BOOLEAN);

        if (!$useSession) {
            return; // Session is disabled in .env
        }

        // Detect if HTTPS is active
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $forceHttps = filter_var($this->config->get('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);

        // Configure session cookie parameters
        session_set_cookie_params([
            'lifetime' => (int)$this->config->get('SESSION_LIFETIME', 0),
            'path'     => $this->config->get('SESSION_PATH', '/'),
            'domain'   => $this->config->get('SESSION_DOMAIN', ''),
            'secure'   => $forceHttps && $isHttps,
            'httponly' => true,
            'samesite' => $this->config->get('SESSION_SAMESITE', 'Strict')
        ]);

        // Set session name
        session_name($this->config->get('SESSION_NAME', 'CAFSESSID'));

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }


    /**
     * Configures various PHP settings based on the environment variables,
     * including error reporting, memory limits, and execution time.
     */
    public function configurePHP(): void
    {
        $devMode = filter_var($this->config->get('DEV_MODE', true), FILTER_VALIDATE_BOOLEAN);

        // Error reporting
        if ($devMode) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            // Basic PHP settings
            ini_set('log_errors', '1');
            ini_set('memory_limit', $this->config->get('MEMORY_LIMIT', '256M'));
            set_time_limit((int)$this->config->get('TIME_LIMIT', 300));

            // Additional PHP settings
            ini_set('max_execution_time', $this->config->get('MAX_EXECUTION_TIME', '300'));
            ini_set('max_input_time', $this->config->get('MAX_INPUT_TIME', '300'));
            ini_set('post_max_size', $this->config->get('POST_MAX_SIZE', '50M'));
            ini_set('upload_max_filesize', $this->config->get('UPLOAD_MAX_FILESIZE', '50M'));
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    /**
     * Calculates and returns the total execution time since the bootstrap started.
     * @return float
     */
    public function getExecutionTime(): float
    {
        return microtime(true) - $this->startTime;
    }
}

Bootstrap::boot();
$registry = new Registry();