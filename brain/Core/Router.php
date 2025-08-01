<?php



// /**
//  * CyberAfridi Custom Router
//  *
//  * A flexible and extensible routing class for CyberAfridi Framework
//  * Supports custom routing, MVC fallback, language prefixing, DI, middleware, and more.
//  *
//  * @version 1.0.0
//  * @author CyberAfridi
//  */

// final class Router
// {
//     private $registry;
//     private $routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];
//     private $namedRoutes = [];
//     private $beforeFilters = [];
//     private $currentRoute = [];
//     private $routeGroups = [];

//     private $enableUrlGeneration = true;
//     private $enableRouteParameters = true;
//     private $enableDI = true;

//     public function __construct($registry)
//     {
//         $this->registry = $registry;
//     }

//     public function setFeature(string $feature, bool $status): void
//     {
//         switch ($feature) {
//             case 'url_generation': $this->enableUrlGeneration = $status; break;
//             case 'route_parameters': $this->enableRouteParameters = $status; break;
//             case 'dependency_injection': $this->enableDI = $status; break;
//             default: throw new Exception("Unknown feature: $feature");
//         }
//     }

//     public function group(string $prefix, callable $callback): void
//     {
//         $this->routeGroups[] = $prefix;
//         $callback($this);
//         array_pop($this->routeGroups);
//     }

//     public function before(string $method, string $path, callable $filter): void
//     {
//         $normalized = $this->normalizePath($path);
//         $this->beforeFilters[$method][$normalized][] = $filter;
//     }

//     public function run(): void
//     {
//         $this->enforceHttps();

//         $uri = $this->normalizePath(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
//         $method = $_SERVER['REQUEST_METHOD'];

//         $lang = $this->detectAndSetLanguage();
//         if (preg_match('#^/' . preg_quote($lang, '#') . '(/|$)#', $uri)) {
//             $uri = substr($uri, strlen($lang) + 1) ?: '/';
//         }

//         if (isset($_GET['q'])) {
//             $uri = $this->handleDynamicQuery($_GET['q']);
//         }

//         if (preg_match('#\.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?|woff|ttf|eot|map)$#i', $uri)) {
//             return;
//         }

//         if ($this->enableRouteParameters && isset($this->routes[$method])) {
//             foreach ($this->routes[$method] as $pattern => $handler) {
//                 $regex = $this->convertRoutePatternToRegex($pattern, $paramNames);
//                 if (preg_match($regex, $uri, $matches)) {
//                     array_shift($matches);
//                     $params = array_combine($paramNames, $matches) ?: [];
//                     $this->handleFilters($method, $pattern);
//                     $this->callHandler($handler, $params);
//                     return;
//                 }
//             }
//         }

//         if (isset($this->routes[$method][$uri])) {
//             $this->handleFilters($method, $uri);
//             $this->callHandler($this->routes[$method][$uri]);
//             return;
//         }

//         $this->fallbackMVC($uri);
//     }

//     private function fallbackMVC(string $uri): void
//     {
//         $segments = array_map('strtolower', explode('/', trim($uri, '/')));
//         $module = 'Public';
//         $controller = ucfirst($segments[1] ?? 'Home');
//         $action = $segments[0] ?? 'index';

//         if (in_array($segments[0] ?? '', ['admin', 'ceo'], true)) {
//             $module = ucfirst($segments[0]);
//             $controller = ucfirst($segments[1] ?? 'Dashboard');
//             $action = $segments[2] ?? 'index';
//         }

//         $action = str_replace('-', '_', $action);
//         $file = DIR_CONTROLLERS . $module . '/' . $controller . '.php';
//         $class = "Controller{$module}{$controller}";

//         if (!is_readable($file)) {
//             $this->serve404("Controller file not found: $file");
//             return;
//         }

//         require_once $file;
//         if (!class_exists($class)) {
//             $this->serve404("Class [$class] not found in file: $file");
//             return;
//         }

//         $instance = new $class($this->registry);
//         if (!method_exists($instance, $action)) {
//             $action = method_exists($instance, 'index') ? 'index' : null;
//         }

//         if ($action) {
//             $instance->$action();
//         } else {
//             $this->serve404("No valid method found in controller [$class]");
//         }
//     }

//     private function handleDynamicQuery(string $q): string
//     {
//         $q = trim($q, "\"' \t\n\r\0\x0B");
//         $q = str_replace(' ', '-', $q);
//         $path = strtolower(trim($q, '/'));
//         $segments = explode('/', $path);

//         $module = 'Public';
//         $controller = ucfirst($segments[0] ?? 'Home');

