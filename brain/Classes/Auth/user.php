<?php

/**
 * Class User
 *
 * Combines authentication/session/permission handling (OpenCart-style)
 * with modern ORM-style features (CRUD methods, etc.)
 */
class User
{
    // Dependencies
    private $db;
    private $request;
    private $session;

    // Session/User Context
    private int $user_id = 0;
    private string $username = '';
    private int $user_group_id = 0;
    private array $permission = [];

    // ORM-style properties
    protected int $id = 0;
    protected string $name = '';
    protected string $email = '';
    protected string $password = '';
    protected string $role = 'user';

    protected static ?Database $pdo = null;

    /**
     * Constructor handles either dependency injection or data filling.
     */
    public function __construct($registryOrData = [])
    {
        if (is_array($registryOrData)) {
            $this->fill($registryOrData);
        } elseif (is_object($registryOrData)) {
            $registry = $registryOrData;
            $this->db      = $registry->get('db');
            $this->request = $registry->get('request');
            $this->session = $registry->get('session');

            if (!empty($this->session->data['user_id'])) {
                $this->loadUser((int)$this->session->data['user_id']);
            }
        }
    }

    public static function setDatabase(Database $database): void
    {
        self::$pdo = $database;
    }

    // === Auth & Session Methods ===

    public function login(string $username, string $password): bool
    {
        $username = $this->db->escape($username);
        $escapedPassword = $this->db->escape($password);

        $password_sha1 = "SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('$escapedPassword')))))";
        $password_md5 = md5($password);

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '$username' AND (
            password = $password_sha1 OR
            password = '" . $this->db->escape($password_md5) . "' OR
            password = '" . $this->db->escape($password) . "') AND status = '1'");

        if ($query->num_rows) {
            $user = $query->row;
            $this->session->data['user_id'] = $user['user_id'];
            $this->loadUser((int)$user['user_id']);
            return true;
        }

        return false;
    }

    private function loadUser(int $user_id): void
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '$user_id' AND status = '1'");
        if (!$query->num_rows) {
            $this->logout();
            return;
        }

        $user = $query->row;
        $this->user_id       = (int)$user['user_id'];
        $this->username      = $user['username'];
        $this->user_group_id = (int)$user['user_group_id'];

        $ip = $this->db->escape($this->request->server['REMOTE_ADDR'] ?? '');
        $this->db->query("UPDATE " . DB_PREFIX . "user SET ip = '$ip' WHERE user_id = '$user_id'");

        $this->loadPermissions($this->user_group_id);
    }

    private function loadPermissions(int $group_id): void
    {
        $query = $this->db->query("SELECT permission FROM " . DB_PREFIX . "user_group WHERE user_group_id = '$group_id'");
        if ($query->num_rows && $query->row['permission']) {
            $permissions = unserialize($query->row['permission']);
            if (is_array($permissions)) {
                foreach ($permissions as $key => $value) {
                    $this->permission[$key] = $value;
                }
            }
        }
    }

    public function logout(): void
    {
        unset($this->session->data['user_id']);
        $this->user_id = 0;
        $this->username = '';
        $this->user_group_id = 0;
        $this->permission = [];
    }

    public function hasPermission(string $key, string $value): bool
    {
        return isset($this->permission[$key]) && in_array($value, $this->permission[$key]);
    }

    public function isLogged(): bool
    {
        return $this->user_id > 0;
    }

    public function getId(): int
    {
        return $this->user_id ?: $this->id;
    }

    public function getUserName(): string
    {
        return $this->username ?: $this->name;
    }

    // === ORM-like Methods ===

    public function fill(array $data): void
    {
        $this->id       = $data['id']       ?? 0;
        $this->name     = $data['name']     ?? '';
        $this->email    = $data['email']    ?? '';
        $this->password = $data['password'] ?? '';
        $this->role     = $data['role']     ?? 'user';
    }

    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }

    public static function findById(int $id): ?User
    {
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    public static function findByEmail(string $email): ?User
    {
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    public static function authenticate(string $email, string $password): ?User
    {
        $user = self::findByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            return $user;
        }
        return null;
    }

    public function save(): bool
    {
        if ($this->id > 0) {
            $stmt = self::$pdo->prepare("UPDATE users SET name = :name, email = :email, password = :password, role = :role WHERE id = :id");
            return $stmt->execute([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => $this->password,
                'role'     => $this->role,
                'id'       => $this->id
            ]);
        } else {
            $stmt = self::$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
            $result = $stmt->execute([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => $this->password,
                'role'     => $this->role,
            ]);

            if ($result) {
                $this->id = (int)self::$pdo->lastInsertId();
            }
            return $result;
        }
    }

    public function delete(): bool
    {
        if ($this->id <= 0) return false;
        $stmt = self::$pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
        ];
    }
}
