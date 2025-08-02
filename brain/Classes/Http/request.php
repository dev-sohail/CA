<?php

class Request
{
    public array $get     = [];
    public array $post    = [];
    public array $request = [];
    public array $cookie  = [];
    public array $files   = [];
    public array $server  = [];

    protected ?object $registry = null;

    public function __construct(?object $registry = null)
    {
        $this->registry = $registry;

        $this->get     = $_GET;
        $this->post    = $_POST;
        $this->request = $_REQUEST;
        $this->cookie  = $_COOKIE;
        $this->files   = $_FILES;
        $this->server  = $_SERVER;

        $this->forceHttpsIfEnabled();
    }

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function uri(): string
    {
        return strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    }

    public static function queryString(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    public static function query(): array
    {
        return $_GET;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST, self::input());
    }

    public static function raw(): string
    {
        return file_get_contents('php://input');
    }

    public static function input(): array
    {
        $contentType = self::header('Content-Type');
        $raw = self::raw();

        if (str_contains($contentType, 'application/json')) {
            return json_decode($raw, true) ?? [];
        }

        parse_str($raw, $parsed);
        return $parsed;
    }

    public static function headers(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[ucwords($header, '-')] = $value;
            }
        }
        return $headers;
    }

    public static function header(string $name, mixed $default = null): mixed
    {
        $headers = self::headers();
        return $headers[$name] ?? $default;
    }

    public static function isAjax(): bool
    {
        return strtolower(self::header('X-Requested-With')) === 'xmlhttprequest';
    }

    public static function ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function files(): array
    {
        return $_FILES;
    }

    public static function file(string $key): mixed
    {
        return $_FILES[$key] ?? null;
    }

    public static function is(string $method): bool
    {
        return strtoupper(self::method()) === strtoupper($method);
    }

    protected function forceHttpsIfEnabled(): void
    {
        if (!$this->registry) return;

        $config = $this->registry->get('config');
        if (!$config || !$config->get('force_https')) return;

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                    $_SERVER['SERVER_PORT'] == 443;

        if (!$isSecure) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $uri  = $_SERVER['REQUEST_URI'] ?? '/';
            header("Location: https://{$host}{$uri}", true, 301);
            exit;
        }
    }
}
