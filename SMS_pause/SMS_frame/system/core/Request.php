<?php
/**
 * Request Class
 * 
 * Handles HTTP request data and methods
 */

namespace System\Core;

class Request
{
    /**
     * Get the request method
     * 
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the request URL
     * 
     * @return string
     */
    public function getUrl()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get POST data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }

    /**
     * Get all request data (POST + GET)
     * 
     * @return array
     */
    public function getAll()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Check if request is POST
     * 
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Check if request is GET
     * 
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Get request headers
     * 
     * @param string $key
     * @return string|null
     */
    public function getHeader($key)
    {
        $headers = getallheaders();
        return $headers[$key] ?? null;
    }

    /**
     * Get uploaded files
     * 
     * @return array
     */
    public function getFiles()
    {
        return $_FILES;
    }

    /**
     * Get session data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSession($key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION;
        }
        
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session data
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Remove session data
     * 
     * @param string $key
     */
    public function removeSession($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session
     */
    public function destroySession()
    {
        session_destroy();
    }
} 