<?php

/**
 * Cache Class - Provides multiple caching mechanisms
 * 
 * Supports file-based, memory-based, and distributed caching
 * with TTL, tags, and cache invalidation features
 */
class Cache
{
    private $registry;
    private static $instance = null;
    private $driver = 'file';
    private $cachePath;
    private $defaultTTL = 3600; // 1 hour default
    private $memoryCache = [];
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];

    /**
     * Initialize cache system
     */
    public function __construct($config = [])
    {
        // Try to get Registry instance
        if (class_exists('Registry')) {
            $this->registry = Registry::getInstance();

            // Get config from registry if available
            if ($this->registry && $this->registry->has('config')) {
                $globalConfig = $this->registry->get('config');
                if (method_exists($globalConfig, 'get')) {
                    $config = array_merge($globalConfig->get('cache', []), $config);
                }
            }
        }

        $this->driver = $config['driver'] ?? $this->getConfig('cache.driver', 'file');
        $this->cachePath = $config['path'] ?? $this->getCachePath();
        $this->defaultTTL = $config['ttl'] ?? $this->getConfig('cache.ttl', 3600);

        // Ensure cache directory exists
        if ($this->cachePath && !file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Get singleton instance - integrates with Registry
     */
    public static function getInstance($config = [])
    {
        // Try to get from Registry first
        if (class_exists('Registry')) {
            $registry = Registry::getInstance();
            if ($registry && $registry->has('cache')) {
                return $registry->get('cache');
            }
        }

        // Create new instance if not in registry
        if (self::$instance === null) {
            self::$instance = new self($config);

            // Store in registry if available
            if (class_exists('Registry')) {
                $registry = Registry::getInstance();
                if ($registry) {
                    $registry->set('cache', self::$instance);
                }
            }
        }
        return self::$instance;
    }

    /**
     * Store data in cache
     */
    public function put($key, $value, $ttl = null, $tags = [])
    {
        $ttl = $ttl ?? $this->defaultTTL;
        $cacheKey = $this->generateKey($key);

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
            'tags' => $tags,
            'key' => $key
        ];

        $success = false;

        switch ($this->driver) {
            case 'memory':
                $this->memoryCache[$cacheKey] = $data;
                $success = true;
                break;

            case 'file':
            default:
                $success = $this->putFile($cacheKey, $data);
                break;
        }

        if ($success) {
            $this->stats['writes']++;

            // Store tag mapping for invalidation
            if (!empty($tags)) {
                $this->storeTags($cacheKey, $tags);
            }
        }

        return $success;
    }

    /**
     * Store data in cache (alias for put with simpler syntax)
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->put($key, $value, $ttl);
    }

    /**
     * Retrieve data from cache
     */
    public function get($key, $default = null)
    {
        $cacheKey = $this->generateKey($key);
        $data = null;

        switch ($this->driver) {
            case 'memory':
                $data = $this->memoryCache[$cacheKey] ?? null;
                break;

            case 'file':
            default:
                $data = $this->getFile($cacheKey);
                break;
        }

        if ($data === null || $this->isExpired($data)) {
            $this->stats['misses']++;

            // Clean up expired data
            if ($data !== null && $this->isExpired($data)) {
                $this->forget($key);
            }

            return $default;
        }

        $this->stats['hits']++;
        return $data['value'];
    }

    /**
     * Check if cache key exists and is not expired
     */
    public function has($key)
    {
        return $this->get($key, '__cache_miss__') !== '__cache_miss__';
    }

    /**
     * Remove item from cache
     */
    public function forget($key)
    {
        $cacheKey = $this->generateKey($key);
        $success = false;

        switch ($this->driver) {
            case 'memory':
                if (isset($this->memoryCache[$cacheKey])) {
                    unset($this->memoryCache[$cacheKey]);
                    $success = true;
                }
                break;

            case 'file':
            default:
                $filePath = $this->getFilePath($cacheKey);
                if (file_exists($filePath)) {
                    $success = unlink($filePath);
                }
                break;
        }

        if ($success) {
            $this->stats['deletes']++;
        }

        return $success;
    }

    /**
     * Get or store data in cache
     */
    public function remember($key, $callback, $ttl = null, $tags = [])
    {
        $value = $this->get($key, '__cache_miss__');

        if ($value !== '__cache_miss__') {
            return $value;
        }

        $value = is_callable($callback) ? $callback() : $callback;
        $this->put($key, $value, $ttl, $tags);

        return $value;
    }

    /**
     * Store data forever (very long TTL)
     */
    public function forever($key, $value, $tags = [])
    {
        return $this->put($key, $value, 315360000, $tags); // ~10 years
    }

    /**
     * Increment a cached value
     */
    public function increment($key, $value = 1)
    {
        $current = $this->get($key, 0);
        $new = (int)$current + $value;
        $this->put($key, $new);
        return $new;
    }

    /**
     * Decrement a cached value
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * Clear all cache
     */
    public function flush()
    {
        $success = false;

        switch ($this->driver) {
            case 'memory':
                $this->memoryCache = [];
                $success = true;
                break;

            case 'file':
            default:
                $success = $this->flushFiles();
                break;
        }

        if ($success) {
            $this->stats = ['hits' => 0, 'misses' => 0, 'writes' => 0, 'deletes' => 0];
        }

        return $success;
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTags($tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        $deleted = 0;
        foreach ($tags as $tag) {
            $keys = $this->getKeysByTag($tag);
            foreach ($keys as $key) {
                if ($this->forgetByGeneratedKey($key)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;

        return array_merge($this->stats, [
            'hit_rate' => $hitRate . '%',
            'total_requests' => $total,
            'driver' => $this->driver,
            'memory_usage' => $this->driver === 'memory' ? count($this->memoryCache) : 'N/A'
        ]);
    }

    /**
     * Clean expired cache entries
     */
    public function cleanup()
    {
        $cleaned = 0;

        if ($this->driver === 'file') {
            $files = glob($this->cachePath . '*.cache');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if ($content) {
                    $data = json_decode($content, true);
                    if ($data && $this->isExpired($data)) {
                        unlink($file);
                        $cleaned++;
                    }
                }
            }
        } elseif ($this->driver === 'memory') {
            foreach ($this->memoryCache as $key => $data) {
                if ($this->isExpired($data)) {
                    unset($this->memoryCache[$key]);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Get cache size information
     */
    public function getSize()
    {
        $size = 0;
        $count = 0;

        if ($this->driver === 'file') {
            $files = glob($this->cachePath . '*.cache');
            $count = count($files);
            foreach ($files as $file) {
                $size += filesize($file);
            }
        } elseif ($this->driver === 'memory') {
            $count = count($this->memoryCache);
            $size = strlen(serialize($this->memoryCache));
        }

        return [
            'size_bytes' => $size,
            'size_human' => $this->formatBytes($size),
            'item_count' => $count
        ];
    }

    // Private helper methods

    private function generateKey($key)
    {
        return 'cache_' . md5($key);
    }

    private function getFilePath($cacheKey)
    {
        return $this->cachePath . DIRECTORY_SEPARATOR . $cacheKey . '.cache';
    }

    private function putFile($cacheKey, $data)
    {
        if (!$this->cachePath) {
            return false;
        }

        $filePath = $this->getFilePath($cacheKey);
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($jsonData === false) {
            return false;
        }

        return file_put_contents($filePath, $jsonData, LOCK_EX) !== false;
    }

    private function getFile($cacheKey)
    {
        if (!$this->cachePath) {
            return null;
        }

        $filePath = $this->getFilePath($cacheKey);

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        return $data !== null ? $data : null;
    }

    private function isExpired($data)
    {
        return isset($data['expires_at']) && time() > $data['expires_at'];
    }

    private function flushFiles()
    {
        if (!$this->cachePath) {
            return false;
        }

        $files = glob($this->cachePath . DIRECTORY_SEPARATOR . '*.cache');
        $success = true;

        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }

        // Clean tag files too
        $tagPath = $this->cachePath . DIRECTORY_SEPARATOR . 'tags' . DIRECTORY_SEPARATOR;
        if (file_exists($tagPath)) {
            $tagFiles = glob($tagPath . '*.tag');
            foreach ($tagFiles as $file) {
                unlink($file);
            }
        }

        return $success;
    }

    private function storeTags($cacheKey, $tags)
    {
        if (!$this->cachePath) {
            return;
        }

        $tagPath = $this->cachePath . DIRECTORY_SEPARATOR . 'tags' . DIRECTORY_SEPARATOR;
        if (!file_exists($tagPath)) {
            mkdir($tagPath, 0755, true);
        }

        foreach ($tags as $tag) {
            $tagFile = $tagPath . md5($tag) . '.tag';
            $keys = [];

            if (file_exists($tagFile)) {
                $content = file_get_contents($tagFile);
                if ($content) {
                    $decodedKeys = json_decode($content, true);
                    $keys = is_array($decodedKeys) ? $decodedKeys : [];
                }
            }

            if (!in_array($cacheKey, $keys)) {
                $keys[] = $cacheKey;
                file_put_contents($tagFile, json_encode($keys), LOCK_EX);
            }
        }
    }

    private function getKeysByTag($tag)
    {
        if (!$this->cachePath) {
            return [];
        }

        $tagFile = $this->cachePath . DIRECTORY_SEPARATOR . 'tags' . DIRECTORY_SEPARATOR . md5($tag) . '.tag';

        if (!file_exists($tagFile)) {
            return [];
        }

        $content = file_get_contents($tagFile);
        if ($content) {
            $keys = json_decode($content, true);
            return is_array($keys) ? $keys : [];
        }

        return [];
    }

    private function forgetByGeneratedKey($cacheKey)
    {
        $success = false;

        switch ($this->driver) {
            case 'memory':
                if (isset($this->memoryCache[$cacheKey])) {
                    unset($this->memoryCache[$cacheKey]);
                    $success = true;
                }
                break;

            case 'file':
            default:
                $filePath = $this->getFilePath($cacheKey);
                if (file_exists($filePath)) {
                    $success = unlink($filePath);
                }
                break;
        }

        if ($success) {
            $this->stats['deletes']++;
        }

        return $success;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getConfig($key, $default = null)
    {
        // Fixed: Was calling $this->get() instead of Config::get()
        if (class_exists('Config') && method_exists('Config', 'get')) {
            return Config::get($key, $default);
        }

        // Alternative: Try registry-based config access
        if ($this->registry && $this->registry->has('config')) {
            $config = $this->registry->get('config');
            if (is_object($config) && method_exists($config, 'get')) {
                return $config->get($key, $default);
            }
        }

        return $default;
    }

    private function getCachePath()
    {
        // Try to get from Registry first
        if ($this->registry && $this->registry->has('paths.cache')) {
            return rtrim($this->registry->get('paths.cache'), DIRECTORY_SEPARATOR);
        }

        // Use DIR_CACHE constant if defined
        if (defined('DIR_CACHE')) {
            return rtrim(DIR_CACHE, DIRECTORY_SEPARATOR);
        }

        // Fallback to relative path in system temp directory
        $tempDir = sys_get_temp_dir();
        $cachePath = $tempDir . DIRECTORY_SEPARATOR . 'php_cache';

        // Create directory if it doesn't exist
        if (!file_exists($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        return $cachePath;
    }
}

/**
 * Cache Helper Functions
 */

if (!function_exists('cache')) {
    /**
     * Get cache instance or retrieve/store cache data
     */
    function cache($key = null, $value = null, $ttl = null)
    {
        $cache = Cache::getInstance();

        if ($key === null) {
            return $cache;
        }

        if ($value === null) {
            return $cache->get($key);
        }

        return $cache->put($key, $value, $ttl);
    }
}

if (!function_exists('cache_set')) {
    /**
     * Store data in cache (simpler syntax)
     */
    function cache_set($key, $value, $ttl = null)
    {
        return Cache::getInstance()->set($key, $value, $ttl);
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Get or store data in cache
     */
    function cache_remember($key, $callback, $ttl = null, $tags = [])
    {
        return Cache::getInstance()->remember($key, $callback, $ttl, $tags);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Remove item from cache
     */
    function cache_forget($key)
    {
        return Cache::getInstance()->forget($key);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * Clear all cache
     */
    function cache_flush()
    {
        return Cache::getInstance()->flush();
    }
}
