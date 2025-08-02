<?php

class Routes
{
    private $registry;
    private $response;
    
    private static array $routes = [];
    private static ?string $currentRole = null;
    private static ?string $currentModule = null;
    private static string $basePath;
    private static string $defaultRole = 'App';
    private static string $defaultModule = 'Main';

    public static function init(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function loadRoutes(?string $role = null, ?string $module = null): void
    {
        if (!$role && !$module) {
            $roles = self::scanDirs(self::$basePath);
            foreach ($roles as $roleDir) {
                $rolePath = self::$basePath . '/' . $roleDir;
                $modules = self::scanDirs($rolePath);
                foreach ($modules as $moduleDir) {
                    self::loadModuleRoutes($roleDir, $moduleDir);
                }
            }
        } elseif ($role && !$module) {
            $rolePath = self::$basePath . '/' . $role;
            $modules = self::scanDirs($rolePath);
            foreach ($modules as $moduleDir) {
                self::loadModuleRoutes($role, $moduleDir);
            }
        } elseif ($role && $module) {
            self::loadModuleRoutes($role, $module);
        }
    }

    private static function scanDirs(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }
        return array_filter(scandir($path), fn($item) => $item !== '.' && $item !== '..' && is_dir("$path/$item"));
    }

    private static function loadModuleRoutes(string $role, string $module): void
    {
        $routesFile = self::$basePath . "/$role/$module/routes.php";
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    public static function get(string $uri, $callback): void
    {
        self::addRoute('GET', $uri, $callback);
    }

    public static function post(string $uri, $callback): void
    {
        self::addRoute('POST', $uri, $callback);
    }

    public static function put(string $uri, $callback): void
    {
        self::addRoute('PUT', $uri, $callback);
    }

    public static function delete(string $uri, $callback): void
    {
        self::addRoute('DELETE', $uri, $callback);
    }

    private static function addRoute(string $method, string $uri, $callback): void
    {
        $uri = trim($uri, '/');
        self::$routes[$method][$uri] = $callback;
    }

    public static function dispatch(): void
    {
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $method = $_SERVER['REQUEST_METHOD'];

        if (isset(self::$routes[$method][$requestUri])) {
            self::handleCallback(self::$routes[$method][$requestUri]);
            return;
        }

        // Replace $Response::send404 with Response::send404 or fully qualified
        Response::send404("Route [$requestUri] not found");
    }

    private static function handleCallback($callback): void
    {
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        if (is_string($callback)) {
            self::invokeController($callback);
            return;
        }

        Response::send500("Invalid route callback.");
    }

    private static function invokeController(string $callback): void
    {
        self::detectRoleModule();

        $role = self::$currentRole ?? self::$defaultRole;
        $module = self::$currentModule ?? self::$defaultModule;

        $parts = explode('@', $callback);
        if (count($parts) !== 2) {
            Response::send500("Invalid controller callback format.");
            return;
        }

        [$controllerName, $methodName] = $parts;
        $controllerClass = "\\$role\\Controllers\\$module\\$controllerName";

        if (!class_exists($controllerClass)) {
            Response::send404("Controller [$controllerClass] not found.");
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            Response::send404("Method [$methodName] not found in controller [$controllerClass].");
            return;
        }

        $controller->$methodName();
    }


    private static function detectRoleModule(): void
    {
        if (self::$currentRole !== null && self::$currentModule !== null) {
            return;
        }

        $uriSegments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

        self::$currentRole = ucfirst(strtolower($uriSegments[0] ?? self::$defaultRole));
        self::$currentModule = ucfirst(strtolower($uriSegments[1] ?? self::$defaultModule));
    }
}
