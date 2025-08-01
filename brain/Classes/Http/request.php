 
<?php
/**
 * Enhanced Request Class
 * Handles HTTP request data with improved security and functionality
 */
class Request {
    public $get = array();
    public $post = array();
    public $request = array();
    public $cookie = array();
    public $files = array();
    public $server = array();
    
    /**
     * Constructor - Initialize and clean all superglobal data
     */
    public function __construct() {
        $this->get = $this->clean($_GET);
        $this->post = $this->clean($_POST);
        $this->request = $this->clean($_REQUEST);
        $this->cookie = $this->clean($_COOKIE);
        $this->files = $this->cleanFiles($_FILES);
        $this->server = $this->clean($_SERVER);
    }
    
    /**
     * Recursively clean data by applying HTML entity encoding
     * @param mixed $data - Data to be cleaned
     * @return mixed - Cleaned data
     */
    public function clean($data) {
        if (is_array($data)) {
            $cleaned = array();
            foreach ($data as $key => $value) {
                $cleanKey = $this->clean($key);
                $cleaned[$cleanKey] = $this->clean($value);
            }
            return $cleaned;
        } else {
            // Handle null values
            if ($data === null) {
                return null;
            }
            // Convert to string and apply HTML encoding
            return htmlspecialchars((string)$data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Clean file upload data (files array has special structure)
     * @param array $files - $_FILES superglobal
     * @return array - Cleaned files data
     */
    private function cleanFiles($files) {
        if (!is_array($files)) {
            return array();
        }
        
        $cleaned = array();
        foreach ($files as $key => $file) {
            $cleanKey = $this->clean($key);
            if (is_array($file)) {
                $cleaned[$cleanKey] = array();
                foreach ($file as $prop => $value) {
                    // Don't clean 'tmp_name' and 'size' as they need to remain functional
                    if (in_array($prop, ['tmp_name', 'size', 'error'])) {
                        $cleaned[$cleanKey][$prop] = $value;
                    } else {
                        $cleaned[$cleanKey][$prop] = $this->clean($value);
                    }
                }
            }
        }
        return $cleaned;
    }
    
    /**
     * Get the real IP address of the client
     * Checks various headers that might contain the real IP
     * @return string - Client IP address
     */
    public function getRealIpAddr() {
        // Array of headers to check in order of preference
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Shared internet
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated list (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR even if it might be private/reserved
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get a GET parameter with optional default value
     * @param string $key - Parameter name
     * @param mixed $default - Default value if not found
     * @return mixed - Parameter value or default
     */
    public function get($key, $default = null) {
        return isset($this->get[$key]) ? $this->get[$key] : $default;
    }
    
    /**
     * Get a POST parameter with optional default value
     * @param string $key - Parameter name
     * @param mixed $default - Default value if not found
     * @return mixed - Parameter value or default
     */
    public function post($key, $default = null) {
        return isset($this->post[$key]) ? $this->post[$key] : $default;
    }
    
    /**
     * Get a COOKIE value with optional default value
     * @param string $key - Cookie name
     * @param mixed $default - Default value if not found
     * @return mixed - Cookie value or default
     */
    public function cookie($key, $default = null) {
        return isset($this->cookie[$key]) ? $this->cookie[$key] : $default;
    }
    
    /**
     * Get a SERVER variable with optional default value
     * @param string $key - Server variable name
     * @param mixed $default - Default value if not found
     * @return mixed - Server variable value or default
     */
    public function server($key, $default = null) {
        return isset($this->server[$key]) ? $this->server[$key] : $default;
    }
    
    /**
     * Check if request method is GET
     * @return bool
     */
    public function isGet() {
        return $this->server('REQUEST_METHOD') === 'GET';
    }
    
    /**
     * Check if request method is POST
     * @return bool
     */
    public function isPost() {
        return $this->server('REQUEST_METHOD') === 'POST';
    }
    
    /**
     * Check if request is AJAX
     * @return bool
     */
    public function isAjax() {
        return strtolower($this->server('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest';
    }
    
    /**
     * Get the HTTP method
     * @return string - HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod() {
        return $this->server('REQUEST_METHOD', 'GET');
    }
    
    /**
     * Get the request URI
     * @return string - Request URI
     */
    public function getUri() {
        return $this->server('REQUEST_URI', '/');
    }
    
    /**
     * Get user agent string
     * @return string - User agent
     */
    public function getUserAgent() {
        return $this->server('HTTP_USER_AGENT', '');
    }
    
    /**
     * Check if the request is using HTTPS
     * @return bool
     */
    public function isSecure() {
        return (
            (!empty($this->server('HTTPS')) && $this->server('HTTPS') !== 'off') ||
            $this->server('SERVER_PORT') == 443 ||
            $this->server('HTTP_X_FORWARDED_PROTO') === 'https'
        );
    }
}