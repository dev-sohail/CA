<?php
/**
 * Base Controller Class
 * 
 * All application controllers should extend this class
 */

namespace System\Core;

abstract class Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Database
     */
    protected $db;

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
        $this->db = new Database();
    }

    /**
     * Render a view
     * 
     * @param string $view
     * @param array $data
     * @return string
     */
    protected function render($view, $data = [])
    {
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = APP_PATH . "/views/{$view}.php";
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new \Exception("View file not found: {$viewFile}");
        }
        
        // Get the buffered content
        $content = ob_get_clean();
        
        return $content;
    }

    /**
     * Render a view with layout
     * 
     * @param string $view
     * @param array $data
     * @param string $layout
     */
    protected function renderWithLayout($view, $data = [], $layout = 'default')
    {
        // Get the view content
        $content = $this->render($view, $data);
        
        // Include the layout
        $layoutFile = APP_PATH . "/views/layouts/{$layout}.php";
        
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            // If no layout, just echo the content
            echo $content;
        }
    }

    /**
     * Redirect to a URL
     * 
     * @param string $url
     */
    protected function redirect($url)
    {
        $this->response->redirect($url);
    }

    /**
     * Send JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json($data, $statusCode = 200)
    {
        $this->response->json($data, $statusCode);
    }

    /**
     * Get POST data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getPost($key = null, $default = null)
    {
        return $this->request->getPost($key, $default);
    }

    /**
     * Get GET data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getQuery($key = null, $default = null)
    {
        return $this->request->getQuery($key, $default);
    }

    /**
     * Get session data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSession($key = null, $default = null)
    {
        return $this->request->getSession($key, $default);
    }

    /**
     * Set session data
     * 
     * @param string $key
     * @param mixed $value
     */
    protected function setSession($key, $value)
    {
        $this->request->setSession($key, $value);
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    protected function isLoggedIn()
    {
        return $this->getSession('logged_in') === true;
    }

    /**
     * Get current user role
     * 
     * @return string|null
     */
    protected function getUserRole()
    {
        return $this->getSession('role');
    }

    /**
     * Get current username
     * 
     * @return string|null
     */
    protected function getUsername()
    {
        return $this->getSession('user');
    }

    /**
     * Require authentication
     * 
     * @param string $role
     */
    protected function requireAuth($role = null)
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        if ($role && $this->getUserRole() !== $role) {
            $this->redirect('/auth/login');
        }
    }
} 