<?php
/**
 * Router Class
 * 
 * Handles URL routing and controller method calls
 */

namespace System\Core;

class Router
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Constructor
     * 
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->loadRoutes();
    }

    /**
     * Load application routes
     */
    protected function loadRoutes()
    {
        // Define routes
        $this->routes = [
            // Home page
            'GET' => [
                '/' => ['HomeController', 'index'],
                '/home' => ['HomeController', 'index'],
                '/about' => ['HomeController', 'about'],
                '/auth/login' => ['AuthController', 'login'],
                '/auth/register' => ['AuthController', 'register'],
                '/auth/logout' => ['AuthController', 'logout'],
                '/portal/teacher' => ['PortalController', 'teacher'],
                '/portal/student' => ['PortalController', 'student'],
                '/portal/parent' => ['PortalController', 'parent'],
                '/portal/staff' => ['PortalController', 'staff'],
                '/portal/admin' => ['PortalController', 'admin'],
            ],
            
            // POST routes
            'POST' => [
                '/auth/login' => ['AuthController', 'loginPost'],
                '/auth/register' => ['AuthController', 'registerPost'],
                '/api/attendance' => ['ApiController', 'attendance'],
                '/api/notices' => ['ApiController', 'notices'],
                '/api/timetable' => ['ApiController', 'timetable'],
            ]
        ];
    }

    /**
     * Dispatch the request to the appropriate controller
     * 
     * @param string $url
     * @return mixed
     */
    public function dispatch($url)
    {
        $method = $this->request->getMethod();
        $path = parse_url($url, PHP_URL_PATH);
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        
        // Check if route exists
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            $controllerName = $route[0];
            $actionName = $route[1];
            
            // Create controller instance
            $controllerClass = "App\\Controllers\\{$controllerName}";
            
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass($this->request, $this->response);
                
                // Check if method exists
                if (method_exists($controller, $actionName)) {
                    return $controller->$actionName();
                } else {
                    throw new \Exception("Method {$actionName} not found in {$controllerName}");
                }
            } else {
                throw new \Exception("Controller {$controllerName} not found");
            }
        } else {
            // For POST requests, try to find a matching GET route and show error
            if ($method === 'POST') {
                $this->response->setStatusCode(405);
                echo '<h1>Method Not Allowed</h1>';
                echo '<p>The requested method is not allowed for this URL.</p>';
                return;
            }
            
            // Route not found
            $this->response->setStatusCode(404);
            return $this->render404();
        }
    }

    /**
     * Render 404 page
     */
    protected function render404()
    {
        http_response_code(404);
        include APP_PATH . '/views/errors/404.php';
    }

    /**
     * Add a new route
     * 
     * @param string $method
     * @param string $path
     * @param array $handler
     */
    public function addRoute($method, $path, $handler)
    {
        $this->routes[$method][$path] = $handler;
    }
} 