<?php

/**
 * Class Logger
 *
 * Simple logger with support for log levels and context.
 * You can extend it to implement PSR-3 if needed.
 */
class Logger
{
    protected string $logPath;
    protected string $defaultLogFile = 'app.log';

    /**
     * Constructor
     */
    public function __construct(string $logDirectory = __DIR__ . '/../../logs/')
    {
        $this->logPath = rtrim($logDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Log a message with a given level
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $interpolated = $this->interpolate($message, $context);

        $logLine = "[$date] [$level] $interpolated" . PHP_EOL;

        $filename = $this->getLogFileName();
        file_put_contents($filename, $logLine, FILE_APPEND);
    }

    /**
     * Shortcut methods
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    /**
     * Set default log file name (e.g. "system.log")
     */
    public function setLogFile(string $filename): void
    {
        $this->defaultLogFile = $filename;
    }

    /**
     * Return the full path to the current log file (can be rotated daily)
     */
    protected function getLogFileName(): string
    {
        // Optional: return daily log file
        return $this->logPath . date('Y-m-d') . '-' . $this->defaultLogFile;
    }

    /**
     * Replace placeholders in message with context values
     */
    protected function interpolate(string $message, array $context = []): string
    {
        $replacements = [];
        foreach ($context as $key => $val) {
            $replacements['{' . $key . '}'] = is_scalar($val) ? $val : json_encode($val);
        }

        return strtr($message, $replacements);
    }
}
