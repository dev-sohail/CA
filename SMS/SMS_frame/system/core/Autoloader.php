<?php
/**
 * Autoloader Class
 * 
 * Handles automatic class loading for the MVC framework
 */

namespace System\Core;

class Autoloader
{
    /**
     * Register the autoloader
     */
    public static function register()
    {
        spl_autoload_register([new self, 'loadClass']);
    }

    /**
     * Load a class file
     * 
     * @param string $class The class name to load
     * @return void
     */
    public function loadClass($class)
    {
        // Convert namespace separators to directory separators
        $file = BASE_PATH . '/' . str_replace('\\', '/', $class) . '.php';
        
        // Check if the file exists and load it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register the autoloader
Autoloader::register(); 