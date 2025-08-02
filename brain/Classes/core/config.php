<?php

/**
 * A static class to manage application configuration.
 * It supports loading configurations from files or arrays and
 * provides dot notation access for nested keys.
 */
class Config
{
    /**
     * @var array The loaded configuration data.
     */
    protected static $config = [];

    /**
     * Load configuration from a file or array.
     *
     * @param string|array $source Path to config file or an array of config data.
     * @return void
     */
    public static function load($source)
    {
        // If the source is an array, merge it with the existing configuration.
        if (is_array($source)) {
            self::$config = array_merge(self::$config, $source);
        // If the source is a file path, include the file.
        } elseif (is_file($source)) {
            $data = include $source;
            if (is_array($data)) {
                // If the file returns an array, merge it.
                self::$config = array_merge(self::$config, $data);
            }
        }
    }

    /**
     * Get a config value by key.
     * Supports dot notation for nested keys (e.g., 'database.host').
     *
     * @param string $key The configuration key.
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed The value of the key, or the default value if not found.
     */
    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $segment) {
            // Check if the current segment exists and is an array.
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                // Key not found, return the default value.
                return $default;
            }
        }

        return $value;
    	// return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    /**
     * Set a config value by key.
     * Supports dot notation for nested keys.
     *
     * @param string $key The configuration key.
     * @param mixed $value The value to set.
     * @return void
     */
    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $ref = &self::$config;

        foreach ($keys as $segment) {
            // Create a nested array if it doesn't exist.
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            // Move the reference deeper into the array.
            $ref = &$ref[$segment];
        }

        // Set the final value.
        $ref = $value;

        // $this->data[$key] = $value;
    }

    /**
     * Check if a config key exists.
     *
     * @param string $key The configuration key.
     * @return bool True if the key exists, false otherwise.
     */
    public static function has($key)
    {
        // Use the get method to check for existence with a unique fallback.
        return self::get($key, '__not_found__') !== '__not_found__';
    	// return isset($this->data[$key]);
    }

    /**
     * Get all configuration values.
     *
     * @return array All configuration data.
     */
    protected static function all()
    {
        return self::$config;
    }

    /**
     * Clear all configuration values.
     *
     * @return void
     */
    protected static function clear()
    {
        self::$config = [];
    }
}
