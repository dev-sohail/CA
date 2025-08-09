<?php
/**
 * Database Configuration
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'casms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Database options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
]);

// Backup settings
define('DB_BACKUP_ENABLED', true);
define('DB_BACKUP_PATH', BASE_PATH . '/database/backups');
define('DB_BACKUP_RETENTION_DAYS', 30); 