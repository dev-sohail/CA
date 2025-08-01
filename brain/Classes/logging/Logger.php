<?php
/**
 * Class Logger
 *
 * A comprehensive logging utility with multiple log levels and timestamped entries.
 * Supports info, error, debug, warning, and critical log levels.
 *
 * Usage:
 * ```php
 * define('DIR_LOGS', __DIR__ . '/logs/');
 * $logger = new Logger('system.log');
 * $logger->info('Application started');
 * $logger->error('Database connection failed');
 * $logger->debug('Processing user input');
 * ```
 *
 * Features:
 * - Multiple log levels (INFO, ERROR, DEBUG, WARNING, CRITICAL)
 * - Timestamped log entries
 * - Thread-safe file writing with locks
 * - Configurable log directory via DIR_LOGS constant
 * - Backward compatible with simple write() method
 *
 * Requirements:
 * - The `DIR_LOGS` constant must be defined and writable
 * - PHP 7.4+ for typed properties
 */
class Logger
{
    /**
     * Full path to the log file.
     *
     * @var string
     */
    private string $file;

    /**
     * Log level constants
     */
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * Logger constructor.
     *
     * @param string $filename The log filename (e.g., 'error.log')
     *
     * @throws RuntimeException if DIR_LOGS is not defined or not writable
     */
    public function __construct(string $filename)
    {
        if (!defined('DIR_LOGS')) {
            throw new \RuntimeException("DIR_LOGS constant is not defined.");
        }

        $dir = rtrim(DIR_LOGS, '/\\');
        $this->file = $dir . DIRECTORY_SEPARATOR . ltrim($filename, '/\\');

        // Ensure directory exists and is writable
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new \RuntimeException("Cannot create log directory: $dir");
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException("Log directory is not writable: $dir");
        }
    }

    /**
     * Write a message to the log file with a timestamp and level.
     *
     * @param string $message The log message
     * @param string $level   The log level
     * @return void
     */
    public function write(string $message, string $level = self::LEVEL_INFO): void
    {
        $entry = sprintf(
            "[%s] [%s] %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            PHP_EOL
        );

        file_put_contents($this->file, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log an informational message.
     *
     * @param string $message The log message
     * @return void
     */
    public function info(string $message): void
    {
        $this->write($message, self::LEVEL_INFO);
    }

    /**
     * Log an error message.
     *
     * @param string $message The log message
     * @return void
     */
    public function error(string $message): void
    {
        $this->write($message, self::LEVEL_ERROR);
    }

    /**
     * Log a debug message.
     *
     * @param string $message The log message
     * @return void
     */
    public function debug(string $message): void
    {
        $this->write($message, self::LEVEL_DEBUG);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The log message
     * @return void
     */
    public function warning(string $message): void
    {
        $this->write($message, self::LEVEL_WARNING);
    }

    /**
     * Log a critical message.
     *
     * @param string $message The log message
     * @return void
     */
    public function critical(string $message): void
    {
        $this->write($message, self::LEVEL_CRITICAL);
    }

    /**
     * Log an exception with full stack trace.
     *
     * @param \Throwable $exception The exception to log
     * @param string     $level     The log level (default: ERROR)
     * @return void
     */
    public function exception(\Throwable $exception, string $level = self::LEVEL_ERROR): void
    {
        $message = sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        $this->write($message, $level);
    }

    /**
     * Get the full path to the log file.
     *
     * @return string
     */
    public function getLogFile(): string
    {
        return $this->file;
    }

    /**
     * Check if the log file exists.
     *
     * @return bool
     */
    public function logFileExists(): bool
    {
        return file_exists($this->file);
    }

    /**
     * Get the size of the log file in bytes.
     *
     * @return int|false File size in bytes, or false on failure
     */
    public function getLogFileSize()
    {
        return file_exists($this->file) ? filesize($this->file) : false;
    }

    /**
     * Clear the log file (truncate to zero bytes).
     *
     * @return bool True on success, false on failure
     */
    public function clearLog(): bool
    {
        return file_put_contents($this->file, '', LOCK_EX) !== false;
    }
}