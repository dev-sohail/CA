<?php

final class Registry
{
    /**
     * Internal storage for registered items
     *
     * @var array<string, mixed>
     */
    private array $data = [];


    /**
     * Retrieve an item by key
     *
     * @param string $key The key to fetch
     * @return mixed|null Returns stored value or null if key not found
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Store an item by key
     *
     * @param string $key   The key under which the value will be stored
     * @param mixed  $value The value to store
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Check if a key exists in the registry
     *
     * @param string $key The key to check
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}
