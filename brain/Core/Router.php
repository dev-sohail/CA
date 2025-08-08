<?php

class Router
{
    protected static $routes = [];
    protected static $notFound;

    /**
     * Register a GET route
     */
    public static function get($path, $handler)
    {
        self::addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public static function post($path, $handler)
    {
        self::addRoute('POST', $path, $handler);
    }

    /**
     * Register a route for any HTTP method
     */
    public static function any($path, $handler)
    {
        self::addRoute('ANY', $path, $handler);
    }

    /**
     * Set a 404 handler
     */
    public static function setNotFound($handler)
    {
        self::$notFound = $handler;
    }

    /**
     * Add a route to the registry
     */
    protected static function addRoute($method, $path, $handler)
    {
        self::$routes[] = [
            'method'  => strtoupper($method),
            'path'    => rtrim($path, '/'),
            'handler' => $handler
        ];
    }

    /**
     * Dispatch the request
     */
    public static function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = rtrim($uri, '/') ?: '/';

        foreach (self::$routes as $route) {
            if (
                ($route['method'] === $method || $route['method'] === 'ANY') &&
                $route['path'] === $uri
            ) {
                return call_user_func($route['handler']);
            }
        }

        if (self::$notFound) {
            http_response_code(404);
            return call_user_func(self::$notFound);
        }

        // Default fallback
        http_response_code(404);
        echo "404 Not Found";
    }
}
