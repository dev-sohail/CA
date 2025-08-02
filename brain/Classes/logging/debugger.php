<?php
/****/

    /**
     * Class Debugger
     *
     * Centralized error, exception, shutdown handling, and logging/debugging utilities
     * for the CyberAfridi Framework.
     *
     * Usage:
     *  - Initialize early with Debugging::init('development', '/path/to/logfile.log');
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
     *  - handleError(int $errno, string $errstr, string $errfile, int $errline): void
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
     */
/****/

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

    protected static array $logs = [];
    protected static array $timers = [];
    /**
     * Path to the debug log file
     * @var string
     */
    protected static string $logFile = DIR_LOGS . '/debug.log';

    /**
     * Optional callback for database query logging
     * @var callable|null
     */
    protected static $dbLogger = null;

    /**
     * Initialize debugging environment and register error handlers
     * 
     * @param string $env Debug environment ('development' enables detailed output)
     * @param string|null $logFile Optional custom log file path
     * @return void
     */
    public static function init(string $env = 'production', $logFile = null): void
    {
        self::$isEnabled = strtolower($env) === 'development';
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();

        if ($logFile !== null) {
            self::$logFile = $logFile;
        }

        self::registerHandlers();
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
     * @return void
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $message = "PHP ERROR [$errno] $errstr in $errfile on line $errline";
        self::log($message);

        if (self::$isEnabled) {
            self::render($message, 'red');
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
        $message = "UNCAUGHT EXCEPTION: " . $e->getMessage() .
                   " in " . $e->getFile() . " on line " . $e->getLine() .
                   "\nTrace:\n" . $e->getTraceAsString();

        self::log($message);

        if (self::$isEnabled) {
            // nl2br for HTML line breaks, orange color to highlight exceptions
            self::render(nl2br($message), 'orange');
        } else {
            // Generic message to avoid exposing sensitive info in production
            echo "Something went wrong.";
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
        if ($error !== null) {
            $message = "FATAL ERROR: [{$error['type']}] {$error['message']} in {$error['file']} on line {$error['line']}";
            self::log($message);

            if (self::$isEnabled) {
                self::render($message, 'darkred');
            }
        }

        if (self::$isEnabled) {
            $executionTime = round(microtime(true) - self::$startTime, 4);
            $memoryUsed = round((memory_get_usage() - self::$startMemory) / 1024 / 1024, 2);
            self::render("Execution Time: {$executionTime}s | Memory Used: {$memoryUsed}MB", 'green');
        }
    }

    // /**
    //  * Append a message to the log file with a timestamp.
    //  * 
    //  * @param string $msg Message to log
    //  * @return void
    //  */
    // public static function log(string $msg): void
    // {
    //     $timestamp = date('[Y-m-d H:i:s]');
    //     file_put_contents(self::$logFile, "$timestamp $msg\n", FILE_APPEND);
    // }

    // /**
    //  * Dumps variables inside a styled <pre> block for easy reading.
    //  * 
    //  * @param mixed ...$vars Variables to dump
    //  * @return void
    //  */
    // public static function dump(...$vars): void
    // {
    //     echo "<pre style='background:#f0f0f0;border:1px solid #ccc;padding:10px;font-size:13px;'>";
    //     foreach ($vars as $var) {
    //         print_r($var);
    //     }
    //     echo "</pre>";
    // }

    /**
     * Dumps variables and then halts execution.
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    public static function dd(...$vars): void
    {
        self::dump(...$vars);
        die;
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
            self::log("SQL: $query | Params: " . json_encode($params));
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
        echo "<pre style='color:$color;padding:10px;background:#fff;border-left:5px solid $color;white-space:pre-wrap;'>$message</pre>";
    }




    /**
     * Start a named timer
     */
    public static function start(string $name): void
    {
        self::$timers[$name] = microtime(true);
    }

    /**
     * End a named timer and return the elapsed time
     */
    public static function end(string $name): float
    {
        if (!isset(self::$timers[$name])) {
            return 0;
        }

        $elapsed = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);

        return round($elapsed, 5);
    }

    /**
     * Dump a variable in readable format
     */
    public static function dump(mixed $var, bool $exit = false): void
    {
        echo '<pre style="background:#111;color:#0f0;padding:10px;border-radius:5px;overflow:auto;">';
        print_r($var);
        echo '</pre>';

        if ($exit) {
            exit;
        }
    }

    // /**
    //  * Alias of dump($var, true)
    //  */
    // public static function dd(mixed $var): void
    // {
    //     self::dump($var, true);
    // }

    /**
     * Log a debug message
     */
    public static function log(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] $message";

        if (!empty($context)) {
            $entry .= ' ' . json_encode($context);
        }

        self::$logs[] = $entry;

        // Save immediately
        file_put_contents(self::$logFile, $entry . PHP_EOL, FILE_APPEND);
    }

    /**
     * Set custom log file
     */
    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
    }

    // /**
    //  * Capture and format PHP error
    //  */
    // public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    // {
    //     $error = "Error [$errno]: $errstr in $errfile on line $errline";
    //     self::log($error);
    // }

    /**
     * Register debugger error handler
     */
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
    }

    /**
     * Clear the log file (dev only)
     */
    public static function clearLog(): void
    {
        file_put_contents(self::$logFile, '');
    }

    /**
     * Get all collected logs from memory (not file)
     */
    public static function getLogs(): array
    {
        return self::$logs;
    }
}