//         if (in_array($segments[0] ?? '', ['admin', 'ceo'], true)) {
//             $module = ucfirst($segments[0]);
//             $controller = ucfirst($segments[1] ?? 'Dashboard');
//         }

//         $file = DIR_CONTROLLERS . "{$module}/{$controller}.php";
//         return is_readable($file) ? "/$path" : '/search/' . rawurlencode($q);
//     }

//     private function normalizePath(string $path): string
//     {
//         $path = trim($path);
//         $path = preg_replace('#/{2,}#', '/', $path);
//         $path = '/' . ltrim($path, '/');
//         return strlen($path) > 1 ? rtrim($path, '/') : $path;
//     }

//     private function handleFilters(string $method, string $path): void
//     {
//         if (!empty($this->beforeFilters[$method][$path])) {
//             foreach ($this->beforeFilters[$method][$path] as $filter) {
//                 $filter($this->registry->get('request'));
//             }
//         }
//     }

//     private function convertRoutePatternToRegex(string $pattern, array &$paramNames): string
//     {
//         $paramNames = [];
//         $normalized = $this->normalizePath($pattern);
//         $regex = preg_replace_callback('/\{(\w+)\}/', function ($matches) use (&$paramNames) {
//             $paramNames[] = $matches[1];
//             return '([^/]+)';
//         }, $normalized);

//         return '#^' . $regex . '$#';
//     }

//     public function url(string $name, array $params = []): string
//     {
//         if (!$this->enableUrlGeneration) {
//             throw new Exception("URL generation is disabled.");
//         }

//         if (!isset($this->namedRoutes[$name])) {
//             throw new Exception("Named route [$name] not found.");
//         }

//         $path = $this->namedRoutes[$name]['path'];
//         foreach ($params as $key => $value) {
//             $path = str_replace("{" . $key . "}", urlencode($value), $path);
//         }

//         return $this->normalizePath($path);
//     }

//     private function callHandler($handler, array $params = []): void
//     {
//         if ($this->enableDI) {
//             $request = $this->registry->get('request');
//             $response = $this->registry->get('response');
//             call_user_func($handler, $request, $response, ...array_values($params));
//         } elseif (!empty($params)) {
//             call_user_func_array($handler, array_values($params));
//         } else {
//             call_user_func($handler);
//         }
//     }

//     public function get(string $path, $handler, $name = null): void
//     {
//         $this->register('GET', $path, $handler, $name);
//     }

//     public function post(string $path, $handler, $name = null): void
//     {
//         $this->register('POST', $path, $handler, $name);
//     }

//     public function register(string $method, string $path, $handler, ?string $name = null): void
//     {
//         $fullPath = implode('', $this->routeGroups) . $this->normalizePath($path);
//         $method = strtoupper($method);
//         $this->routes[$method][$fullPath] = $handler;

//         if ($name && $this->enableUrlGeneration) {
//             $this->namedRoutes[$name] = ['method' => $method, 'path' => $fullPath];
//         }
//     }

//     private function isAjax(): bool
//     {
//         return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
//             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
//     }

//     public function email_send(array $data, string $template): string
//     {
//         $file = DIR_EMAIL_TEMPLATE . $template . '/index.tpl';
//         if (!file_exists($file)) {
//             throw new Exception("Email template not found: $file");
//         }
//         extract($data, EXTR_SKIP);
//         ob_start();
//         include $file;
//         return ob_get_clean();
//     }

//     private function enforceHttps(): void
//     {
//         if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return;
//         if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return;
//         if ($_SERVER['SERVER_PORT'] != 443) {
//             $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//             header("Location: $redirect", true, 301);
//             exit;
//         }
//     }

//     private function detectAndSetLanguage(): string
//     {
//         // placeholder for dynamic language detection (e.g., from URI or headers)
//         return 'en'; // default to English
//     }

//     private function serve404(string $message): void
//     {
//         http_response_code(404);
//         echo "<h1>404 Not Found</h1><p>$message</p>";
//         exit;
//     }
// }


