<?php

class Logger
{
    protected string $logPath;
    protected string $defaultLogFile;
    protected bool $splitByLevel;

    /**
     * Constructor
     *
     * @param string $logFileName The base log file name (e.g., "app.log").
     * @param bool $splitByLevel Whether to split logs by severity level.
     */
    public function __construct(string $logFileName = 'frame.log', bool $splitByLevel = true)
    {
        $this->logPath = rtrim(DIR_LOGS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->defaultLogFile = $logFileName;
        $this->splitByLevel = $splitByLevel;

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Log a message with a given level
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);
        $date = date('Y-m-d H:i:s');
        $interpolated = $this->interpolate($message, $context);
        $logLine = "[$date] [$level] $interpolated" . PHP_EOL;

        $filename = $this->getLogFileName($level);
        file_put_contents($filename, $logLine, FILE_APPEND);
    }

    // --- Shortcut Methods ---
    public function debug(string $message, array $context = []): void { $this->log('DEBUG', $message, $context); }
    public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); }
    public function notice(string $message, array $context = []): void { $this->log('NOTICE', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
    public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
    public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }

    /**
     * Set default log file name
     */
    public function setLogFile(string $filename): void
    {
        $this->defaultLogFile = $filename;
    }

    /**
     * Get log file name based on level or default
     */
    protected function getLogFileName(string $level = ''): string
    {
        $datePrefix = date('Y-m-d');
        if ($this->splitByLevel && $level) {
            return $this->logPath . "{$datePrefix}-" . strtolower($level) . '.log';
        }

        return $this->logPath . "{$datePrefix}-" . $this->defaultLogFile;
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
