<?php

/**
 * Enhanced Response Class
 * Handles HTTP responses with improved functionality and security
 */
class Response
{
    private $headers = array();
    private $statusCode = 200;
    private $compressionLevel = 0;
    private $output = '';
    private $contentType = 'text/html';
    private $charset = 'UTF-8';

    // HTTP Status Code constants
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_NO_CONTENT = 204;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_SERVICE_UNAVAILABLE = 503;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Set default headers
        $this->addHeader('X-Powered-By: Custom PHP Framework');
        $this->addHeader('X-Content-Type-Options: nosniff');
        $this->addHeader('X-Frame-Options: SAMEORIGIN');
        $this->addHeader('X-XSS-Protection: 1; mode=block');
    }

    /**
     * Add a header to the response
     * @param string $header - Header string
     * @param bool $replace - Whether to replace existing header
     */
    public function addHeader($header, $replace = true)
    {
        if (!$replace) {
            $this->headers[] = $header;
        } else {
            // Extract header name for replacement logic
            $headerParts = explode(':', $header, 2);
            $headerName = trim($headerParts[0]);

            // Remove existing header with same name
            $this->headers = array_filter($this->headers, function ($existingHeader) use ($headerName) {
                $existingParts = explode(':', $existingHeader, 2);
                return strcasecmp(trim($existingParts[0]), $headerName) !== 0;
            });

            $this->headers[] = $header;
        }
    }

    /**
     * Set HTTP status code
     * @param int $code - HTTP status code
     */
    public function setStatusCode($code)
    {
        $this->statusCode = (int)$code;
        return $this;
    }

    /**
     * Get current status code
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set content type
     * @param string $contentType - Content type
     * @param string $charset - Character set
     */
    public function setContentType($contentType, $charset = null)
    {
        $this->contentType = $contentType;
        if ($charset !== null) {
            $this->charset = $charset;
        }
        return $this;
    }

    /**
     * Redirect to a URL
     * @param string $url - URL to redirect to
     * @param int $statusCode - HTTP status code for redirect
     * @param bool $exit - Whether to exit after redirect
     */
    public function redirect($url, $statusCode = self::HTTP_FOUND, $exit = true)
    {
        // Validate URL to prevent header injection
        $url = filter_var($url, FILTER_SANITIZE_URL);

        $this->setStatusCode($statusCode);
        $this->addHeader('Location: ' . $url);

        if (!headers_sent()) {
            $this->sendHeaders();
        }

        if ($exit) {
            exit;
        }
    }

    /**
     * Set compression level
     * @param int $level - Compression level (0-9)
     */
    public function setCompression($level)
    {
        $this->compressionLevel = max(0, min(9, (int)$level));
        return $this;
    }

    /**
     * Set output content
     * @param string $output - Content to output
     */
    public function setOutput($output)
    {
        $this->output = (string)$output;
        return $this;
    }

    /**
     * Append content to output
     * @param string $content - Content to append
     */
    public function appendOutput($content)
    {
        $this->output .= (string)$content;
        return $this;
    }

    /**
     * Get current output
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Load and render a template/view file
     * @param string $templatePath - Path to template file
     * @param array $data - Data to pass to template
     * @return string - Rendered template content
     * @throws Exception if template file not found
     */
    public function view($templatePath, $data = array())
    {
        if (!file_exists($templatePath)) {
            throw new Exception('Template file not found: ' . $templatePath);
        }

        if (!is_readable($templatePath)) {
            throw new Exception('Template file not readable: ' . $templatePath);
        }

        // Extract data array to variables
        if (is_array($data) && !empty($data)) {
            extract($data, EXTR_SKIP); // EXTR_SKIP prevents overwriting existing variables
        }

        ob_start();
        try {
            include $templatePath;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw new Exception('Error rendering template: ' . $e->getMessage());
        }

        // $this->response->setOutput(
        //     $this->response->view(MODULES_DIR . 'blog/view/post.php', $data)
        // );
    }

    /**
     * Render view and set as output
     * @param string $templatePath - Path to template file
     * @param array $data - Data to pass to template
     */
    public function render($templatePath, $data = array())
    {
        $this->setOutput($this->view($templatePath, $data));
        return $this;
    }

    /**
     * Compress data using gzip if supported
     * @param string $data - Data to compress
     * @param int $level - Compression level
     * @return string - Compressed or original data
     */
    private function compress($data, $level = 0)
    {
        // Check if compression is possible and beneficial
        if (strlen($data) < 1024) { // Don't compress small content
            return $data;
        }

        if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
            return $data;
        }

        if (headers_sent() || connection_status() !== CONNECTION_NORMAL) {
            return $data;
        }

        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        $encoding = null;

        if (strpos($acceptEncoding, 'gzip') !== false) {
            $encoding = 'gzip';
        } elseif (strpos($acceptEncoding, 'deflate') !== false) {
            $encoding = 'deflate';
        }

        if (!$encoding) {
            return $data;
        }

        $this->addHeader('Content-Encoding: ' . $encoding);
        $this->addHeader('Vary: Accept-Encoding');

        return ($encoding === 'gzip') ? gzencode($data, $level) : gzcompress($data, $level);
    }

    /**
     * Send HTTP headers
     */
    private function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        // Send status code
        http_response_code($this->statusCode);

        // Send content type header
        $contentTypeHeader = 'Content-Type: ' . $this->contentType;
        if ($this->charset && strpos($this->contentType, 'charset=') === false) {
            $contentTypeHeader .= '; charset=' . $this->charset;
        }
        header($contentTypeHeader);

        // Send custom headers
        foreach ($this->headers as $header) {
            header($header, true);
        }
    }

    /**
     * Output the response
     */
    public function output()
    {
        if (empty($this->output)) {
            return;
        }

        $output = $this->output;

        // Apply compression if enabled
        if ($this->compressionLevel > 0) {
            $output = $this->compress($output, $this->compressionLevel);
        }

        // Set content length header
        if (!headers_sent()) {
            $this->addHeader('Content-Length: ' . strlen($output));
            $this->sendHeaders();
        }

        echo $output;
    }

    /**
     * Send JSON response
     * @param mixed $data - Data to encode as JSON
     * @param int $statusCode - HTTP status code
     * @param int $options - JSON encode options
     */
    public function json($data = array(), $statusCode = self::HTTP_OK, $options = 0)
    {
        $this->setStatusCode($statusCode);
        $this->setContentType('application/json');

        // Add CORS headers (customize as needed)
        $this->addHeader('Access-Control-Allow-Origin: *');
        $this->addHeader('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        $this->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization');

        $jsonOutput = json_encode($data, $options | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->setStatusCode(self::HTTP_INTERNAL_SERVER_ERROR);
            $jsonOutput = json_encode([
                'error' => 'JSON encoding failed',
                'message' => json_last_error_msg()
            ]);
        }

        $this->setOutput($jsonOutput);
        $this->output();
        exit;
    }

    /**
     * Send XML response
     * @param string $data - XML data
     * @param int $statusCode - HTTP status code
     */
    public function xml($data, $statusCode = self::HTTP_OK)
    {
        $this->setStatusCode($statusCode);
        $this->setContentType('application/xml');
        $this->setOutput($data);
        $this->output();
        exit;
    }

    /**
     * Send plain text response
     * @param string $data - Text data
     * @param int $statusCode - HTTP status code
     */
    public function text($data, $statusCode = self::HTTP_OK)
    {
        $this->setStatusCode($statusCode);
        $this->setContentType('text/plain');
        $this->setOutput($data);
        $this->output();
        exit;
    }

    /**
     * Send file download response
     * @param string $filePath - Path to file
     * @param string $downloadName - Name for downloaded file
     * @param bool $deleteAfter - Delete file after sending
     */
    public function download($filePath, $downloadName = null, $deleteAfter = false)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->setStatusCode(self::HTTP_NOT_FOUND);
            $this->setOutput('File not found');
            $this->output();
            return;
        }

        $fileName = $downloadName ?: basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->setContentType($mimeType);
        $this->addHeader('Content-Disposition: attachment; filename="' . $fileName . '"');
        $this->addHeader('Content-Length: ' . $fileSize);
        $this->addHeader('Cache-Control: must-revalidate');
        $this->addHeader('Pragma: public');

        $this->sendHeaders();

        readfile($filePath);

        if ($deleteAfter && is_writable($filePath)) {
            unlink($filePath);
        }

        exit;
    }

    /**
     * Send error response
     * @param int $statusCode - HTTP status code
     * @param string $message - Error message
     * @param bool $asJson - Send as JSON response
     */
    public function error($statusCode = self::HTTP_INTERNAL_SERVER_ERROR, $message = 'An error occurred', $asJson = false)
    {
        $this->setStatusCode($statusCode);

        if ($asJson) {
            $this->json([
                'error' => true,
                'status' => $statusCode,
                'message' => $message
            ], $statusCode);
        } else {
            $this->setOutput($message);
            $this->output();
        }

        exit;
    }

    /**
     * Check if headers have been sent
     * @return bool
     */
    public function headersSent()
    {
        return headers_sent();
    }

    /**
     * Clear all output
     */
    public function clear()
    {
        $this->output = '';
        return $this;
    }

    /**
     * Clear all headers except security headers
     */
    public function clearHeaders()
    {
        $securityHeaders = array_filter($this->headers, function ($header) {
            return strpos($header, 'X-') === 0;
        });

        $this->headers = $securityHeaders;
        return $this;
    }
}
