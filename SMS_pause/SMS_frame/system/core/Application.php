<?php
/**
 * Application Class
 * 
 * Main application class that handles routing and controller execution
 */

namespace System\Core;

class Application
{
    /**
     * @var Router
     */
    protected $router;

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
    protected $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->database = new Database();
    }

    /**
     * Run the application
     */
    public function run()
    {
        try {
            // Parse the URL
            $url = $this->request->getUrl();
            
            // Route the request
            $this->router->dispatch($url);
            
        } catch (\Exception $e) {
            // Handle errors
            $this->handleError($e);
        }
    }

    /**
     * Handle application errors
     * 
     * @param \Exception $e
     */
    protected function handleError(\Exception $e)
    {
        // Log the error
        error_log($e->getMessage());
        
        // Show error page in development
        if (defined('DEBUG') && DEBUG) {
            echo '<h1>Application Error</h1>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        } else {
            // Show generic error in production
            $this->response->setStatusCode(500);
            echo '<h1>Internal Server Error</h1>';
        }
    }

    /**
     * Get the router instance
     * 
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Get the database instance
     * 
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }
} 