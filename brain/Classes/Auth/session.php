<?php
/**
 * Enhanced Session Management Class
 * Provides secure session handling with CSRF protection, flash messages, and advanced features
 */
class Session
{
    public $data = array();
    private $started = false;
    private $flashKey = '__flash__';
    private $csrfKey = '__csrf_token__';
    private $fingerprintKey = '__fingerprint__';
    private static $instance = null;
    
    // Session configuration
    private $config = array(
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'regenerate_id_interval' => 300, // 5 minutes
        'gc_maxlifetime' => 1440, // 24 minutes
        'fingerprint_check' => true,
        'ip_check' => false,
        'user_agent_check' => true
    );
    
    /**
     * Constructor - Initialize session with security settings
     * @param array $config - Optional configuration overrides
     */
    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
        $this->start();
    }
    
    /**
     * Get singleton instance
     * @param array $config - Optional configuration
     * @return Session
     */
    public static function getInstance($config = array())
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Start session with security configurations
     * @return bool - Success status
     */
    public function start()
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Configure session settings
        $this->configureSession();
        
        // Start session
        if (!session_start()) {
            throw new Exception('Failed to start session');
        }
        
        $this->started = true;
        $this->data =& $_SESSION;
        
        // Security checks
        $this->performSecurityChecks();
        
        // Initialize flash messages array if not exists
        if (!isset($this->data[$this->flashKey])) {
            $this->data[$this->flashKey] = array();
        }
        
        // Clean up old flash messages
        $this->cleanupFlashMessages();
        
        return true;
    }
    
    /**
     * Configure session security settings
     */
    private function configureSession()
    {
        // Basic session settings
        ini_set('session.use_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', $this->config['cookie_httponly'] ? '1' : '0');
        ini_set('session.gc_maxlifetime', $this->config['gc_maxlifetime']);
        
        // Enhanced security settings
        if ($this->config['use_strict_mode']) {
            ini_set('session.use_strict_mode', '1');
        }
        
        // Set cookie parameters
        $secure = $this->config['cookie_secure'] ?: $this->isHttps();
        
        if (PHP_VERSION_ID >= 70300) {
            // PHP 7.3+ supports SameSite in session_set_cookie_params
            session_set_cookie_params(array(
                'lifetime' => $this->config['cookie_lifetime'],
                'path' => $this->config['cookie_path'],
                'domain' => $this->config['cookie_domain'],
                'secure' => $secure,
                'httponly' => $this->config['cookie_httponly'],
                'samesite' => $this->config['cookie_samesite']
            ));
        } else {
            session_set_cookie_params(
                $this->config['cookie_lifetime'],
                $this->config['cookie_path'],
                $this->config['cookie_domain'],
                $secure,
                $this->config['cookie_httponly']
            );
        }
    }
    
    /**
     * Perform security checks on session
     */
    private function performSecurityChecks()
    {
        $currentFingerprint = $this->generateFingerprint();
        
        // Check if this is a new session
        if (!isset($this->data[$this->fingerprintKey])) {
            $this->data[$this->fingerprintKey] = $currentFingerprint;
            $this->data['__created_at__'] = time();
            $this->data['__last_regenerated__'] = time();
            return;
        }
        
        // Validate session fingerprint
        if ($this->config['fingerprint_check'] && 
            $this->data[$this->fingerprintKey] !== $currentFingerprint) {
            $this->destroy();
            throw new Exception('Session fingerprint mismatch - possible session hijacking');
        }
        
        // Auto-regenerate session ID periodically
        $lastRegenerated = $this->data['__last_regenerated__'] ?? 0;
        if (time() - $lastRegenerated > $this->config['regenerate_id_interval']) {
            $this->regenerateId();
        }
    }
    
    /**
     * Generate session fingerprint for security
     * @return string
     */
    private function generateFingerprint()
    {
        $fingerprint = '';
        
        if ($this->config['ip_check']) {
            $fingerprint .= $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        if ($this->config['user_agent_check']) {
            $fingerprint .= $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        return hash('sha256', $fingerprint);
    }
    
    /**
     * Check if connection is HTTPS
     * @return bool
     */
    private function isHttps()
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
    
    /**
     * Get session ID
     * @return string
     */
    public function getId()
    {
        return session_id();
    }
    
    /**
     * Get session value with dot notation support
     * @param string $key - Session key (supports dot notation)
     * @param mixed $default - Default value if key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->getNestedValue($this->data, $key, $default);
    }
    
    /**
     * Set session value with dot notation support
     * @param string $key - Session key (supports dot notation)
     * @param mixed $value - Value to set
     * @return Session - For method chaining
     */
    public function set($key, $value)
    {
        $this->setNestedValue($this->data, $key, $value);
        return $this;
    }
    
    /**
     * Check if session key exists
     * @param string $key - Session key (supports dot notation)
     * @return bool
     */
    public function has($key)
    {
        return $this->getNestedValue($this->data, $key, '__SESSION_NOT_FOUND__') !== '__SESSION_NOT_FOUND__';
    }
    
    /**
     * Remove session key
     * @param string $key - Session key (supports dot notation)
     * @return Session - For method chaining
     */
    public function remove($key)
    {
        $this->unsetNestedValue($this->data, $key);
        return $this;
    }
    
    /**
     * Get all session data
     * @return array
     */
    public function all()
    {
        return array_diff_key($this->data, array_flip([
            $this->flashKey,
            $this->csrfKey,
            $this->fingerprintKey,
            '__created_at__',
            '__last_regenerated__'
        ]));
    }
    
    /**
     * Clear all session data except system keys
     * @return Session - For method chaining
     */
    public function clear()
    {
        $systemKeys = [
            $this->flashKey,
            $this->csrfKey,
            $this->fingerprintKey,
            '__created_at__',
            '__last_regenerated__'
        ];
        
        foreach ($this->data as $key => $value) {
            if (!in_array($key, $systemKeys)) {
                unset($this->data[$key]);
            }
        }
        
        return $this;
    }
    
    /**
     * Regenerate session ID
     * @param bool $deleteOldSession - Whether to delete old session file
     * @return Session - For method chaining
     */
    public function regenerateId($deleteOldSession = true)
    {
        if (session_regenerate_id($deleteOldSession)) {
            $this->data['__last_regenerated__'] = time();
            $this->data[$this->fingerprintKey] = $this->generateFingerprint();
        }
        return $this;
    }
    
    /**
     * Destroy session completely
     * @return bool - Success status
     */
    public function destroy()
    {
        if (!$this->started) {
            return true;
        }
        
        // Clear session data
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session
        $result = session_destroy();
        $this->started = false;
        
        return $result;
    }
    
    /**
     * Generate CSRF token
     * @return string - CSRF token
     */
    public function generateCsrfToken()
    {
        if (!isset($this->data[$this->csrfKey])) {
            $this->data[$this->csrfKey] = bin2hex(random_bytes(32));
        }
        return $this->data[$this->csrfKey];
    }
    
    /**
     * Get CSRF token
     * @return string|null - CSRF token or null if not generated
     */
    public function getCsrfToken()
    {
        return $this->data[$this->csrfKey] ?? null;
    }
    
    /**
     * Validate CSRF token
     * @param string $token - Token to validate
     * @return bool - Validation result
     */
    public function validateCsrfToken($token)
    {
        $sessionToken = $this->getCsrfToken();
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Set flash message
     * @param string $key - Flash message key
     * @param mixed $message - Message content
     * @return Session - For method chaining
     */
    public function flash($key, $message)
    {
        $this->data[$this->flashKey][$key] = array(
            'message' => $message,
            'timestamp' => time()
        );
        return $this;
    }
    
    /**
     * Get flash message(s)
     * @param string $key - Flash message key (null for all)
     * @return mixed - Flash message(s) or null
     */
    public function getFlash($key = null)
    {
        if ($key === null) {
            $messages = array();
            foreach ($this->data[$this->flashKey] as $flashKey => $data) {
                $messages[$flashKey] = $data['message'];
            }
            return $messages;
        }
        
        if (isset($this->data[$this->flashKey][$key])) {
            $message = $this->data[$this->flashKey][$key]['message'];
            unset($this->data[$this->flashKey][$key]);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Check if flash message exists
     * @param string $key - Flash message key
     * @return bool
     */
    public function hasFlash($key)
    {
        return isset($this->data[$this->flashKey][$key]);
    }
    
    /**
     * Clean up old flash messages (older than 1 hour)
     */
    private function cleanupFlashMessages()
    {
        $now = time();
        foreach ($this->data[$this->flashKey] as $key => $data) {
            if ($now - $data['timestamp'] > 3600) { // 1 hour
                unset($this->data[$this->flashKey][$key]);
            }
        }
    }
    
    /**
     * Get session metadata
     * @return array
     */
    public function getMetadata()
    {
        return array(
            'id' => $this->getId(),
            'created_at' => $this->data['__created_at__'] ?? null,
            'last_regenerated' => $this->data['__last_regenerated__'] ?? null,
            'lifetime' => time() - ($this->data['__created_at__'] ?? time()),
            'fingerprint' => $this->data[$this->fingerprintKey] ?? null,
            'csrf_token' => $this->getCsrfToken()
        );
    }
    
    /**
     * Check if session is expired
     * @param int $maxLifetime - Maximum lifetime in seconds
     * @return bool
     */
    public function isExpired($maxLifetime = null)
    {
        $maxLifetime = $maxLifetime ?: $this->config['gc_maxlifetime'];
        $createdAt = $this->data['__created_at__'] ?? time();
        
        return (time() - $createdAt) > $maxLifetime;
    }
    
    /**
     * Get session size in bytes
     * @return int
     */
    public function getSize()
    {
        return strlen(serialize($this->data));
    }
    
    /**
     * Save session data
     * @return Session - For method chaining
     */
    public function save()
    {
        session_write_close();
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
}