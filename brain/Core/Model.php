<?php

abstract class Model
{
    protected $registry;
    protected $db;

    public function __construct($registry)
    {
        $this->registry = $registry;

        // Optional: access to DB if preconfigured in registry
        if (isset($this->registry->db)) {
            $this->db = $this->registry->db;
        }
    }

    /**
     * Get a service from the registry
     */
    public function __get($key)
    {
        return $this->registry->$key;
    }

    /**
     * Set a service or shared property in registry
     */
    public function __set($key, $value)
    {
        $this->registry->$key = $value;
    }
}
