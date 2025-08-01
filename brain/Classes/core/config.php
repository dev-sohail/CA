 
<?php
/**
 * Enhanced Configuration Class
 * Handles application configuration with file loading, dot notation, and caching
 */
class Config
{
    private $data = array();
    private $configPath = '';
    private $cacheEnabled = false;
    private $cached = array();
    private static $instance = null;
    
    /**
     * Constructor
     * @param string $configPath - Path to configuration directory
     * @param bool $autoLoad - Whether to auto-load config files
     */
    public function __construct($configPath = '', $autoLoad = false)
    {
        $this->configPath = rtrim($configPath, '/\\') . DIRECTORY_SEPARATOR;
        
        if ($autoLoad && !empty($configPath)) {
            $this->loadAll();
        }
    }
    
    /**
     * Get singleton instance
     * @param string $configPath - Path to configuration directory
     * @return Config
     */
    public static function getInstance($configPath = '')
    {
        if (self::$instance === null) {
            self::$instance = new self($configPath, true);
        }
        return self::$instance;
    }
    
    /**
     * Get configuration value with dot notation support
     * @param string $key - Configuration key (supports dot notation like 'database.host')
     * @param mixed $default - Default value if key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Check cache first if enabled
        if ($this->cacheEnabled && isset($this->cached[$key])) {
            return $this->cached[$key];
        }
        
        $value = $this->getNestedValue($this->data, $key, $default);
        
        // Cache the result
        if ($this->cacheEnabled) {
            $this->cached[$key] = $value;
        }
        
        return $value;
    }
    
    /**
     * Set configuration value with dot notation support
     * @param string $key - Configuration key (supports dot notation)
     * @param mixed $value - Value to set
     * @return Config - For method chaining
     */
    public function set($key, $value)
    {
        $this->setNestedValue($this->data, $key, $value);
        
        // Clear cache for this key and related keys
        if ($this->cacheEnabled) {
            $this->clearCacheForKey($key);
        }
        
        return $this;
    }
    
    /**
     * Check if configuration key exists
     * @param string $key - Configuration key (supports dot notation)
     * @return bool
     */
    public function has($key)
    {
        return $this->getNestedValue($this->data, $key, '__CONFIG_NOT_FOUND__') !== '__CONFIG_NOT_FOUND__';
    }
    
    /**
     * Remove configuration key
     * @param string $key - Configuration key (supports dot notation)
     * @return Config - For method chaining
     */
    public function remove($key)
    {
        $this->unsetNestedValue($this->data, $key);
        
        // Clear cache for this key
        if ($this->cacheEnabled) {
            $this->clearCacheForKey($key);
        }
        
        return $this;
    }
    
    /**
     * Get all configuration data
     * @return array
     */
    public function all()
    {
        return $this->data;
    }
    
    /**
     * Merge array of configuration data
     * @param array $config - Configuration array to merge
     * @param bool $recursive - Whether to merge recursively
     * @return Config - For method chaining
     */
    public function merge(array $config, $recursive = true)
    {
        if ($recursive) {
            $this->data = array_merge_recursive($this->data, $config);
        } else {
            $this->data = array_merge($this->data, $config);
        }
        
        // Clear all cache
        if ($this->cacheEnabled) {
            $this->cached = array();
        }
        
        return $this;
    }
    
    /**
     * Replace all configuration data
     * @param array $config - New configuration array
     * @return Config - For method chaining
     */
    public function replace(array $config)
    {
        $this->data = $config;
        
        // Clear all cache
        if ($this->cacheEnabled) {
            $this->cached = array();
        }
        
        return $this;
    }
    
    /**
     * Load configuration from file
     * @param string $filename - Configuration file name (without extension)
     * @param string $group - Group name to load config into
     * @return Config - For method chaining
     * @throws Exception if file not found or invalid
     */
    public function load($filename, $group = null)
    {
        $filePath = $this->findConfigFile($filename);
        
        if (!$filePath) {
            throw new Exception("Configuration file '{$filename}' not found");
        }
        
        $config = $this->parseConfigFile($filePath);
        
        if ($group) {
            $this->set($group, $config);
        } else {
            $this->merge($config);
        }
        
        return $this;
    }
    
    /**
     * Load all configuration files from config directory
     * @return Config - For method chaining
     */
    public function loadAll()
    {
        if (empty($this->configPath) || !is_dir($this->configPath)) {
            return $this;
        }
        
        $files = glob($this->configPath . '*.{php,json,ini,yaml,yml}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            try {
                $this->load($filename, $filename);
            } catch (Exception $e) {
                // Log error but continue loading other files
                error_log("Failed to load config file '{$file}': " . $e->getMessage());
            }
        }
        