// /**
//  * CyberTirah Custom Router
//  *
//  * A powerful, flexible routing system for PHP applications with advanced fallback mechanisms.
//  *
//  * ------------------------------------------------------------------------
//  * ✅ Key Features:
//  * ------------------------------------------------------------------------
//  * - Dynamic route registration with parameter support
//  * - Route grouping and middleware support
//  * - Multi-level fallback routing system
//  * - Language detection and routing
//  * - Module-based controller resolution
//  * - API route handling
//  * - Static asset handling
//  * - HTTPS enforcement
//  * - Rate limiting integration
//  * - Comprehensive error handling
//  *
//  * ------------------------------------------------------------------------
//  * 🧰 Example Usage:
//  * ------------------------------------------------------------------------
//  * $router = new Router($registry);
//  * 
//  * // Basic routes
//  * $router->get('/', 'HomeController@index', 'home');
//  * $router->post('/login', 'AuthController@login', 'auth.login');
//  * 
//  * // Parameterized routes
//  * $router->get('/user/{id}', 'UserController@show', 'user.show');
//  * $router->get('/blog/{slug}', 'BlogController@show', 'blog.show');
//  * 
//  * // Route groups
//  * $router->group('/admin', function($router) {
//  *     $router->get('/', 'AdminController@dashboard');
//  *     $router->get('/users', 'UserController@index');
//  * });
//  * 
//  * // Middleware
//  * $router->before('GET', '/admin/*', 'AuthMiddleware::check');
//  * 
//  * // Run the router
//  * $router->run();
//  *
//  * ------------------------------------------------------------------------
//  * ⚙️ Method Summary:
//  * ------------------------------------------------------------------------
//  * - get(), post(), put(), delete(), patch() - Register HTTP routes
//  * - group() - Group routes with common prefix
//  * - before() - Add middleware/filters
//  * - run() - Main dispatch method
//  * - url() - Generate URLs from named routes
//  * - setFeature() - Enable/disable router features
//  *
//  * ------------------------------------------------------------------------
//  * 🧩 Extensibility:
//  * ------------------------------------------------------------------------
//  * - Custom fallback handlers
//  * - Plugin system integration
//  * - Event system hooks
//  * - Custom parameter patterns
//  * - Advanced caching strategies
//  */

// final class Router
// {
//     /**
//      * Router Core Configuration & Grouping
//      */
    
//     // Service container to access shared dependencies
//     private $registry;
    
//     // Route table indexed by HTTP method
//     private $routes = [
//         'GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => [], 
//         'PATCH' => [], 'OPTIONS' => [], 'HEAD' => []
//     ];
    
//     // Stores named routes for URL generation
//     private $namedRoutes = [];
    
//     // Filters to run before specific routes (middleware-like)
//     private $beforeFilters = [];
//     private $afterFilters = [];
    
//     // Stores data about the currently matched route
//     private $currentRoute = [];
    
//     // Stack of route group prefixes
//     private $routeGroups = [];
//     private $routeGroupMiddleware = [];
    
//     // Feature flags
//     private $enableUrlGeneration = true;
//     private $enableRouteParameters = true;
//     private $enableDI = true;
//     private $enableCaching = true;
//     private $enableHttpsRedirect = false;
    
//     // Language and localization
//     private $detectedLanguage = 'en';
//     private $supportedLanguages = ['en', 'es', 'fr', 'de', 'ar'];
    
//     // Route caching
//     private $routeCache = [];
//     private $cacheEnabled = true;
    
//     // Error handlers
//     private $errorHandlers = [];
    
//     // Performance metrics
//     private $metrics = [
//         'routes_matched' => 0,
//         'fallbacks_used' => 0,
//         'cache_hits' => 0
//     ];

//     /**
//      * Constructor to initialize the router with the application's registry
//      *
//      * @param object $registry Application service container
//      */
//     public function __construct($registry)
//     {
//         $this->registry = $registry;
//         $this->initializeDefaultErrorHandlers();
//         $this->loadRouteCache();
//     }

//     /**
//      * Enable or disable router features at runtime
//      *
//      * @param string $feature Feature key
//      * @param bool   $status  True to enable, false to disable
//      *
//      * @throws Exception If an unknown feature key is provided
//      */
//     public function setFeature(string $feature, bool $status): void
//     {
//         $features = [
//             'url_generation' => 'enableUrlGeneration',
//             'route_parameters' => 'enableRouteParameters', 
//             'dependency_injection' => 'enableDI',
//             'caching' => 'enableCaching',
//             'https_redirect' => 'enableHttpsRedirect'
//         ];

//         if (!isset($features[$feature])) {
//             throw new Exception("Unknown feature: $feature");
//         }

//         $this->{$features[$feature]} = $status;
//     }

//     /**
//      * Group multiple routes under a common path prefix
//      *
//      * @param string   $prefix     Route prefix (e.g. '/admin')
//      * @param callable $callback   Callback that registers routes inside the group
//      * @param array    $middleware Optional middleware for the group
//      */
//     public function group(string $prefix, callable $callback, array $middleware = []): void
//     {
//         // Push prefix and middleware onto stacks
//         $this->routeGroups[] = $prefix;
//         $this->routeGroupMiddleware[] = $middleware;

