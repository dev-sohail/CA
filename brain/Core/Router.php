<?php

/**
 * Simple and Clean Router Class
 * Handles routing for a PHP MVC framework with easy-to-understand variable names
 */
class Router
{
    // Core dependencies
    private $app;
    private $db;
    private $config;
    private $cache;
    private $logger;

    // Request information
    private string $url = '';
    private string $originalUrl = '';
    private array $params = [];
    
    // Route information
    public string $controllerFile = '';
    public string $controllerName = '';
    public string $actionName = '';
    public string $moduleName = '';
    public string $areaName = ''; // admin, api, app
    
    // Settings
    private int $page = 1;
    private string $language = 'en';
    private array $languages = ['en', 'ar', 'fr', 'es'];
    private string $apiVersion = 'v1';
    private bool $isApi = false;
    private bool $isAdmin = false;
    
    // Routes and middleware
    private array $routes = [];
    private array $middleware = [];
    private $controller;

    /**
     * Constructor - Initialize the router
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app->get('db');
        $this->config = $app->get('config');
        $this->cache = $app->get('cache');
        $this->logger = $app->get('logger');
        
        $this->loadRoutes();
        $this->setupDefaults();
    }

    /**
     * Main method to handle routing
     */
    public function run()
    {
        try {
            $this->parseUrl();
            $this->findRoute();
            $this->runMiddleware();
            $this->loadController();
            $this->callAction();
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}

protected class chunk1 extends Router
{
    /**
     * Parse the incoming URL
     */
    private function parseUrl()
    {
        // Get the URL from request
        $this->originalUrl = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        $this->url = strtok($this->originalUrl, '?');
        
        // Remove leading and trailing slashes
        $this->url = trim($this->url, '/');
        
        // Extract language if present
        $this->extractLanguage();
        
        // Extract pagination
        $this->extractPagination();
        
        // Check if API request
        $this->checkApiRequest();
        
        // Check if admin request
        $this->checkAdminRequest();
        
        // Store in app for global access
        $this->app->set('currentUrl', $this->url);
        $this->app->set('currentLanguage', $this->language);
        $this->app->set('currentPage', $this->page);
    }

    /**
     * Extract language from URL
     */
    private function extractLanguage()
    {
        $parts = explode('/', $this->url);
        $firstPart = $parts[0] ?? '';
        
        if (in_array($firstPart, $this->languages)) {
            $this->language = $firstPart;
            // Remove language from URL
            array_shift($parts);
            $this->url = implode('/', $parts);
        }
    }

    /**
     * Extract pagination from URL (e.g., /products/page/2)
     */
    private function extractPagination()
    {
        if (preg_match('/\/page\/(\d+)/', $this->url, $matches)) {
            $this->page = (int)$matches[1];
            $this->url = str_replace('/page/' . $matches[1], '', $this->url);
        }
    }

    /**
     * Check if this is an API request
     */
    private function checkApiRequest()
    {
        if (strpos($this->url, 'api/') === 0) {
            $this->isApi = true;
            $this->areaName = 'api';
            
            // Check for API version
            if (preg_match('/^api\/(v\d+)\//', $this->url, $matches)) {
                $this->apiVersion = $matches[1];
                $this->url = preg_replace('/^api\/v\d+\//', '', $this->url);
            } else {
                $this->url = substr($this->url, 4); // Remove 'api/'
            }
        }
    }

    /**
     * Check if this is an admin request
     */
    private function checkAdminRequest()
    {
        if (strpos($this->url, 'admin/') === 0) {
            $this->isAdmin = true;
            $this->areaName = 'admin';
            $this->url = substr($this->url, 6); // Remove 'admin/'
        }
    }

    /**
     * Find the matching route
     */
    private function findRoute()
    {
        // Try custom routes first
        if ($this->matchCustomRoute()) {
            return;
        }
        
        // Try module routes
        if ($this->matchModuleRoute()) {
            return;
        }
        
        // Try SEO routes
        if ($this->matchSeoRoute()) {
            return;
        }
        
        // Default route matching
        $this->matchDefaultRoute();
    }

    /**
     * Match custom defined routes
     */
    private function matchCustomRoute()
    {
        foreach ($this->routes as $pattern => $route) {
            if (preg_match($pattern, $this->url, $matches)) {
                $this->controllerName = $route['controller'];
                $this->actionName = $route['action'];
                $this->params = array_slice($matches, 1);
                return true;
            }
        }
        return false;
    }

    /**
     * Match module-based routes
     */
    private function matchModuleRoute()
    {
        $parts = explode('/', $this->url);
        
        // Check if first part is a module
        $moduleName = $parts[0] ?? '';
        if ($this->moduleExists($moduleName)) {
            $this->moduleName = ucfirst($moduleName);
            $this->controllerName = $parts[1] ?? 'home';
            $this->actionName = $parts[2] ?? 'index';
            $this->params = array_slice($parts, 3);
            return true;
        }
        
        return false;
    }

    /**
     * Match SEO-friendly routes from database
     */
    private function matchSeoRoute()
    {
        if (empty($this->url)) {
            return false;
        }
        
        // Skip AJAX requests
        if ($this->isAjax()) {
            return false;
        }
        
        // Look up URL alias in database
        $route = $this->lookupSeoRoute($this->url);
        if ($route) {
            $this->controllerName = $route['controller'];
            $this->actionName = $route['action'];
            $this->params = $route['params'] ?? [];
            return true;
        }
        
        return false;
    }

    /**
     * Default route matching (controller/action/params)
     */
    private function matchDefaultRoute()
    {
        $parts = explode('/', $this->url);
        
        $this->controllerName = !empty($parts[0]) ? $parts[0] : 'home';
        $this->actionName = !empty($parts[1]) ? $parts[1] : 'index';
        $this->params = array_slice($parts, 2);
        
        // Replace dashes with underscores for action names
        $this->actionName = str_replace('-', '_', $this->actionName);
    }

    /**
     * Run middleware stack
     */
    private function runMiddleware()
    {
        foreach ($this->middleware as $middlewareClass) {
            $middleware = new $middlewareClass($this->app);
            if (!$middleware->handle()) {
                throw new Exception("Middleware {$middlewareClass} blocked the request");
            }
        }
    }

    /**
     * Load the controller file
     */
    private function loadController()
    {
        $this->controllerFile = $this->getControllerPath();
        
        if (!file_exists($this->controllerFile)) {
            $this->handleNotFound();
            return;
        }
        
        require_once $this->controllerFile;
        
        $className = $this->getControllerClass();
        if (!class_exists($className)) {
            throw new Exception("Controller class {$className} not found");
        }
        
        $this->controller = new $className($this->app);
    }

    /**
     * Call the controller action
     */
    private function callAction()
    {
        // Check if action exists
        if (!method_exists($this->controller, $this->actionName)) {
            $this->actionName = 'index';
        }
        
        // Log the execution
        $this->logger->info("Executing: {$this->controllerName}::{$this->actionName}");
        
        // Call the action with parameters
        call_user_func_array(
            [$this->controller, $this->actionName], 
            $this->params
        );
    }

    /**
     * Get the full path to controller file
     */
    private function getControllerPath()
    {
        $basePath = $this->getBasePath();
        $fileName = ucfirst($this->controllerName) . 'Controller.php';
        return $basePath . '/' . $fileName;
    }

    /**
     * Get the base path for controllers
     */
    private function getBasePath()
    {
        if ($this->isApi) {
            return 'modules/api/Controllers';
        }
        
        if ($this->isAdmin) {
            return 'modules/admin/Controllers';
        }
        
        if ($this->moduleName) {
            return "modules/app/{$this->moduleName}/Controllers";
        }
        
        return 'controllers';
    }

    /**
     * Get the controller class name
     */
    private function getControllerClass()
    {
        $prefix = '';
        if ($this->isApi) {
            $prefix = 'Api';
        } elseif ($this->isAdmin) {
            $prefix = 'Admin';
        }
        
        return $prefix . ucfirst($this->controllerName) . 'Controller';
    }

    /**
     * Check if a module exists
     */
    private function moduleExists($moduleName)
    {
        $area = $this->isAdmin ? 'admin' : 'app';
        $path = "modules/{$area}/" . ucfirst($moduleName);
        return is_dir($path);
    }

    /**
     * Look up SEO route in database
     */
    private function lookupSeoRoute($url)
    {
        $query = $this->db->query(
            "SELECT controller, action, params FROM seo_routes 
             WHERE url = '" . $this->db->escape($url) . "' 
             LIMIT 1"
        );
        
        if ($query->num_rows > 0) {
            $row = $query->row;
            return [
                'controller' => $row['controller'],
                'action' => $row['action'],
                'params' => json_decode($row['params'] ?? '[]', true)
            ];
        }
        
        return null;
    }

    /**
     * Check if request is AJAX
     */
    private function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Load routes from files
     */
    private function loadRoutes()
    {
        $routeFiles = [
            'config/routes.php',
            'modules/api/routes.php',
            'modules/admin/routes.php'
        ];
        
        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                $routes = require $file;
                if (is_array($routes)) {
                    $this->routes = array_merge($this->routes, $routes);
                }
            }
        }
    }

