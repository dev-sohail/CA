<?php

abstract class Controller
{
    protected $registry;
    protected $data = [];

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * Load a model from a module
     */
    protected function loadModel($role, $module, $name)
    {
        $path = DIR_MODULES . $role . '/' . $module . '/models/' . $name . '.php';

        if (is_readable($path)) {
            require_once($path);
            $class = ucfirst($name);
            if (class_exists($class)) {
                return new $class($this->registry);
            }
        }

        throw new Exception("Model not found: $path");
    }

    /**
     * Load a view from a module
     */
    protected function loadView($role, $module, $name, $data = [])
    {
        $path = DIR_MODULES . $role . '/' . $module . '/view/' . $name . '.php';

        if (is_readable($path)) {
            extract($data);
            require($path);
        } else {
            throw new Exception("View not found: $path");
        }
    }

    /**
     * Load another controller inside a module (optional)
     */
    protected function loadController($role, $module, $name)
    {
        $path = DIR_MODULES . $role . '/' . $module . '/controllers/' . $name . '.php';

        if (is_readable($path)) {
            require_once($path);
            $class = ucfirst($name);
            if (class_exists($class)) {
                return new $class($this->registry);
            }
        }

        throw new Exception("Controller not found: $path");
    }

    /**
     * Load module-specific routes manually (if needed)
     */
    protected function loadRoutes($role, $module)
    {
        $path = DIR_MODULES . $role . '/' . $module . '/routes.php';
        if (is_readable($path)) {
            require_once($path);
        }
    }

    /**
     * Set a variable for view usage
     */
    protected function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Render and pass all data to a view
     */
    protected function render($role, $module, $view)
    {
        $this->loadView($role, $module, $view, $this->data);
    }
}
