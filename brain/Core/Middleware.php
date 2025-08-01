<?php

abstract class Middleware
{
    protected $registry;

    public function __construct($registry = null)
    {
        $this->registry = $registry;
    }

    /**
     * Handle the middleware logic.
     * Must be implemented in concrete middleware classes.
     *
     * @param callable $next
     * @return mixed
     */
    abstract public function handle(callable $next);
}
