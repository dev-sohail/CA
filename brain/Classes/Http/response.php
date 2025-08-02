<?php

class Response
{
    private static array $headers = [];
    
    /**
     * Send a 404 Not Found response and exit.
     */
    public static function send404(string $message = '404 Not Found'): void
    {
        http_response_code(404);
        self::sendContent($message);
    }

    /**
     * Send a 500 Internal Server Error response and exit.
     */
    public static function send500(string $message = '500 Internal Server Error'): void
    {
        http_response_code(500);
        self::sendContent($message);
    }

    /**
     * Send a 403 Forbidden response and exit.
     */
    public static function send403(string $message = '403 Forbidden'): void
    {
        http_response_code(403);
        self::sendContent($message);
    }

    /**
     * Send JSON response with given data.
     *
     * @param mixed $data
     * @param int $statusCode HTTP status code, default 200 OK
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send plain text or HTML content and exit.
     */
    public static function sendContent(string $content): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Sets the HTTP status code (e.g. 200, 404).
     *
     * @param int $code
     */
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

      /**
     * Adds a custom HTTP header to the response.
     *
     * @param string $header
     */
    public function addHeader(string $header): void
    {
        $this->headers[] = $header;
    }

    /**
     * Redirect to another URL and exit.
     *
     * @param string $url
     * @param int $statusCode HTTP status code for redirect, default 302 Found
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }
}
