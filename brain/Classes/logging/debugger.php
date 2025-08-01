<?php
/**
 * Class Debugger
 *
 * Centralized error, exception, shutdown handling, and logging/debugging utilities
 * for the CyberAfridi Framework.
 *
 * Usage:
 *  - Initialize early with Debugger::init('development', '/path/to/logfile.log');
 *  - Automatically handles PHP errors, uncaught exceptions, and fatal shutdown errors.
 *  - Logs all errors and exceptions to a log file.
 *  - Displays detailed error info in development mode (colored output).
 *  - Provides helper methods for dumping variables (dump, dd).
 *  - Supports registering a custom database query logger callback.
 *
 * Public API:
 *  - init(string $env, string|null $logFile): void
 *      Initialize debugging environment ('development' or 'production') and log file path.
 *
 *  - dump(mixed ...$vars): void
 *      Nicely output variables wrapped in styled <pre> tag.
 *
 *  - dd(mixed ...$vars): void
 *      dump() + terminate script execution.
 *
 *  - setDBLogger(callable $callback): void
 *      Register a callback to handle DB query logging.
 *
 *  - logQuery(string $query, array $params = []): void
 *      Log database query and parameters using registered callback or default log.
 *
 * Error Handling:
 *  - handleError(int $errno, string $errstr, string $errfile, int $errline): bool
 *      Handles PHP runtime errors, logs and optionally displays them.
 *
 *  - handleException(Throwable $e): void
 *      Handles uncaught exceptions, logs and optionally displays them.
 *
 *  - handleShutdown(): void
 *      Handles fatal errors on script shutdown, logs and optionally displays them.
 *      Also displays execution time and memory usage if enabled.
 *
 * Logging:
 *  - log(string $msg): void
 *      Append a timestamped message to the configured log file.
 *
 * Rendering:
 *  - render(string $message, string $color = 'black'): void
 *      Outputs a styled <pre> block with the message in the specified color.
 *
 * Notes:
 *  - Enable detailed error display only in development environments.
 *  - Log file path can be customized in init().
 *  - Automatically creates log directory if it doesn't exist.
 */
class Debugger
{
    /**
     * Flag to indicate if debugging is enabled (development mode)
     * @var bool
     */
    protected static bool $isEnabled = false;

    /**
     * Timestamp when debugging started (microseconds) for execution time measurement
     * @var float
     */
    protected static float $startTime;

    /**
     * Memory usage at start for measuring memory consumption
     * @var int
     */
    protected static int $startMemory;

    /**
     * Path to the debug log file
     * @var string
     */
    protected static string $logFile = __DIR__ . '/../storage/logs/debug.log';

    /**
     * Optional callback for database query logging
     * @var callable|null
     */
    protected static $dbLogger = null;

    /**
     * Error type mappings for better error display
     * @var array
     */
    protected static array $errorTypes = [
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
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];

    /**
     * Initialize debugging environment and register error handlers
     *
     * @param string $env Debug environment ('development' enables detailed output)
     * @param string|null $logFile Optional custom log file path
     * @return void
     */
    public static function init(string $env = 'production', ?string $logFile = null): void
    {
        self::$isEnabled = strtolower($env) === 'development';
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();
        
        if ($logFile !== null) {
            self::$logFile = $logFile;
        }

        // Ensure log directory exists
        self::ensureLogDirectory();
        
        self::registerHandlers();
    }

