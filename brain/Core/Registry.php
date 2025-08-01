<?php
    // final class Registry
    // {
    //     /**
    //      * Internal storage for registered items
    //      *
    //      * @var array<string, mixed>
    //      */
    //     private array $data = [];

    //     /**
    //      * Retrieve an item by key
    //      *
    //      * @param string $key The key to fetch
    //      * @return mixed|null Returns stored value or null if key not found
    //      */
    //     public function get(string $key)
    //     {
    //         return $this->data[$key] ?? null;
    //     }

    //     /**
    //      * Store an item by key
    //      *
    //      * @param string $key   The key under which the value will be stored
    //      * @param mixed  $value The value to store
    //      * @return void
    //      */
    //     public function set(string $key, $value): void
    //     {
    //         $this->data[$key] = $value;
    //     }

    //     /**
    //      * Check if a key exists in the registry
    //      *
    //      * @param string $key The key to check
    //      * @return bool True if key exists, false otherwise
    //      */
    //     public function has(string $key): bool
    //     {
    //         return array_key_exists($key, $this->data);
    //     }
    // }

    /**
     * Enhanced Registry Pattern Implementation
     *
     * A comprehensive service container that provides:
     * - Singleton pattern for global accessibility
     * - Service registration and retrieval
     * - Lazy loading with closures
     * - Singleton service management
     * - Bootstrap functionality for common services
     * - Type safety and error handling
     *
     * @package CyberAfridi Framework
     */
    final class Registry
    {
        /**
         * Singleton instance
         */
        private static ?Registry $instance = null;

        /**
         * Registered services storage
         * 
         * @var array<string, mixed>
         */
        private array $services = [];

        /**
         * Instantiated singleton services cache
         * 
         * @var array<string, mixed>
         */
        private array $singletons = [];

        /**
         * Service aliases for easier access
         * 
         * @var array<string, string>
         */
        private array $aliases = [];

        /**
         * Private constructor to prevent direct instantiation
         */
        private function __construct() {}

        /**
         * Prevent cloning of the instance
         */
        private function __clone() {}

        /**
         * Prevent unserialization of the instance
         */
        public function __wakeup(): void
        {
            throw new \Exception("Cannot unserialize a singleton.");
        }

        /**
         * Get singleton instance
         * 
         * @return Registry
         */
        public static function getInstance(): Registry
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Register a service
         * 
         * @param string $name The service name
         * @param mixed $value The service value or factory closure
         * @return Registry Fluent interface
         */
        public function set(string $name, $value): Registry
        {
            $this->services[$name] = $value;
            return $this;
        }

        /**
         * Get a service
         * 
         * @param string $name The service name
         * @return mixed
         * @throws \Exception When service is not found
         */
        public function get(string $name)
        {
            // Check for alias
            $serviceName = $this->aliases[$name] ?? $name;

            if (!isset($this->services[$serviceName])) {
                throw new \Exception("Service '{$name}' not found in registry");
            }

            $service = $this->services[$serviceName];

            // If it's a closure, execute it
            if ($service instanceof \Closure) {
                return $service();
            }

            return $service;
        }

        /**
         * Try to get a service without throwing exception
         * 
         * @param string $name The service name
         * @param mixed $default Default value if service not found
         * @return mixed
         */
        public function tryGet(string $name, $default = null)
        {
            try {
                return $this->get($name);
            } catch (\Exception $e) {
                return $default;
            }
        }

        /**
         * Register a singleton service
         * 
         * @param string $name The service name
         * @param mixed $factory The factory closure or value
         * @return Registry Fluent interface
         */
        public function singleton(string $name, $factory): Registry
        {
            $this->services[$name] = function () use ($name, $factory) {
                if (!isset($this->singletons[$name])) {
                    $this->singletons[$name] = $factory instanceof \Closure ? $factory() : $factory;
                }

                return $this->singletons[$name];
            };

            return $this;
        }

        /**
         * Create an alias for a service
         * 
         * @param string $alias The alias name
         * @param string $serviceName The actual service name
         * @return Registry Fluent interface
         */
        public function alias(string $alias, string $serviceName): Registry
        {
            $this->aliases[$alias] = $serviceName;
            return $this;
        }

        /**
         * Check if service exists
         * 
         * @param string $name The service name
         * @return bool
         */
        public function has(string $name): bool
        {
            $serviceName = $this->aliases[$name] ?? $name;
            return isset($this->services[$serviceName]);
        }

        /**
         * Remove a service and its singleton instance
         * 
         * @param string $name The service name
         * @return Registry Fluent interface
         */
        public function remove(string $name): Registry
        {
            unset($this->services[$name]);
            unset($this->singletons[$name]);

            // Remove any aliases pointing to this service
            $this->aliases = array_filter($this->aliases, fn($service) => $service !== $name);

            return $this;
        }

        /**
         * Get all registered service names
         * 
         * @return array<string>
         */
        public function getServices(): array
        {
            return array_keys($this->services);
        }

        /**
         * Get all service aliases
         * 
         * @return array<string, string>
         */
        public function getAliases(): array
        {
            return $this->aliases;
        }

        /**
         * Clear all services and singletons
         * 
         * @return Registry Fluent interface
         */
        public function clear(): Registry
        {
            $this->services = [];
            $this->singletons = [];
            $this->aliases = [];
            return $this;
        }

        /**
         * Check if a service is instantiated as singleton
         * 
         * @param string $name The service name
         * @return bool
         */
        public function isInstantiated(string $name): bool
        {
            return isset($this->singletons[$name]);
        }

        /**
         * Bootstrap common services
         * 
         * @param array $config Configuration array
         * @return Registry Fluent interface
         */
        public function bootstrap(array $config = []): Registry
        {
            // Register configuration first
            $this->set('config', $config);

            // Register database connection if config provided
            if (isset($config['database'])) {
                $this->singleton('database', function () use ($config) {
                    $dbConfig = $config['database'];
                    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";

                    return new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                });

                // Create alias for easier access
                $this->alias('db', 'database');
            }

            // Register request object
            $this->singleton('request', function () {
                return new class {
                    public function get(string $key, $default = null)
                    {
                        return $_GET[$key] ?? $default;
                    }

                    public function post(string $key, $default = null)
                    {
                        return $_POST[$key] ?? $default;
                    }

                    public function server(string $key, $default = null)
                    {
                        return $_SERVER[$key] ?? $default;
                    }

                    public function method(): string
                    {
                        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
                    }

                    public function uri(): string
                    {
                        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
                    }
                };
            });

            // Register response object
            $this->singleton('response', function () {
                return new class {
                    public function json(array $data, int $status = 200): void
                    {
                        http_response_code($status);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                    }

                    public function redirect(string $url, int $status = 302): void
                    {
                        http_response_code($status);
                        header("Location: $url");
                        exit;
                    }

                    public function status(int $code): self
                    {
                        http_response_code($code);
                        return $this;
                    }
                };
            });

            // Register logger if configured
            if (isset($config['log'])) {
                $this->singleton('logger', function () use ($config) {
                    return new class($config['log']) {
                        private string $logFile;

                        public function __construct(array $config)
                        {
                            $this->logFile = $config['file'] ?? 'app.log';
                        }

                        public function log(string $level, string $message): void
                        {
                            $timestamp = date('Y-m-d H:i:s');
                            $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
                            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
                        }

                        public function info(string $message): void
                        {
                            $this->log('INFO', $message);
                        }
                        public function error(string $message): void
                        {
                            $this->log('ERROR', $message);
                        }
                        public function warning(string $message): void
                        {
                            $this->log('WARNING', $message);
                        }
                        public function debug(string $message): void
                        {
                            $this->log('DEBUG', $message);
                        }
                    };
                });
            }

            // Register cache if configured
            if (isset($config['cache'])) {
                $this->singleton('cache', function () use ($config) {
                    $cacheConfig = $config['cache'];

                    if ($cacheConfig['driver'] === 'file') {
                        return new class($cacheConfig) {
                            private string $cacheDir;

                            public function __construct(array $config)
                            {
                                $this->cacheDir = $config['path'] ?? sys_get_temp_dir() . '/cache';
                                if (!is_dir($this->cacheDir)) {
                                    mkdir($this->cacheDir, 0755, true);
                                }
                            }

                            public function get(string $key, $default = null)
                            {
                                $file = $this->cacheDir . '/' . md5($key) . '.cache';
                                if (file_exists($file)) {
                                    $data = unserialize(file_get_contents($file));
                                    if ($data['expires'] > time()) {
                                        return $data['value'];
                                    }
                                    unlink($file);
                                }
                                return $default;
                            }

                            public function set(string $key, $value, int $ttl = 3600): void
                            {
                                $file = $this->cacheDir . '/' . md5($key) . '.cache';
                                $data = ['value' => $value, 'expires' => time() + $ttl];
                                file_put_contents($file, serialize($data), LOCK_EX);
                            }

                            public function delete(string $key): void
                            {
                                $file = $this->cacheDir . '/' . md5($key) . '.cache';
                                if (file_exists($file)) {
                                    unlink($file);
                                }
                            }
                        };
                    }

                    return null;
                });
            }

            return $this;
        }

        /**
         * Magic method to get services as properties
         * 
         * @param string $name
         * @return mixed
         */
        public function __get(string $name)
        {
            return $this->get($name);
        }

        /**
         * Magic method to check if service exists
         * 
         * @param string $name
         * @return bool
         */
        public function __isset(string $name): bool
        {
            return $this->has($name);
        }
    }

    // Usage Examples:
    /*
    // Basic usage
    $registry = Registry::getInstance();

    // Register simple values
    $registry->set('app_name', 'My Application')
            ->set('debug', true);

    // Register with factory closure
    $registry->set('mailer', function() {
        return new PHPMailer();
    });

    // Register singleton
    $registry->singleton('database', function() {
        return new PDO('sqlite:database.db');
    });

    // Create aliases
    $registry->alias('db', 'database');

    // Bootstrap with configuration
    $config = [
        'database' => [
            'host' => 'localhost',
            'database' => 'myapp',
            'username' => 'user',
            'password' => 'pass'
        ],
        'cache' => [
            'driver' => 'file',
            'path' => '/tmp/cache'
        ],
        'log' => [
            'file' => 'logs/app.log'
        ]
    ];

    $registry->bootstrap($config);

    // Access services
    $db = $registry->get('database'); // or $registry->db
    $cache = $registry->cache;
    $logger = $registry->logger;

    // Use services
    $logger->info('Application started');
    $cache->set('user_1', ['name' => 'John'], 3600);
    $user = $cache->get('user_1');
    */