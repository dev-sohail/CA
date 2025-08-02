<?php

class Database
{
    private ?PDO $connection = null;
    private ?PDOStatement $statement = null;
    private array $log = [];

    /**
     * Connect to the database on construct.
     */
    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $config = [
                'hostname' => DB_HOST,
                'username' => DB_USERNAME,
                'password' => DB_PASSWORD,
                'database' => DB_DATABASE,
                'port'     => DB_PORT,
            ];
        }
        $hostname = $config['hostname'];
        $username = $config['username'];
        $password = $config['password'];
        $database = $config['database'];
        $port     = $config['port'];

        try {
            $dsn = "mysql:host={$hostname};port={$port};dbname={$database};charset=utf8mb4";
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_PERSISTENT         => true,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to connect to database: ' . $e->getMessage());
        }
    }


    public function prepare(string $sql): void
    {
        $this->ensureConnected();
        $this->statement = $this->connection->prepare($sql);
    }

    public function bindParam(string|int $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR, int $length = 0): void
    {
        $this->ensureStatementPrepared();
        if ($length > 0) {
            $this->statement->bindParam($parameter, $variable, $data_type, $length);
        } else {
            $this->statement->bindParam($parameter, $variable, $data_type);
        }
    }

    public function bindValue(string|int $parameter, mixed $value, int $data_type = PDO::PARAM_STR): void
    {
        $this->ensureStatementPrepared();
        $this->statement->bindValue($parameter, $value, $data_type);
    }

    public function execute(): object
    {
        $this->ensureStatementPrepared();
        $this->statement->execute();
        return $this->formatResult($this->statement);
    }

    public function query(string $sql, array $params = []): object
    {
        $this->ensureConnected();
        $stmt = $this->connection->prepare($sql);
        $this->logQuery($sql, $params);
        $stmt->execute($params);
        return $this->formatResult($stmt);
    }

    public function run(string $sql, array $params = []): int
    {
        $this->ensureConnected();
        $stmt = $this->connection->prepare($sql);
        $this->logQuery($sql, $params);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function escape(string $value): string
    {
        $this->ensureConnected();
        return substr($this->connection->quote($value), 1, -1);
    }

    public function countAffected(): int
    {
        return $this->statement ? $this->statement->rowCount() : 0;
    }

    public function getLastId(): string
    {
        $this->ensureConnected();
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->ensureConnected();
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->ensureConnected();
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->ensureConnected();
        $this->connection->rollBack();
    }

    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Destructor to cleanup.
     */
    public function __destruct()
    {
        $this->statement = null;
        $this->connection = null;
    }

    // -------------------------------
    // Internal Utility Methods
    // -------------------------------

    private function formatResult(PDOStatement $stmt): object
    {
        $rows = $stmt->fetchAll();
        return (object)[
            'row'      => $rows[0] ?? [],
            'rows'     => $rows,
            'num_rows' => count($rows),
        ];
    }

    private function logQuery(string $sql, array $params): void
    {
        $this->log[] = [
            'query' => $sql,
            'params' => $params,
            'time' => microtime(true)
        ];
    }

    private function ensureConnected(): void
    {
        if (!$this->connection) {
            throw new LogicException('No database connection.');
        }
    }

    private function ensureStatementPrepared(): void
    {
        if (!$this->statement) {
            throw new LogicException('No SQL statement prepared.');
        }
    }
}