//         // Execute the callback to define grouped routes
//         $callback($this);

//         // Remove the last prefix and middleware after group ends
//         array_pop($this->routeGroups);
//         array_pop($this->routeGroupMiddleware);
//     }

//     /**
//      * Register a before-filter (middleware) for routes
//      *
//      * @param string   $method HTTP method or '*' for all
//      * @param string   $path   Path pattern to apply the filter to
//      * @param callable $filter Callback to run before the route is handled
//      */
//     public function before(string $method, string $path, callable $filter): void
//     {
//         $normalized = $this->normalizePath($path);
//         $methods = $method === '*' ? array_keys($this->routes) : [$method];

//         foreach ($methods as $m) {
//             $this->beforeFilters[$m][$normalized][] = $filter;
//         }
//     }

//     /**
//      * Register an after-filter for routes
//      *
//      * @param string   $method HTTP method or '*' for all
//      * @param string   $path   Path pattern
//      * @param callable $filter Callback to run after the route is handled
//      */
//     public function after(string $method, string $path, callable $filter): void
//     {
//         $normalized = $this->normalizePath($path);
//         $methods = $method === '*' ? array_keys($this->routes) : [$method];

//         foreach ($methods as $m) {
//             $this->afterFilters[$m][$normalized][] = $filter;
//         }
//     }

//     /**
//      * Router Execution & Request Dispatching
//      */

//     /**
//      * Main router entry point with comprehensive routing logic
//      */
//     public function run(): void
//     {
//         try {
//             // Initialize request processing
//             $this->initializeRequest();
            
//             // Get normalized URI and method
//             $uri = $this->getRequestUri();
//             $method = $this->getRequestMethod();
            
//             // Handle HTTPS redirection if enabled
//             if ($this->enableHttpsRedirect && !$this->isHttps()) {
//                 $this->redirectToHttps();
//                 return;
//             }
            
//             // Detect and set language
//             $this->detectAndSetLanguage();
            
//             // Remove language prefix from URI if present
//             $uri = $this->removeLanguagePrefix($uri);
            
//             // Support ?q= style dynamic routing
//             if (isset($_GET['q'])) {
//                 $uri = $this->handleDynamicQuery($_GET['q']);
//             }
            
//             // Skip routing for static assets
//             if ($this->isStaticAsset($uri)) {
//                 $this->handleStaticAsset($uri);
//                 return;
//             }
            
//             // Store current route info
//             $this->currentRoute = [
//                 'uri' => $uri,
//                 'method' => $method,
//                 'timestamp' => microtime(true)
//             ];
            
//             // Try multiple routing strategies in order of preference
//             if ($this->tryParameterizedRoutes($method, $uri)) return;
//             if ($this->tryExactRoutes($method, $uri)) return;
//             if ($this->tryWildcardRoutes($method, $uri)) return;
//             if ($this->tryApiRoutes($method, $uri)) return;
//             if ($this->tryModuleRoutes($method, $uri)) return;
//             if ($this->tryControllerRoutes($method, $uri)) return;
//             if ($this->tryResourceRoutes($method, $uri)) return;
            
//             // Final fallback to MVC
//             $this->fallbackMVC($uri);
            
//         } catch (Exception $e) {
//             $this->handleRouterException($e);
//         } finally {
//             $this->saveRouteCache();
//             $this->logMetrics();
//         }
//     }

//     /**
//      * Initialize request processing
//      */
//     private function initializeRequest(): void
//     {
//         // Set default headers for API responses
//         if ($this->isApiRequest()) {
//             header('Content-Type: application/json');
//             header('Access-Control-Allow-Origin: *');
//             header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
//             header('Access-Control-Allow-Headers: Content-Type, Authorization');
//         }
        
//         // Handle preflight OPTIONS requests
//         if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//             http_response_code(200);
//             exit;
//         }
//     }

//     /**
//      * Get normalized request URI
//      */
//     private function getRequestUri(): string
//     {
//         $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//         return $this->normalizePath($uri);
//     }

//     /**
//      * Get request method with override support
//      */
//     private function getRequestMethod(): string
//     {
//         $method = $_SERVER['REQUEST_METHOD'];
        
//         // Support method override via _method parameter
//         if ($method === 'POST' && isset($_POST['_method'])) {
//             $method = strtoupper($_POST['_method']);
//         }
        
//         // Support method override via X-HTTP-Method-Override header
//         if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
//             $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
//         }
        
//         return $method;
//     }

