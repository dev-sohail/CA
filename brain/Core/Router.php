<?php

namespace Brain\Classes\Core;

use Brain\Classes\Api\ApiResponse;
use Brain\Classes\Cache\CacheManager;
use Brain\Classes\Http\Middleware;
use Brain\Classes\Http\Request;
use Brain\Classes\Http\Response;
use Brain\Classes\Logging\Logger;
use Brain\Classes\Security\Csrf;
use Brain\Classes\Security\Firewall;
use Exception;

/**
 * Enhanced Router Core Class
 * Handles routing, middleware, module loading, and SEO URL management
 */
final class Router
{
    private Registry $registry;
    private $db;
    private $config;
    private CacheManager $cache;
    private Logger $logger;
    private Middleware $middleware;
    private Firewall $firewall;
    
    // Route properties
    private string $path = '';
    private string $requri = '';
    private array $args = [];
    private array $routes = [];
    private array $middlewareStack = [];
    private array $moduleRoutes = [];
    
    // Controller properties
    public ?string $file = null;
    public string $controller = '';
    public string $action = '';
    public string $module = '';
    public string $area = ''; // admin, api, app
    public int $c_p_index = 1;
    public array $slog_data = [];
    
    // Route matching
    private array $routePatterns = [];
    private array $namedRoutes = [];
    private string $currentLanguage = 'en';
    private array $supportedLanguages = ['en', 'ar'];
    
    // API and versioning
    private string $apiVersion = 'v1';
    private bool $isApiRequest = false;
    
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->cache = $registry->get('cache');
        $this->logger = $registry->get('logger');
        $this->middleware = new Middleware($registry);
        $this->firewall = new Firewall($registry);
        