        return $this;
    }
    
    /**
     * Save configuration to file
     * @param string $filename - File name to save to
     * @param string $format - Format to save in (php, json, ini)
     * @param string $group - Specific group to save (null for all)
     * @return bool - Success status
     */
    public function save($filename, $format = 'php', $group = null)
    {
        if (empty($this->configPath)) {
            throw new Exception("Config path not set");
        }
        
        $data = $group ? $this->get($group, array()) : $this->data;
        $filePath = $this->configPath . $filename . '.' . $format;
        
        switch (strtolower($format)) {
            case 'php':
                return $this->savePhpFile($filePath, $data);
            case 'json':
                return $this->saveJsonFile($filePath, $data);
            case 'ini':
                return $this->saveIniFile($filePath, $data);
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }
    
    /**
     * Enable or disable caching
     * @param bool $enabled - Whether to enable caching
     * @return Config - For method chaining
     */
    public function enableCache($enabled = true)
    {
        $this->cacheEnabled = $enabled;
        if (!$enabled) {
            $this->cached = array();
        }
        return $this;
    }
    
    /**
     * Clear configuration cache
     * @return Config - For method chaining
     */
    public function clearCache()
    {
        $this->cached = array();
        return $this;
    }
    
    /**
     * Get configuration as JSON string
     * @param int $options - JSON encode options
     * @return string
     */
    public function toJson($options = JSON_PRETTY_PRINT)
    {
        return json_encode($this->data, $options);
    }
    
    /**
     * Load configuration from JSON string
     * @param string $json - JSON string
     * @return Config - For method chaining
     */
    public function fromJson($json)
    {
        $config = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }
        
        $this->merge($config);
        return $this;
    }
    
    /**
     * Get nested value using dot notation
     * @param array $array - Array to search in
     * @param string $key - Dot notation key
     * @param mixed $default - Default value
     * @return mixed
     */
    private function getNestedValue($array, $key, $default = null)
    {
        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : $default;
        }
        
        $keys = explode('.', $key);
        $current = $array;
        
        foreach ($keys as $segment) {
            if (!is_array($current) || !isset($current[$segment])) {
                return $default;
            }
            $current = $current[$segment];
        }
        
        return $current;
    }
    
    /**
     * Set nested value using dot notation
     * @param array &$array - Array to modify
     * @param string $key - Dot notation key
     * @param mixed $value - Value to set
     */
    private function setNestedValue(&$array, $key, $value)
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }
        
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = array();
            }
            $current = &$current[$segment];
        }
        
        $current = $value;
    }
    
    /**
     * Unset nested value using dot notation
     * @param array &$array - Array to modify
     * @param string $key - Dot notation key
     */
    private function unsetNestedValue(&$array, $key)
    {
        if (strpos($key, '.') === false) {
            unset($array[$key]);
            return;
        }
        
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$array;
        
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return; // Path doesn't exist
            }
            $current = &$current[$segment];
        }
        
        unset($current[$lastKey]);
    }
    
    /**
     * Find configuration file with various extensions
     * @param string $filename - Base filename
     * @return string|false - Full file path or false if not found
     */
    private function findConfigFile($filename)
    {
        $extensions = ['php', 'json', 'ini', 'yaml', 'yml'];
        
        foreach ($extensions as $ext) {
            $filePath = $this->configPath . $filename . '.' . $ext;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        
        return false;
    }
    
    /**
     * Parse configuration file based on extension
     * @param string $filePath - Full file path
     * @return array - Parsed configuration
     * @throws Exception if file cannot be parsed
     */
    private function parseConfigFile($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'php':
                return $this->parsePhpFile($filePath);
            case 'json':
                return $this->parseJsonFile($filePath);
            case 'ini':
                return $this->parseIniFile($filePath);
            case 'yaml':
            case 'yml':
                return $this->parseYamlFile($filePath);
            default:
                throw new Exception("Unsupported file format: {$extension}");
        }
    }
    
    /**
     * Parse PHP configuration file
     * @param string $filePath - File path
     * @return array
     */
    private function parsePhpFile($filePath)
    {
        $config = include $filePath;
        return is_array($config) ? $config : array();
    }
    
    /**
     * Parse JSON configuration file
     * @param string $filePath - File path
     * @return array
     * @throws Exception if JSON is invalid
     */
    private function parseJsonFile($filePath)
    {
        $content = file_get_contents($filePath);
        $config = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in {$filePath}: " . json_last_error_msg());
        }
        
        return is_array($config) ? $config : array();
    }
    
    /**
     * Parse INI configuration file
     * @param string $filePath - File path
     * @return array
     */
    private function parseIniFile($filePath)
    {
        $config = parse_ini_file($filePath, true);
        return is_array($config) ? $config : array();
    }
    
    /**
     * Parse YAML configuration file (requires yaml extension)
     * @param string $filePath - File path
     * @return array
     * @throws Exception if YAML extension not available
     */
    private function parseYamlFile($filePath)
    {
        if (!function_exists('yaml_parse_file')) {
            throw new Exception("YAML extension not available");
        }
        
        $config = yaml_parse_file($filePath);
        return is_array($config) ? $config : array();
    }
    
    /**
     * Save configuration as PHP file
     * @param string $filePath - File path
     * @param array $data - Data to save
     * @return bool
     */
    private function savePhpFile($filePath, $data)
    {
        $content = "<?php\nreturn " . var_export($data, true) . ";\n";
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Save configuration as JSON file
     * @param string $filePath - File path
     * @param array $data - Data to save
     * @return bool
     */
    private function saveJsonFile($filePath, $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Save configuration as INI file
     * @param string $filePath - File path
     * @param array $data - Data to save
     * @return bool
     */
    private function saveIniFile($filePath, $data)
    {
        $content = '';
        foreach ($data as $section => $values) {
            if (is_array($values)) {
                $content .= "[{$section}]\n";
                foreach ($values as $key => $value) {
                    $content .= "{$key} = " . (is_string($value) ? "\"{$value}\"" : $value) . "\n";
                }
                $content .= "\n";
            } else {
                $content .= "{$section} = " . (is_string($values) ? "\"{$values}\"" : $values) . "\n";
            }
        }
        
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Clear cache for specific key and related keys
     * @param string $key - Key to clear cache for
     */
    private function clearCacheForKey($key)
    {
        // Clear exact match
        unset($this->cached[$key]);
        
        // Clear related nested keys
        foreach (array_keys($this->cached) as $cachedKey) {
            if (strpos($cachedKey, $key . '.') === 0) {
                unset($this->cached[$cachedKey]);
            }
        }
    }
}