//     /**
//      * Try parameterized routes (highest priority)
//      */
//     private function tryParameterizedRoutes(string $method, string $uri): bool
//     {
//         if (!$this->enableRouteParameters || !isset($this->routes[$method])) {
//             return false;
//         }

//         foreach ($this->routes[$method] as $routePattern => $handler) {
//             if (strpos($routePattern, '{') === false) continue;
            
//             $regex = $this->convertRoutePatternToRegex($routePattern, $paramNames);
//             if (preg_match($regex, $uri, $matches)) {
//                 array_shift($matches);
//                 $params = array_combine($paramNames, $matches) ?: [];
                
//                 $this->executeRoute($method, $routePattern, $handler, $params);
//                 $this->metrics['routes_matched']++;
//                 return true;
//             }
//         }
        
//         return false;
//     }

//     /**
//      * Try exact route matches
//      */
//     private function tryExactRoutes(string $method, string $uri): bool
//     {
//         if (isset($this->routes[$method][$uri])) {
//             $handler = $this->routes[$method][$uri];
//             $this->executeRoute($method, $uri, $handler);
//             $this->metrics['routes_matched']++;
//             return true;
//         }
        
//         return false;
//     }

//     /**
//      * Try wildcard routes
//      */
//     private function tryWildcardRoutes(string $method, string $uri): bool
//     {
//         if (!isset($this->routes[$method])) return false;
        
//         foreach ($this->routes[$method] as $pattern => $handler) {
//             if (strpos($pattern, '*') !== false) {
//                 $regex = str_replace('*', '.*', preg_quote($pattern, '#'));
//                 if (preg_match("#^{$regex}$#", $uri)) {
//                     $this->executeRoute($method, $pattern, $handler);
//                     $this->metrics['routes_matched']++;
//                     return true;
//                 }
//             }
//         }
        
//         return false;
//     }

//     /**
//      * Try API-specific routing
//      */
//     private function tryApiRoutes(string $method, string $uri): bool
//     {
//         if (!preg_match('#^/api(/.*)?$#', $uri, $matches)) {
//             return false;
//         }
        
//         $apiPath = $matches[1] ?? '/';
//         $this->metrics['fallbacks_used']++;
        
//         return $this->resolveApiController($method, $apiPath);
//     }

//     /**
//      * Try module-based routing
//      */
//     private function tryModuleRoutes(string $method, string $uri): bool
//     {
//         $segments = explode('/', trim($uri, '/'));
//         if (empty($segments[0])) return false;
        
//         $possibleModules = ['admin', 'ceo', 'api', 'app'];
        
//         if (in_array($segments[0], $possibleModules)) {
//             $this->metrics['fallbacks_used']++;
//             return $this->resolveModuleController($method, $segments);
//         }
        
//         return false;
//     }

//     /**
//      * Try direct controller resolution
//      */
//     private function tryControllerRoutes(string $method, string $uri): bool
//     {
//         $segments = array_filter(explode('/', trim($uri, '/')));
//         if (empty($segments)) return false;
        
//         $controllerName = ucfirst($segments[0]);
//         $action = $segments[1] ?? 'index';
        
//         $controllerFile = DIR_CONTROLLERS . "Public/{$controllerName}.php";
//         if (is_readable($controllerFile)) {
//             $this->metrics['fallbacks_used']++;
//             return $this->executeController('Public', $controllerName, $action, array_slice($segments, 2));
//         }
        
//         return false;
//     }

//     /**
//      * Try RESTful resource routing
//      */
//     private function tryResourceRoutes(string $method, string $uri): bool
//     {
//         $segments = array_filter(explode('/', trim($uri, '/')));
//         if (count($segments) < 2) return false;
        
//         $resource = $segments[0];
//         $id = $segments[1];
        
//         $methodActionMap = [
//             'GET' => is_numeric($id) ? 'show' : 'index',
//             'POST' => 'store',
//             'PUT' => 'update',
//             'PATCH' => 'update',
//             'DELETE' => 'destroy'
//         ];
        
//         if (isset($methodActionMap[$method])) {
//             $controllerName = ucfirst($resource);
//             $action = $methodActionMap[$method];
            
//             $controllerFile = DIR_CONTROLLERS . "Public/{$controllerName}.php";
//             if (is_readable($controllerFile)) {
//                 $this->metrics['fallbacks_used']++;
//                 $params = is_numeric($id) ? [$id] : [];
//                 return $this->executeController('Public', $controllerName, $action, $params);
//             }
//         }
        
//         return false;
//     }

