<?php
/**
 * Response Class
 * 
 * Handles HTTP responses and status codes
 */

namespace System\Core;

class Response
{
    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Set the response status code
     * 
     * @param int $code
     * @return $this
     */
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        http_response_code($code);
        return $this;
    }

    /**
     * Get the response status code
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set a response header
     * 
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        header("$name: $value");
        return $this;
    }

    /**
     * Get response headers
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Redirect to a URL
     * 
     * @param string $url
     * @param int $statusCode
     */
    public function redirect($url, $statusCode = 302)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        exit();
    }

    /**
     * Send JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    public function json($data, $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Send plain text response
     * 
     * @param string $text
     * @param int $statusCode
     */
    public function text($text, $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/plain');
        echo $text;
        exit();
    }

    /**
     * Send HTML response
     * 
     * @param string $html
     * @param int $statusCode
     */
    public function html($html, $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/html');
        echo $html;
        exit();
    }
} 