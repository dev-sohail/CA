<?php
/**
 * Simple PDO-based MySQL Database wrapper class.
 *
 * Features:
 * - Persistent connection with error mode exception
 * - Prepared statement support with bindParam and execute
 * - Query method for direct queries with parameters
 * - Returns results as object with 'row', 'rows', 'num_rows'
 * - Provides escaping, affected rows count, last insert ID
 * - Connection health check and cleanup on destruction
 *
 * Usage:
 *  $db = new Database([
 *      'driver'   => 'mysql',
 *      'host'     => 'localhost',
 *      'port'     => '3306',
 *      'database' => 'dbname',
 *      'username' => 'user',
 *      'password' => 'pass',
 *      'charset'  => 'utf8mb4',
 *      'prefix'   => ''
 *  ]);
 *  
 *  $result = $db->query('SELECT * FROM users WHERE id = ?', [1]);
 *  echo $result->row['username'];
 *
 *  $db->prepare('INSERT INTO users (name) VALUES (:name)');
 *  $name = 'John';
 *  $db->bindParam(':name', $name);
 *  $db->execute();
 */
class Database
{
    /** @var PDO|null */
    private ?PDO $connection = null;

    /** @var PDOStatement|null */
    private ?PDOStatement $statement = null;

    /** @var string */
    private string $prefix = '';

    /**
     * Construct and connect to DB
     *
     * @param array $config Database configuration
     * @throws RuntimeException if connection fails
     */
    public function __construct(array $config)
    {
        if (!empty($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to connect to database: ' . $e->getMessage());
        }
    }

    /**
     * Prepare a SQL statement
     *
     * @param string $sql SQL query with placeholders
     * @return void
     * @throws LogicException if no connection
     */
    public function prepare(string $sql): void
    {
        if ($this->connection === null) {
            throw new LogicException('No database connection.');
        }
        $this->statement = $this->connection->prepare($this->prefixTables($sql));
    }

    /**
     * Bind a parameter to the prepared statement
     *
     * @param string|int $parameter Parameter identifier
     * @param mixed &$variable Variable to bind
     * @param int $data_type PDO data type, default PARAM_STR
     * @param int $length Length for data type, default 0 (no length)
     * @return void
     * @throws LogicException if statement not prepared
     */
    public function bindParam(string|int $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR, int $length = 0): void
    {
        if ($this->statement === null) {
            throw new LogicException('No statement prepared.');
        }
        if ($length > 0) {
            $this->statement->bindParam($parameter, $variable, $data_type, $length);
        } else {
            $this->statement->bindParam($parameter, $variable, $data_type);
        }
    }

    /**
     * Execute the prepared statement and get results
     *
     * @return object Result object with row, rows, num_rows
     * @throws LogicException if statement not prepared
     */
    public function execute(): object
    {
        if ($this->statement === null) {
            throw new LogicException('No statement prepared.');
        }
        $this->statement->execute();
        return $this->getResultObject($this->statement);
    }

    /**
     * Execute a query with optional parameters and return results
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters array
     * @return object Result object with row, rows, num_rows
     * @throws RuntimeException on query failure
     */
    public function query(string $sql, array $params = []): object
    {
        if ($this->connection === null) {
            throw new LogicException('No database connection.');
        }

        $stmt = $this->connection->prepare($this->prefixTables($sql));
        $stmt->execute($params);

        return $this->getResultObject($stmt);
    }

    /**
     * Format the result from a PDOStatement into an object
     *
     * @param PDOStatement $stmt
     * @return object Object with properties: row, rows, num_rows
     */
    private function getResultObject(PDOStatement $stmt): object
    {
        $rows = $stmt->fetchAll();
        return (object)[
            'row' => $rows[0] ?? [],
            'rows' => $rows,
            'num_rows' => count($rows),
        ];
    }

    /**
     * Escape a string for use in a query
     *
     * @param string $value Input string
     * @return string Escaped string (without surrounding quotes)
     * @throws LogicException if no connection
     */
    public function escape(string $value): string
    {
        if ($this->connection === null) {
            throw new LogicException('No database connection.');
        }
        return substr($this->connection->quote($value), 1, -1);
    }

    /**
     * Get number of rows affected by last query
     *
     * @return int Number of affected rows
     */
    public function countAffected(): int
    {
        return $this->statement ? $this->statement->rowCount() : 0;
    }

    /**
     * Get last inserted ID from the database
     *
     * @return string Last insert ID
     * @throws LogicException if no connection
     */
    public function getLastId(): string
    {
        if ($this->connection === null) {
            throw new LogicException('No database connection.');
        }
        return $this->connection->lastInsertId();
    }

    /**
     * Check if the connection is established
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Replace table prefix in SQL queries
     *
     * @param string $sql The SQL query
     * @return string The SQL query with prefixed tables
     */
    private function prefixTables(string $sql): string
    {
        if ($this->prefix) {
            return str_replace('`#__', '`' . $this->prefix, $sql);
        }
        return $sql;
    }

    /**
     * Destructor to close connection
     */
    public function __destruct()
    {
        $this->statement = null;
        $this->connection = null;
    }
}