//     /**
//      * Execute a matched route with full lifecycle
//      */
//     private function executeRoute(string $method, string $pattern, $handler, array $params = []): void
//     {
//         // Run before filters
//         $this->runBeforeFilters($method, $pattern);
        
//         // Execute the handler
//         $result = $this->callHandler($handler, $params);
        
//         // Run after filters
//         $this->runAfterFilters($method, $pattern);
        
//         // Handle result if needed
//         $this->handleRouteResult($result);
//     }

//     /**
//      * Fallback Routing, Dynamic Queries & Path Utilities
//      */

//     /**
//      * Enhanced MVC fallback with better error handling
//      */
//     private function fallbackMVC(string $uri): void
//     {
//         $segments = array_map('strtolower', array_filter(explode('/', trim($uri, '/'))));
        
//         // Default configuration
//         $module = 'Public';
//         $controller = 'Home';
//         $action = 'index';
//         $params = [];
        
//         // Parse segments based on different patterns
//         if (!empty($segments)) {
//             // Check for module prefix
//             if (in_array($segments[0], ['admin', 'ceo', 'api', 'app'])) {
//                 $module = ucfirst($segments[0]);
//                 $controller = ucfirst($segments[1] ?? 'Dashboard');
//                 $action = $segments[2] ?? 'index';
//                 $params = array_slice($segments, 3);
//             } else {
//                 $controller = ucfirst($segments[0]);
//                 $action = $segments[1] ?? 'index';
//                 $params = array_slice($segments, 2);
//             }
//         }
        
//         // Convert dash-case to underscore for PHP methods
//         $action = str_replace('-', '_', $action);
        
//         // Try multiple controller resolution strategies
//         if ($this->tryControllerResolution($module, $controller, $action, $params)) {
//             return;
//         }
        
//         // Try alternative controller names
//         $alternatives = [
//             $controller . 'Controller',
//             str_replace('Controller', '', $controller),
//             ucfirst(strtolower($controller))
//         ];
        
//         foreach ($alternatives as $altController) {
//             if ($this->tryControllerResolution($module, $altController, $action, $params)) {
//                 return;
//             }
//         }
        
//         // Final fallback strategies
//         $this->tryFinalFallbacks($uri, $segments);
//     }

//     /**
//      * Try to resolve and execute a controller
//      */
//     private function tryControllerResolution(string $module, string $controller, string $action, array $params = []): bool
//     {
//         $controllerFile = DIR_CONTROLLERS . "{$module}/{$controller}.php";
//         $className = "Controller{$module}{$controller}";
        
//         if (!is_readable($controllerFile)) {
//             return false;
//         }
        
//         require_once $controllerFile;
        
//         if (!class_exists($className)) {
//             return false;
//         }
        
//         $instance = new $className($this->registry);
        
//         // Try the requested action
//         if (method_exists($instance, $action)) {
//             call_user_func_array([$instance, $action], $params);
//             return true;
//         }
        
//         // Try common action alternatives
//         $actionAlternatives = [
//             $action . 'Action',
//             'handle' . ucfirst($action),
//             'process' . ucfirst($action)
//         ];
        
//         foreach ($actionAlternatives as $altAction) {
//             if (method_exists($instance, $altAction)) {
//                 call_user_func_array([$instance, $altAction], $params);
//                 return true;
//             }
//         }
        
//         // Fallback to index if available
//         if (method_exists($instance, 'index')) {
//             $instance->index();
//             return true;
//         }
        
//         return false;
//     }

//     /**
//      * Execute a controller method
//      */
//     private function executeController(string $module, string $controller, string $action, array $params = []): bool
//     {
//         return $this->tryControllerResolution($module, $controller, $action, $params);
//     }

//     /**
//      * Resolve API controllers
//      */
//     private function resolveApiController(string $method, string $path): bool
//     {
//         $segments = array_filter(explode('/', trim($path, '/')));
        
//         if (empty($segments)) {
//             $controller = 'Api';
//             $action = 'index';
//         } else {
//             $controller = ucfirst($segments[0]) . 'Api';
//             $action = strtolower($method) . ucfirst($segments[1] ?? 'index');
//         }
        
//         return $this->executeController('Api', $controller, $action, array_slice($segments, 2));
//     }

//     /**
//      * Resolve module controllers
//      */
//     private function resolveModuleController(string $method, array $segments): bool
//     {
//         $module = ucfirst($segments[0]);
//         $controller = ucfirst($segments[1] ?? 'Dashboard');
//         $action = $segments[2] ?? 'index';
//         $params = array_slice($segments, 3);
        
//         return $this->executeController($module, $controller, $action, $params);
//     }