    /**
     * Setup default routes and settings
     */
    private function setupDefaults()
    {
        // Add common route patterns
        $this->addRoute('/^product\/(\d+)$/', [
            'controller' => 'product',
            'action' => 'view'
        ]);
        
        $this->addRoute('/^category\/([^\/]+)$/', [
            'controller' => 'category',
            'action' => 'index'
        ]);
        
        // Add API routes if needed
        if ($this->isApi) {
            $this->addRoute('/^users\/(\d+)$/', [
                'controller' => 'user',
                'action' => 'show'
            ]);
        }
    }

    /**
     * Add a route pattern
     */
    public function addRoute($pattern, $route)
    {
        $this->routes[$pattern] = $route;
    }

    /**
     * Add middleware
     */
    public function addMiddleware($middlewareClass)
    {
        $this->middleware[] = $middlewareClass;
    }

    /**
     * Handle 404 not found
     */
    private function handleNotFound()
    {
        if ($this->isApi) {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
        }
        
        $this->controllerFile = 'controllers/ErrorController.php';
        $this->controllerName = 'error';
        $this->actionName = 'notFound';
    }

    /**
     * Handle routing errors
     */
    private function handleError($e)
    {
        $this->logger->error('Router Error: ' . $e->getMessage());
        
        if ($this->isApi) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            exit;
        }
        
        // Show error page
        $this->handleNotFound();
    }

    /**
     * Get current route info
     */
    public function getCurrentRoute()
    {
        return [
            'controller' => $this->controllerName,
            'action' => $this->actionName,
            'module' => $this->moduleName,
            'area' => $this->areaName,
            'params' => $this->params,
            'language' => $this->language,
            'page' => $this->page,
            'is_api' => $this->isApi,
            'is_admin' => $this->isAdmin
        ];
    }

    /**
     * Generate URL
     */
    public function url($route, $params = [])
    {
        $url = '/' . $this->language . '/' . ltrim($route, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Get controller name
     */
    public function getController()
    {
        return $this->controllerName;
    }

    /**
     * Get action name  
     */
    public function getAction()
    {
        return $this->actionName;
    }

    /**
     * Get parameters
     */
    public function getParams()
    {
        return $this->params;
    }
}

protected class chunk2 extends Router
{

}
protected class chunk3 extends Router
{

}