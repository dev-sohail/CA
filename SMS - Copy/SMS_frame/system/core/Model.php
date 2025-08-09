<?php
/**
 * Base Model Class
 * 
 * All application models should extend this class
 */

namespace System\Core;

abstract class Model
{
    /**
     * @var Database
     */
    protected $db;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $fillable = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Find a record by ID
     * 
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Find all records
     * 
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function findAll($orderBy = null, $direction = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Find records by condition
     * 
     * @param string $where
     * @param array $params
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function findBy($where, $params = [], $orderBy = null, $direction = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$where}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Find a single record by condition
     * 
     * @param string $where
     * @param array $params
     * @return array|false
     */
    public function findOne($where, $params = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1";
        return $this->db->fetch($sql, $params);
    }

    /**
     * Create a new record
     * 
     * @param array $data
     * @return int
     */
    public function create($data)
    {
        // Filter data to only include fillable fields
        $data = $this->filterFillable($data);
        
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update a record
     * 
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update($id, $data)
    {
        // Filter data to only include fillable fields
        $data = $this->filterFillable($data);
        
        return $this->db->update($this->table, $data, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    /**
     * Delete a record
     * 
     * @param int $id
     * @return int
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    /**
     * Count records
     * 
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count($where = null, $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] ?? 0;
    }

    /**
     * Filter data to only include fillable fields
     * 
     * @param array $data
     * @return array
     */
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Execute a custom query
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query($sql, $params = [])
    {
        return $this->db->query($sql, $params);
    }

    /**
     * Execute a custom query and fetch all results
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function queryAll($sql, $params = [])
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Execute a custom query and fetch single result
     * 
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function queryOne($sql, $params = [])
    {
        return $this->db->fetch($sql, $params);
    }
} 