//     /**
//      * Final fallback strategies
//      */
//     private function tryFinalFallbacks(string $uri, array $segments): void
//     {
//         // Try search controller with the URI as query
//         if ($this->trySearchFallback($uri)) return;
        
//         // Try catch-all controller
//         if ($this->tryCatchAllController($uri, $segments)) return;
        
//         // Try custom error pages
//         if ($this->tryCustomErrorPage($uri)) return;
        
//         // Final 404
//         $this->serve404("Route not found: $uri");
//     }

//     /**
//      * Try search controller fallback
//      */
//     private function trySearchFallback(string $uri): bool
//     {
//         $searchFile = DIR_CONTROLLERS . 'Public/Search.php';
//         if (is_readable($searchFile)) {
//             require_once $searchFile;
//             $search = new ControllerPublicSearch($this->registry);
//             if (method_exists($search, 'query')) {
//                 $search->query(ltrim($uri, '/'));
//                 return true;
//             }
//         }
//         return false;
//     }

//     /**
//      * Try catch-all controller
//      */
//     private function tryCatchAllController(string $uri, array $segments): bool
//     {
//         $catchAllFile = DIR_CONTROLLERS . 'Public/CatchAll.php';
//         if (is_readable($catchAllFile)) {
//             require_once $catchAllFile;
//             $catchAll = new ControllerPublicCatchAll($this->registry);
//             if (method_exists($catchAll, 'handle')) {
//                 $catchAll->handle($uri, $segments);
//                 return true;
//             }
//         }
//         return false;
//     }

//     /**
//      * Try custom error page
//      */
//     private function tryCustomErrorPage(string $uri): bool
//     {
//         $errorFile = DIR_CONTROLLERS . 'Public/Error.php';
//         if (is_readable($errorFile)) {
//             require_once $errorFile;
//             $error = new ControllerPublicError($this->registry);
//             if (method_exists($error, 'notFound')) {
//                 $error->notFound($uri);
//                 return true;
//             }
//         }
//         return false;
//     }

//     /**
//      * Enhanced dynamic query handler
//      */
//     private function handleDynamicQuery(string $q): string
//     {
//         $q = trim($q, "\"' \t\n\r\0\x0B");
//         $q = preg_replace('/[^\w\-\/]/', '', $q);
//         $path = strtolower(trim($q, '/'));
        
//         if (empty($path)) return '/';
        
//         $segments = explode('/', $path);
        
//         // Check for direct controller match
//         $controller = ucfirst($segments[0]);
//         $controllerFile = DIR_CONTROLLERS . "Public/{$controller}.php";
        
//         if (is_readable($controllerFile)) {
//             return '/' . $path;
//         }
        
//         // Check for module-based routing
//         if (in_array($segments[0], ['admin', 'ceo', 'api', 'app'])) {
//             $module = ucfirst($segments[0]);
//             $controller = ucfirst($segments[1] ?? 'Dashboard');
//             $moduleControllerFile = DIR_CONTROLLERS . "{$module}/{$controller}.php";
            
//             if (is_readable($moduleControllerFile)) {
//                 return '/' . $path;
//             }
//         }
        
//         // Fallback to search with the original query
//         return '/search/' . rawurlencode($q);
//     }

//     /**
//      * Normalize path with enhanced cleaning
//      */
//     private function normalizePath(string $path): string
//     {
//         $path = trim($path);
//         $path = preg_replace('#/{2,}#', '/', $path);
//         $path = rtrim($path, '/');
        
//         if (empty($path) || $path[0] !== '/') {
//             $path = '/' . ltrim($path, '/');
//         }
        
//         if ($path === '/') return $path;
        
//         return $path;
//     }

//     /**
//      * Router Utilities, Route Registration, and Error Handling
//      */

//     /**
//      * Run before filters with pattern matching
//      */
//     private function runBeforeFilters(string $method, string $path): void
//     {
//         if (empty($this->beforeFilters[$method])) return;
        
//         foreach ($this->beforeFilters[$method] as $pattern => $filters) {
//             if ($this->matchesPattern($path, $pattern)) {
//                 foreach ($filters as $filter) {
//                     $this->executeFilter($filter);
//                 }
//             }
//         }
        
//         // Run group middleware
//         foreach ($this->routeGroupMiddleware as $middleware) {
//             foreach ($middleware as $filter) {
//                 $this->executeFilter($filter);
//             }
//         }
//     }

//     /**
//      * Run after filters
//      */
//     private function runAfterFilters(string $method, string $path): void
//     {
//         if (empty($this->afterFilters[$method])) return;
        