        $this->loadModuleRoutes();
        $this->initializeRoutes();
    }

    /**
     * Main routing execution method
     */
    public function run(): void
    {
        try {
            // Security checks
            $this->firewall->checkRequest();
            
            // Parse and resolve route
            $this->parseRequest();
            $this->resolveRoute();
            
            // Handle middleware stack
            $this->runMiddleware();
            
            // Load and execute controller
            $this->loadController();
            $this->executeAction();
            
        } catch (Exception $e) {
            $this->handleRoutingError($e);
        }
    }

    /**
     * Parse incoming request
     */
    private function parseRequest(): void
    {
        $this->detectLanguage();
        $this->cleanRequestUri();
        $this->detectApiRequest();
        $this->extractPaginationInfo();
        
        // Store processed URI for other components
        $this->registry->set('processedUri', $this->requri);
        $this->registry->set('currentLanguage', $this->currentLanguage);
    }

    /**
     * Detect and set current language
     */
    private function detectLanguage(): void
    {
        $fullurl = explode('/', substr($_SERVER['REQUEST_URI'], 1), 2);
        $langCandidate = str_replace("-", " ", $fullurl[0] ?? '');
        
        if (in_array($langCandidate, $this->supportedLanguages)) {
            $this->currentLanguage = $langCandidate;
            $this->requri = str_replace([$langCandidate . '/'], '', $_SERVER['REQUEST_URI']);
        } else {
            $this->requri = $_SERVER['REQUEST_URI'];
        }
    }

    /**
     * Clean and prepare request URI
     */
    private function cleanRequestUri(): void
    {
        // Remove query string
        $this->requri = preg_replace('/\?.*/', '', $this->requri);
        
        // Trim slashes
        $this->path = ltrim(rtrim($this->requri, "/"), "/");
        
        // Store original path
        $this->registry->set('originalPath', $this->path);
    }

    /**
     * Detect if this is an API request
     */
    private function detectApiRequest(): void
    {
        $this->isApiRequest = strpos($this->path, 'api/') === 0;
        
        if ($this->isApiRequest) {
            // Extract API version if present
            if (preg_match('/^api\/(v\d+)\//', $this->path, $matches)) {
                $this->apiVersion = $matches[1];
                $this->path = preg_replace('/^api\/v\d+\//', '', $this->path);
            } else {
                $this->path = preg_replace('/^api\//', '', $this->path);
            }
            
            $this->area = 'api';
        }
    }

    /**
     * Extract pagination information
     */
    private function extractPaginationInfo(): void
    {
        $uris = $this->path ? explode("/", $this->path) : [];
        
        foreach ($uris as $key => $value) {
            if ($value === 'page' && isset($uris[$key + 1])) {
                $this->c_p_index = (int)$uris[$key + 1];
                $this->path = str_replace('/page/' . $uris[$key + 1], '', $this->path);
                break;
            }
        }
        
        $this->registry->set('c_p_index', $this->c_p_index);
    }

    /**
     * Resolve the current route
     */
    private function resolveRoute(): void
    {
        // Try module routes first
        if ($this->resolveModuleRoute()) {
            return;
        }
        
        // Try SEO URL resolution
        if ($this->resolveSeoRoute()) {
            return;
        }
        
        // Try custom route patterns
        if ($this->resolvePatternRoute()) {
            return;
        }
        
        // Default controller/action resolution
        $this->resolveDefaultRoute();
    }

    /**
     * Resolve module-based routes
     */
    private function resolveModuleRoute(): bool
    {
        $parts = explode('/', $this->path);
        
        // Check for admin area
        if (isset($parts[0]) && $parts[0] === 'admin') {
            $this->area = 'admin';
            $this->module = $parts[1] ?? 'Home';
            $this->controller = $parts[2] ?? 'index';
            $this->action = $parts[3] ?? 'index';
            
            $this->file = $this->buildModulePath('admin', $this->module, $this->controller);
            return file_exists($this->file);
        }
        
        // Check for app modules
        if (isset($parts[0]) && $this->isAppModule($parts[0])) {
            $this->area = 'app';
            $this->module = ucfirst($parts[0]);
            $this->controller = $parts[1] ?? 'index';
            $this->action = $parts[2] ?? 'index';
            
            $this->file = $this->buildModulePath('app', $this->module, $this->controller);
            return file_exists($this->file);
        }
        
        return false;
    }

    /**
     * Resolve SEO-friendly URLs
     */
    private function resolveSeoRoute(): bool
    {
        if (empty($this->path)) {
            return false;
        }

        // Handle admin area
        if ($this->path === 'admin') {
            $this->area = 'admin';
            $this->controller = $_GET['controller'] ?? 'dashboard';
            $this->action = 'index';
            return true;
        }

        // Skip AJAX requests
        if ($this->isAjaxRequest()) {
            return false;
        }

        // Check banners
        $banners = $this->getAllBanners();
        if (isset($banners[$this->path])) {
            $this->registry->set('banners', $banners[$this->path]);
        }

        // Process URL segments for aliases
        $resolvedPath = $this->processUrlSegments($this->path);
        
        if ($resolvedPath !== $this->path) {
            $parts = explode('/', $resolvedPath);
            $this->controller = $parts[0] ?? 'home';
            $this->action = isset($parts[1]) ? str_replace('-', '_', $parts[1]) : 'index';
            return true;
        }

        return false;
    }

    /**
     * Process URL segments for alias resolution
     */
    private function processUrlSegments(string $alias): string
    {
        $resolvedSegments = '';
        $parts = explode('/', $alias);
        
        foreach ($parts as $part) {
            $query = $this->db->query(
                "SELECT slog_id, url, slog FROM aliases WHERE url = '" . 
                $this->db->escape($part) . "' LIMIT 1"
            );
            
            if ($query->num_rows > 0) {
                $hash = empty($resolvedSegments) ? '/' : '';
                $resolvedSegments = $query->row['slog'] . $hash;
                $this->registry->set('slug_data', $query->row);
            }
        }
        
        if (!empty($resolvedSegments)) {
            return $resolvedSegments;
        }
        
        // Fallback: use first segment as controller
        if (count($parts) > 0) {
            $routeData = [
                'slug' => $parts[0],
                'query' => $parts[1] ?? null
            ];
            $this->registry->set('slug_data', $routeData);
        }
        
        return $alias;
    }

    /**
     * Resolve pattern-based routes
     */
    private function resolvePatternRoute(): bool
    {
        foreach ($this->routePatterns as $pattern => $route) {
            if (preg_match($pattern, $this->path, $matches)) {
                $this->controller = $route['controller'];
                $this->action = $route['action'];
                $this->args = array_slice($matches, 1);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Default route resolution
     */
    private function resolveDefaultRoute(): void
    {
        $parts = explode('/', $this->path);
        
        $this->controller = !empty($parts[0]) ? $parts[0] : 'home';
        $this->action = isset($parts[1]) ? str_replace('-', '_', $parts[1]) : 'index';
        $this->args = array_slice($parts, 2);
        
        // Set default file path
        if (empty($this->file)) {
            $this->file = $this->buildControllerPath($this->controller);
        }
        
        $this->registry->set('bodyclass', $this->path);
    }

    /**
     * Run middleware stack
     */
    private function runMiddleware(): void
    {
        foreach ($this->middlewareStack as $middlewareClass) {
            $middleware = new $middlewareClass($this->registry);
            
            if (!$middleware->handle()) {
                throw new Exception("Middleware {$middlewareClass} blocked request");
            }
        }
    }

    /**
     * Load the appropriate controller
     */
    private function loadController(): void
    {
        // Check if file exists
        if (!is_readable($this->file)) {
            $this->handleNotFound();
            return;
        }

        require_once($this->file);
        
        // Clean controller name
        $this->controller = str_replace(['-', '_', '&'], '', $this->controller);
        
        // Build class name
        $className = $this->buildControllerClassName();
        
        if (!class_exists($className)) {
            throw new Exception("Controller class {$className} not found");
        }
        
        $this->controllerInstance = new $className($this->registry);
    }

    /**
     * Execute the controller action
     */
    private function executeAction(): void
    {
        if (!is_callable([$this->controllerInstance, $this->action])) {
            $this->action = 'index';
        }
        
        // Log the route execution
        $this->logger->info("Executing route: {$this->controller}::{$this->action}", [
            'module' => $this->module,
            'area' => $this->area,
            'args' => $this->args
        ]);
        
        // Execute with error handling
        try {
            call_user_func_array(
                [$this->controllerInstance, $this->action], 
                $this->args
            );
        } catch (Exception $e) {
            $this->handleControllerError($e);
        }
    }

    /**
     * Build controller file path
     */
    private function buildControllerPath(string $controller): string
    {
        if ($this->isApiRequest) {
            return DIR_APP . "modules/api/Controllers/{$controller}.php";
        }
        
        return DIR_APP . "controller/{$controller}.php";
    }

    /**
     * Build module file path
     */
    private function buildModulePath(string $area, string $module, string $controller): string
    {
        return DIR_APP . "modules/{$area}/{$module}/Controllers/{$controller}.php";
    }

    /**
     * Build controller class name
     */
    private function buildControllerClassName(): string
    {
        $prefix = '';
        
        if ($this->isApiRequest) {
            $prefix = 'Api';
        } elseif ($this->area === 'admin') {
            $prefix = 'Admin';
        }
        
        return $prefix . 'Controller' . ucfirst($this->controller);
    }

    /**
     * Load module routes
     */
    private function loadModuleRoutes(): void
    {
        $modulesPaths = [
            DIR_APP . 'modules/admin/',
            DIR_APP . 'modules/app/',
            DIR_APP . 'modules/api/'
        ];
        
        foreach ($modulesPaths as $modulePath) {
            if (is_dir($modulePath)) {
                $modules = scandir($modulePath);
                foreach ($modules as $module) {
                    if ($module !== '.' && $module !== '..' && is_dir($modulePath . $module)) {
                        $routeFile = $modulePath . $module . '/routes.php';
                        if (file_exists($routeFile)) {
                            $routes = require $routeFile;
                            if (is_array($routes)) {
                                $this->moduleRoutes[$module] = $routes;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Initialize default routes and patterns
     */
    private function initializeRoutes(): void
    {
        // Add common route patterns
        $this->addRoutePattern('/^products\/(\d+)$/', [
            'controller' => 'product',
            'action' => 'view'
        ]);
        
        $this->addRoutePattern('/^category\/([^\/]+)\/page\/(\d+)$/', [
            'controller' => 'category',
            'action' => 'index'
        ]);
        
        // Add API routes
        if ($this->isApiRequest) {
            $this->addApiRoutes();
        }
    }

    /**
     * Add API-specific routes
     */
    private function addApiRoutes(): void
    {
        $this->addRoutePattern('/^users\/(\d+)$/', [
            'controller' => 'user',
            'action' => 'show'
        ]);
        
        $this->addRoutePattern('/^auth\/login$/', [
            'controller' => 'auth',
            'action' => 'login'
        ]);
    }

    /**
     * Add a route pattern
     */
    public function addRoutePattern(string $pattern, array $route): void
    {
        $this->routePatterns[$pattern] = $route;
    }

    /**
     * Add middleware to stack
     */
    public function addMiddleware(string $middlewareClass): void
    {
        $this->middlewareStack[] = $middlewareClass;
    }

    /**
     * Get all banners for SEO
     */
    private function getAllBanners(): array
    {
        $cacheKey = "banners_lang_{$this->config->get('config_language_id')}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $routes = [];
        $sql = "SELECT b.*, bd.* FROM banner b 
                LEFT JOIN banner_description bd ON bd.banner_id = b.banner_id 
                WHERE b.status = 1 AND bd.lang_id = '" . 
                $this->config->get('config_language_id') . "'";
        
        $result = $this->db->query($sql);
        
        foreach ($result->rows as $row) {
            $routes[$row['url']] = [
                'meta_title' => $row['meta_title'] ?: '',
                'meta_keyword' => $row['meta_keyword'] ?: '',
                'meta_description' => $row['meta_description'] ?: $row['description'],
                'banner' => $row['image'] ?: '',
                'description' => $row['description'] ?: '',
                'title' => $row['title'] ?: '',
            ];
        }
        
        $this->cache->set($cacheKey, $routes, 3600); // Cache for 1 hour
        
        return $routes;
    }

    /**
     * Check if module exists in app area
     */
    private function isAppModule(string $module): bool
    {
        return is_dir(DIR_APP . "modules/app/" . ucfirst($module));
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Handle 404 errors
     */
    private function handleNotFound(): void
    {
        if ($this->isApiRequest) {
            $response = new ApiResponse();
            $response->error(404, 'Endpoint not found');
            return;
        }
        
        $this->file = DIR_APP . 'controller/error404.php';
        $this->controller = 'error404';
        $this->action = 'index';
    }

    /**
     * Handle routing errors
     */
    private function handleRoutingError(Exception $e): void
    {
        $this->logger->error('Routing Error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        if ($this->isApiRequest) {
            $response = new ApiResponse();
            $response->error(500, 'Internal routing error');
        } else {
            $this->handleNotFound();
        }
    }

    /**
     * Handle controller execution errors
     */
    private function handleControllerError(Exception $e): void
    {
        $this->logger->error('Controller Error: ' . $e->getMessage(), [
            'controller' => $this->controller,
            'action' => $this->action,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        if ($this->isApiRequest) {
            $response = new ApiResponse();
            $response->error(500, 'Controller execution error');
        } else {
            throw $e; // Re-throw for global error handler
        }
    }

    /**
     * Get current route information
     */
    public function getCurrentRoute(): array
    {
        return [
            'controller' => $this->controller,
            'action' => $this->action,
            'module' => $this->module,
            'area' => $this->area,
            'args' => $this->args,
            'language' => $this->currentLanguage,
            'is_api' => $this->isApiRequest,
            'api_version' => $this->apiVersion
        ];
    }

    /**
     * Generate URL from route
     */
    public function url(string $route, array $params = []): string
    {
        // Implementation for URL generation
        $url = '/' . $this->currentLanguage . '/' . ltrim($route, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
}