    /**
     * Ensure the log directory exists and is writable
     *
     * @return void
     */
    protected static function ensureLogDirectory(): void
    {
        $logDir = dirname(self::$logFile);
        
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                throw new RuntimeException("Cannot create log directory: $logDir");
            }
        }

        if (!is_writable($logDir)) {
            throw new RuntimeException("Log directory is not writable: $logDir");
        }
    }

    /**
     * Register PHP error, exception, and shutdown handlers
     *
     * Configures error display, logging, and error reporting level.
     *
     * @return void
     */
    protected static function registerHandlers(): void
    {
        ini_set('display_errors', self::$isEnabled ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$logFile);
        error_reporting(E_ALL);

        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleShutdown']);
    }

    /**
     * Error handler callback for PHP runtime errors
     *
     * Logs the error and optionally displays it if debugging is enabled.
     *
     * @param int $errno Error level code
     * @param string $errstr Error message
     * @param string $errfile File where error occurred
     * @param int $errline Line number of error
     * @return bool True to prevent PHP's internal error handler from running
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Don't handle suppressed errors (with @)
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::$errorTypes[$errno] ?? 'Unknown Error';
        $message = "PHP {$errorType} [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
        
        self::log($message);
        
        if (self::$isEnabled) {
            $color = self::getErrorColor($errno);
            self::render($message, $color);
        }

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Get appropriate color for error type
     *
     * @param int $errno Error number
     * @return string Color name
     */
    protected static function getErrorColor(int $errno): string
    {
        switch ($errno) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'red';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'orange';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'blue';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'purple';
            default:
                return 'gray';
        }
    }

    /**
     * Exception handler callback for uncaught exceptions
     *
     * Logs the exception message and stack trace, and optionally displays
     * detailed info in development mode or a generic message in production.
     *
     * @param Throwable $e The uncaught exception object
     * @return void
     */
    public static function handleException(Throwable $e): void
    {
        $message = sprintf(
            "UNCAUGHT EXCEPTION [%s]: %s in %s on line %d\nStack Trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        self::log($message);

        if (self::$isEnabled) {
            // Enhanced exception display with better formatting
            $displayMessage = sprintf(
                "<strong>%s</strong>: %s\n<strong>File:</strong> %s\n<strong>Line:</strong> %d\n\n<strong>Stack Trace:</strong>\n%s",
                get_class($e),
                htmlspecialchars($e->getMessage()),
                htmlspecialchars($e->getFile()),
                $e->getLine(),
                htmlspecialchars($e->getTraceAsString())
            );
            self::render($displayMessage, 'darkred');
        } else {
            // Generic message to avoid exposing sensitive info in production
            http_response_code(500);
            echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
            echo "<h1>Something went wrong</h1>";
            echo "<p>The application encountered an unexpected error. Please try again later.</p>";
            echo "</body></html>";
        }
    }

    /**
     * Handles shutdown tasks, including fatal error detection and resource usage reporting.
     *
     * - Checks if a fatal error occurred during script execution and logs/displays it.
     * - If debugging is enabled, outputs total script execution time and memory used.
     *
     * @return void
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        // Check if it's a fatal error
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $errorType = self::$errorTypes[$error['type']] ?? 'Fatal Error';
            $message = "FATAL ERROR [{$errorType}]: {$error['message']} in {$error['file']} on line {$error['line']}";
            
            self::log($message);
            
            if (self::$isEnabled) {
                self::render($message, 'darkred');
            }
        }

        // Display performance metrics in development mode
        if (self::$isEnabled) {
            $executionTime = round(microtime(true) - self::$startTime, 4);
            $memoryUsed = round((memory_get_usage() - self::$startMemory) / 1024 / 1024, 2);
            $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2);
            
            $performanceMessage = "Execution Time: {$executionTime}s | Memory Used: {$memoryUsed}MB | Peak Memory: {$peakMemory}MB";
            self::render($performanceMessage, 'green');
        }
    }

    /**
     * Append a message to the log file with a timestamp.
     *
     * @param string $msg Message to log
     * @return void
     */
    public static function log(string $msg): void
    {
        $timestamp = date('[Y-m-d H:i:s]');
        $logEntry = "{$timestamp} {$msg}" . PHP_EOL;
        
        // Use file locking to prevent corruption in concurrent access
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Dumps variables inside a styled <pre> block for easy reading.
     *
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    public static function dump(...$vars): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = basename($backtrace['file'] ?? 'unknown');
        $line = $backtrace['line'] ?? 'unknown';
        
        echo "<div style='margin:10px 0;'>";
        echo "<div style='background:#2c3e50;color:white;padding:5px 10px;font-size:12px;border-radius:3px 3px 0 0;'>";
        echo "Debug Output - {$file}:{$line}";
        echo "</div>";
        echo "<pre style='background:#f8f9fa;border:1px solid #dee2e6;padding:15px;margin:0;font-size:13px;line-height:1.4;border-radius:0 0 3px 3px;overflow-x:auto;'>";
        
        foreach ($vars as $index => $var) {
            if (count($vars) > 1) {
                echo "--- Variable " . ($index + 1) . " ---\n";
            }
            
            if (is_string($var)) {
                echo "string(" . strlen($var) . ") \"" . htmlspecialchars($var) . "\"\n";
            } elseif (is_array($var) || is_object($var)) {
                print_r($var);
            } else {
                var_dump($var);
            }
            
            if ($index < count($vars) - 1) {
                echo "\n";
            }
        }
        
        echo "</pre></div>";
    }

    /**
     * Dumps variables and then halts execution.
     *
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    public static function dd(...$vars): void
    {
        self::dump(...$vars);
        exit(1);
    }

    /**
     * Sets a custom database query logger callback.
     *
     * @param callable $callback Function accepting query string and params
     * @return void
     */
    public static function setDBLogger(callable $callback): void
    {
        self::$dbLogger = $callback;
    }

    /**
     * Logs a database query using either the custom DB logger or the default log.
     *
     * @param string $query SQL query string
     * @param array $params Query parameters
     * @return void
     */
    public static function logQuery(string $query, array $params = []): void
    {
        if (is_callable(self::$dbLogger)) {
            call_user_func(self::$dbLogger, $query, $params);
        } elseif (self::$isEnabled) {
            $formattedParams = empty($params) ? 'None' : json_encode($params, JSON_PRETTY_PRINT);
            $message = "SQL Query: {$query}\nParameters: {$formattedParams}";
            self::log($message);
        }
    }

    /**
     * Outputs a styled HTML <pre> block with the given message and color.
     *
     * @param string $message Message to render
     * @param string $color CSS color name or code
     * @return void
     */
    public static function render(string $message, string $color = 'black'): void
    {
        $timestamp = date('H:i:s');
        
        echo "<div style='margin:5px 0;font-family:monospace;'>";
        echo "<pre style='color:{$color};padding:12px;background:#fff;border-left:4px solid {$color};";
        echo "white-space:pre-wrap;word-wrap:break-word;box-shadow:0 1px 3px rgba(0,0,0,0.1);";
        echo "border-radius:0 3px 3px 0;margin:0;'>";
        echo "<span style='color:#666;font-size:11px;'>[{$timestamp}] </span>";
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo "</pre></div>";
    }

    /**
     * Get current debugging status
     *
     * @return bool True if debugging is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$isEnabled;
    }

    /**
     * Get the log file path
     *
     * @return string Path to log file
     */
    public static function getLogFile(): string
    {
        return self::$logFile;
    }

    /**
     * Clear the debug log file
     *
     * @return bool True on success, false on failure
     */
    public static function clearLog(): bool
    {
        return file_put_contents(self::$logFile, '', LOCK_EX) !== false;
    }

    /**
     * Get execution statistics
     *
     * @return array Array containing execution time and memory usage
     */
    public static function getStats(): array
    {
        return [
            'execution_time' => round(microtime(true) - self::$startTime, 4),
            'memory_used' => round((memory_get_usage() - self::$startMemory) / 1024 / 1024, 2),
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'current_memory' => round(memory_get_usage() / 1024 / 1024, 2)
        ];
    }
}