//         foreach ($this->afterFilters[$method] as $pattern => $filters) {
//             if ($this->matchesPattern($path, $pattern)) {
//                 foreach ($filters as $filter) {
//                     $this->executeFilter($filter);
//                 }
//             }
//         }
//     }

//     /**
//      * Execute a filter/middleware
//      */
//     private function executeFilter($filter): void
//     {
//         if (is_callable($filter)) {
//             $filter($this->registry->get('request'));
//         } elseif (is_string($filter) && strpos($filter, '::') !== false) {
//             list($class, $method) = explode('::', $filter);
//             if (class_exists($class) && method_exists($class, $method)) {
//                 $class::$method($this->registry->get('request'));
//             }
//         }
//     }

//     /**
//      * Check if path matches pattern (supports wildcards)
//      */
//     private function matchesPattern(string $path, string $pattern): bool
//     {
//         if ($path === $pattern) return true;
        
//         $regex = str_replace('*', '.*', preg_quote($pattern, '#'));
//         return (bool) preg_match("#^{$regex}$#", $path);
//     }

//     /**
//      * Convert route pattern to regex with enhanced parameter support
//      */
//     private function convertRoutePatternToRegex(string $pattern, array &$paramNames): string
//     {
//         $paramNames = [];
//         $normalized = $this->normalizePath($pattern);
        
//         // Support different parameter patterns
//         $patterns = [
//             '/\{(\w+)\}/' => '([^/]+)',           // {id}
//             '/\{(\w+)\?\}/' => '?([^/]*)',        // {id?} - optional
//             '/\{(\w+):([^}]+)\}/' => '($2)',      // {id:\d+} - with regex
//         ];
        
//         $regexBody = $normalized;
//         foreach ($patterns as $search => $replace) {
//             $regexBody = preg_replace_callback($search, function($matches) use (&$paramNames, $replace) {
//                 $paramNames[] = $matches[1];
//                 return str_replace('$2', $matches[2] ?? '[^/]+', $replace);
//             }, $regexBody);
//         }
        
//         return '#^' . $regexBody . '$#';
//     }

//     /**
//      * Generate URL from named route with caching
//      */
//     public function url(string $name, array $params = []): string
//     {
//         if (!$this->enableUrlGeneration) {
//             throw new Exception("URL generation is disabled.");
//         }
        
//         $cacheKey = "url_{$name}_" . md5(serialize($params));
//         if ($this->cacheEnabled && isset($this->routeCache[$cacheKey])) {
//             $this->metrics['cache_hits']++;
//             return $this->routeCache[$cacheKey];
//         }
        
//         if (!isset($this->namedRoutes[$name])) {
//             throw new Exception("Named route [$name] not found.");
//         }
        
//         $path = $this->namedRoutes[$name]['path'];
        
//         foreach ($params as $key => $value) {
//             $path = str_replace('{' . $key . '}', urlencode($value), $path);
//         }
        
//         $url = $this->normalizePath($path);
        
//         if ($this->cacheEnabled) {
//             $this->routeCache[$cacheKey] = $url;
//         }
        
//         return $url;
//     }

//     /**
//      * Enhanced handler calling with better DI
//      */
//     private function callHandler($handler, array $params = [])
//     {
//         if (is_string($handler) && strpos($handler, '@') !== false) {
//             list($controller, $method) = explode('@', $handler);
//             $handler = [$this->resolveController($controller), $method];
//         }
        
//         if ($this->enableDI) {
//             $dependencies = $this->resolveDependencies($handler);
//             return call_user_func_array($handler, array_merge($dependencies, array_values($params)));
//         }
        
//         if (!empty($params)) {
//             return call_user_func_array($handler, array_values($params));
//         }
        
//         return call_user_func($handler);
//     }

//     /**
//      * Resolve controller instance
//      */
//     private function resolveController(string $controller)
//     {
//         if (class_exists($controller)) {
//             return new $controller($this->registry);
//         }
        
//         throw new Exception("Controller [$controller] not found");
//     }

//     /**
//      * Resolve handler dependencies
//      */
//     private function resolveDependencies($handler): array
//     {
//         $dependencies = [];
        
//         // Add common dependencies
//         if ($this->registry->has('request')) {
//             $dependencies[] = $this->registry->get('request');
//         }
        
//         if ($this->registry->has('response')) {
//             $dependencies[] = $this->registry->get('response');
//         }
        
//         return $dependencies;
//     }

//     /**
//      * Handle route execution result
//      */
//     private function handleRouteResult($result): void
//     {
//         if (is_array($result) || is_object($result)) {
//             header('Content-Type: