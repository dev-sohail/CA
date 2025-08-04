<?php

/**
 * Simple and Clean Router Class
 * Handles routing for a PHP MVC framework with easy-to-understand variable names
 */
class Routerjj
{
    // Core dependencies
    private $app;
    private $db;
    private $config;
    private $logger;
    private $cache;

    // Current request info
    private string $url = '';
    private string $path = '';
    private array $segments = [];
    private array $params = [];
    
    // Current route result
    public string $controller = '';
    public string $action = '';
    public string $module = '';
    public string $area = '';
    public string $file_path = '';
    public int $page = 1;
    
    // Settings
    private string $language = 'en';
    private array $languages = ['en', 'ar'];
    private bool $is_api = false;
    private string $api_version = 'v1';
    
    // Route storage
    private array $routes = [];
    private array $patterns = [];
    private array $middleware = [];

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app->get('db');
        $this->config = $app->get('config');
        $this->logger = $app->get('logger');
        $this->cache = $app->get('cache');
        
        $this->loadRoutes();
        $this->setupDefaultRoutes();
    }

    /**
     * Main method to handle routing
     */
    public function dispatch(): void
    {
        try {
            $this->parseUrl();
            $this->findRoute();
            $this->runMiddleware();
            $this->loadController();
            $this->runAction();
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Parse the incoming URL
     */
    private function parseUrl(): void
    {
        $this->url = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        $this->url = strtok($this->url, '?');
        
        // Detect language
        $this->detectLanguage();
        
        // Clean up the path
        $this->path = trim($this->url, '/');
        
        // Split into segments
        $this->segments = $this->path ? explode('/', $this->path) : [];
        
        // Check for API request
        $this->checkApiRequest();
        
        // Extract pagination
        $this->extractPagination();
        
        // Store in app for global access
        $this->app->set('current_url', $this->url);
        $this->app->set('current_path', $this->path);
        $this->app->set('language', $this->language);
    }

    /**
     * Detect language from URL
     */
    private function detectLanguage(): void
    {
        $parts = explode('/', trim($this->url, '/'));
        $first = $parts[0] ?? '';
        
        if (in_array($first, $this->languages)) {
            $this->language = $first;
            $this->url = '/' . implode('/', array_slice($parts, 1));
        }
    }

    /**
     * Check if this is an API request
     */
    private function checkApiRequest(): void
    {
        if (str_starts_with($this->path, 'api/')) {
            $this->is_api = true;
            $this->area = 'api';
            
            // Remove 'api/' from path
            $this->path = substr($this->path, 4);
            $this->segments = $this->path ? explode('/', $this->path) : [];
            
            // Check for version
            if (isset($this->segments[0]) && str_starts_with($this->segments[0], 'v')) {
                $this->api_version = array_shift($this->segments);
                $this->path = implode('/', $this->segments);
            }
        }
    }

    /**
     * Extract pagination info (e.g., /page/2)
     */
    private function extractPagination(): void
    {
        $key = array_search('page', $this->segments);
        if ($key !== false && isset($this->segments[$key + 1])) {
            $this->page = (int)$this->segments[$key + 1];
            
            // Remove page segments
            unset($this->segments[$key], $this->segments[$key + 1]);
            $this->segments = array_values($this->segments);
            $this->path = implode('/', $this->segments);
        }
    }

    /**
     * Find matching route
     */
    private function findRoute(): void
    {
        // Try module routes first
        if ($this->matchModule()) return;
        
        // Try custom patterns
        if ($this->matchPattern()) return;
        
        // Try SEO URLs
        if ($this->matchSeoUrl()) return;
        
        // Default controller/action
        $this->setDefaultRoute();
    }

    /**
     * Match module-based routes (admin/users/edit, etc.)
     */
    private function matchModule(): bool
    {
        if (empty($this->segments)) return false;
        
        $first = $this->segments[0];
        
        // Admin area
        if ($first === 'admin') {
            $this->area = 'admin';
            $this->module = $this->segments[1] ?? 'dashboard';
            $this->controller = $this->segments[2] ?? 'home';
            $this->action = $this->segments[3] ?? 'index';
            $this->params = array_slice($this->segments, 4);
            
            $this->file_path = $this->buildPath('admin', $this->module, $this->controller);
            return file_exists($this->file_path);
        }
        
        // App modules
        if ($this->isModule($first)) {
            $this->area = 'app';
            $this->module = ucfirst($first);
            $this->controller = $this->segments[1] ?? 'home';
            $this->action = $this->segments[2] ?? 'index';
            $this->params = array_slice($this->segments, 3);
            
            $this->file_path = $this->buildPath('app', $this->module, $this->controller);
            return file_exists($this->file_path);
        }
        
        return false;
    }

    /**
     * Match custom route patterns
     */
    private function matchPattern(): bool
    {
        foreach ($this->patterns as $pattern => $route) {
            if (preg_match($pattern, $this->path, $matches)) {
                $this->controller = $route['controller'];
                $this->action = $route['action'];
                $this->params = array_slice($matches, 1);
                
                if (isset($route['module'])) {
                    $this->module = $route['module'];
                }
                if (isset($route['area'])) {
                    $this->area = $route['area'];
                }
                
                return true;
            }
        }
        return false;
    }

    /**
     * Match SEO-friendly URLs from database
     */
    private function matchSeoUrl(): bool
    {
        if (empty($this->path)) return false;
        
        $query = $this->db->query(
            "SELECT controller, action, params FROM seo_routes WHERE url = '" . 
            $this->db->escape($this->path) . "' LIMIT 1"
        );
        
        if ($query->num_rows > 0) {
            $row = $query->row;
            $this->controller = $row['controller'];
            $this->action = $row['action'] ?: 'index';
            $this->params = $row['params'] ? json_decode($row['params'], true) : [];
            return true;
        }
        
        return false;
    }

    /**
     * Set default controller and action
     */
    private function setDefaultRoute(): void
    {
        $this->controller = $this->segments[0] ?? 'home';
        $this->action = $this->segments[1] ?? 'index';
        $this->params = array_slice($this->segments, 2);
        
        // Clean controller name
        $this->controller = str_replace('-', '_', $this->controller);
        $this->action = str_replace('-', '_', $this->action);
        
        // Set file path
        if (empty($this->file_path)) {
            $this->file_path = $this->buildPath();
        }
    }

    /**
     * Build file path for controller
     */
    private function buildPath(string $area = '', string $module = '', string $controller = ''): string
    {
        if ($this->is_api) {
            return DIR_APP . "controllers/api/{$this->controller}.php";
        }
        
        if ($area && $module) {
            return DIR_APP . "modules/{$area}/{$module}/controllers/{$controller}.php";
        }
        
        return DIR_APP . "controllers/{$this->controller}.php";
    }

    /**
     * Run middleware stack
     */
    private function runMiddleware(): void
    {
        foreach ($this->middleware as $class) {
            $middleware = new $class($this->app);
            if (!$middleware->handle()) {
                throw new Exception("Middleware {$class} blocked request");
            }
        }
    }

    /**
     * Load controller file
     */
    private function loadController(): void
    {
        if (!file_exists($this->file_path)) {
            $this->show404();
            return;
        }
        
        require_once $this->file_path;
        
        $class_name = $this->getControllerClass();
        
        if (!class_exists($class_name)) {
            throw new Exception("Controller class {$class_name} not found");
        }
        
        $this->controller_instance = new $class_name($this->app);
    }

    /**
     * Get controller class name
     */
    private function getControllerClass(): string
    {
        $prefix = '';
        if ($this->is_api) $prefix = 'Api';
        if ($this->area === 'admin') $prefix = 'Admin';
        
        $name = str_replace(['_', '-'], '', $this->controller);
        return $prefix . ucfirst($name) . 'Controller';
    }

    /**
     * Run controller action
     */
    private function runAction(): void
    {
        // Check if action exists
        if (!method_exists($this->controller_instance, $this->action)) {
            $this->action = 'index';
        }
        
        // Log the execution
        $this->logger->info("Running {$this->controller}::{$this->action}");
        
        // Call the action with parameters
        call_user_func_array(
            [$this->controller_instance, $this->action], 
            $this->params
        );
    }

    /**
     * Load all route files from modules
     */
    private function loadRoutes(): void
    {
        $areas = ['admin', 'app', 'api'];
        
        foreach ($areas as $area) {
            $path = DIR_APP . "modules/{$area}";
            if (!is_dir($path)) continue;
            
            $modules = scandir($path);
            foreach ($modules as $module) {
                if ($module === '.' || $module === '..') continue;
                
                $route_file = "{$path}/{$module}/routes.php";
                if (file_exists($route_file)) {
                    $routes = require $route_file;
                    if (is_array($routes)) {
                        $this->routes = array_merge($this->routes, $routes);
                    }
                }
            }
        }
    }

    /**
     * Setup default route patterns
     */
    private function setupDefaultRoutes(): void
    {
        // Product view: /product/123
        $this->addPattern('/^product\/(\d+)$/', [
            'controller' => 'product',
            'action' => 'view'
        ]);
        
        // Category with pagination: /category/electronics/page/2
        $this->addPattern('/^category\/([^\/]+)$/', [
            'controller' => 'category',
            'action' => 'view'
        ]);
        
        // User profile: /user/john-doe
        $this->addPattern('/^user\/([^\/]+)$/', [
            'controller' => 'user',
            'action' => 'profile'
        ]);
    }

    /**
     * Add a route pattern
     */
    public function addPattern(string $pattern, array $route): void
    {
        $this->patterns[$pattern] = $route;
    }

    /**
     * Add middleware
     */
    public function addMiddleware(string $class): void
    {
        $this->middleware[] = $class;
    }

    /**
     * Check if a module exists
     */
    private function isModule(string $name): bool
    {
        return is_dir(DIR_APP . "modules/app/" . ucfirst($name));
    }

    /**
     * Handle 404 errors
     */
    private function show404(): void
    {
        if ($this->is_api) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
        }
        
        $this->file_path = DIR_APP . 'controllers/error.php';
        $this->controller = 'error';
        $this->action = 'show404';
    }

    /**
     * Handle routing errors
     */
    private function handleError(Exception $e): void
    {
        $this->logger->error("Router Error: " . $e->getMessage());
        
        if ($this->is_api) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        } else {
            $this->show404();
        }
    }

    /**
     * Get current route info
     */
    public function getRoute(): array
    {
        return [
            'controller' => $this->controller,
            'action' => $this->action,
            'module' => $this->module,
            'area' => $this->area,
            'params' => $this->params,
            'language' => $this->language,
            'page' => $this->page,
            'is_api' => $this->is_api
        ];
    }

    /**
     * Generate URL
     */
    public function url(string $path, array $params = []): string
    {
        $url = '/' . $this->language . '/' . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Redirect to URL
     */
    public function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }
}