<?php

/**
 * Class User
 *
 * Handles all user authentication, session management, and permission loading.
 *
 * Responsibilities:
 * - Login: Authenticates users using secure password hashing with bcrypt fallback to legacy methods
 * - Session: Maintains login state using PHP sessions with security measures
 * - Permission: Loads and verifies user permissions based on user group
 * - Logout: Clears session and resets user context
 * - Security: Rate limiting, IP validation, and secure password handling
 *
 * Usage:
 * - Used in admin or public modules to check if a user is logged in
 *   and whether they have access to certain routes or features.
 *
 * Example:
 *   $user = new User($registry);
 *   if ($user->isLogged()) {
 *       // User is authenticated
 *   }
 *   if ($user->hasPermission('module', 'edit')) {
 *       // User has edit access to module
 *   }
 *
 * Dependencies:
 * - $registry with 'db', 'request', and 'session' services.
 */
class User
{
    private $db;
    private $request;
    private $session;

    private int $user_id = 0;
    private string $username = '';
    private int $user_group_id = 0;
    private array $permission = [];
    private ?string $email = null;
    private ?DateTime $last_login = null;
    
    // Security constants
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes
    private const SESSION_TIMEOUT = 7200; // 2 hours

    /**
     * Initialize User with dependencies and check session login.
     */
    public function __construct($registry)
    {
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');

        $this->initializeSession();
        
        if (!empty($this->session->data['user_id'])) {
            $this->validateSession();
            if ($this->isSessionValid()) {
                $this->loadUser((int)$this->session->data['user_id']);
            }
        }
    }

    /**
     * Initialize secure session settings
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_strict_mode', '1');
        }
    }

    /**
     * Validate current session for security
     */
    private function validateSession(): void
    {
        $current_ip = $this->request->server['REMOTE_ADDR'] ?? '';
        $session_ip = $this->session->data['user_ip'] ?? '';
        $last_activity = $this->session->data['last_activity'] ?? 0;

        // Check for session hijacking
        if ($session_ip !== $current_ip) {
            $this->logout();
            return;
        }

        // Check session timeout
        if (time() - $last_activity > self::SESSION_TIMEOUT) {
            $this->logout();
            return;
        }

        // Update last activity
        $this->session->data['last_activity'] = time();
    }

    /**
     * Check if current session is valid
     */
    private function isSessionValid(): bool
    {
        return !empty($this->session->data['user_id']) && 
               !empty($this->session->data['user_ip']) &&
               !empty($this->session->data['last_activity']);
    }

    /**
     * Attempt to authenticate a user with rate limiting and security checks.
     */
    public function login(string $username, string $password): bool
    {
        // Input validation
        if (empty($username) || empty($password)) {
            return false;
        }

        // Check rate limiting
        if (!$this->checkRateLimit($username)) {
            error_log("Login rate limit exceeded for user: $username");
            return false;
        }

        // Sanitize username
        $username = trim($username);
        if (strlen($username) > 255) {
            return false;
        }

        // Check if account is locked
        if ($this->isAccountLocked($username)) {
            return false;
        }

        // Prepare statement to prevent SQL injection
        $stmt = $this->db->prepare("
            SELECT user_id, username, password, salt, user_group_id, email, status, failed_attempts, locked_until
            FROM " . DB_PREFIX . "user 
            WHERE username = ? AND status = '1'
        ");
        
        if (!$stmt) {
            error_log("Database prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->recordFailedAttempt($username);
            return false;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password using multiple methods
        if ($this->verifyPassword($password, $user['password'], $user['salt'] ?? '')) {
            // Reset failed attempts
            $this->resetFailedAttempts($username);
            
            // Set secure session
            $this->setSecureSession($user);
            
            // Load user data
            $this->loadUser((int)$user['user_id']);
            
            // Update last login
            $this->updateLastLogin($user['user_id']);
            
            return true;
        }

        // Record failed attempt
        $this->recordFailedAttempt($username);
        return false;
    }

    /**
     * Verify password using multiple hashing methods
     */
    private function verifyPassword(string $password, string $hash, string $salt = ''): bool
    {
        // Modern bcrypt verification (preferred)
        if (password_verify($password, $hash)) {
            return true;
        }

        // Legacy SHA1 verification (OpenCart style)
        if (!empty($salt) && $hash === $this->legacySHA1($salt, $password)) {
            return true;
        }

        // MD5 fallback (least secure)
        if ($hash === md5($password)) {
            return true;
        }

        // Plain text fallback (very insecure, should be upgraded)
        if ($hash === $password) {
            return true;
        }

        return false;
    }

    /**
     * Set secure session data
     */
    private function setSecureSession(array $user): void
    {
        session_regenerate_id(true);
        
        $this->session->data['user_id'] = $user['user_id'];
        $this->session->data['user_ip'] = $this->request->server['REMOTE_ADDR'] ?? '';
        $this->session->data['last_activity'] = time();
        $this->session->data['session_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Check rate limiting for login attempts
     */
    private function checkRateLimit(string $username): bool
    {
        $ip = $this->request->server['REMOTE_ADDR'] ?? '';
        $current_time = time();
        
        // Check IP-based rate limiting
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM " . DB_PREFIX . "login_attempts 
            WHERE ip = ? AND attempt_time > ?
        ");
        
        if ($stmt) {
            $time_threshold = $current_time - 300; // 5 minutes
            $stmt->bind_param('si', $ip, $time_threshold);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row['attempts'] >= 10) { // Max 10 attempts per IP in 5 minutes
                return false;
            }
        }

        return true;
    }

    /**
     * Check if account is locked
     */
    private function isAccountLocked(string $username): bool
    {
        $stmt = $this->db->prepare("
            SELECT failed_attempts, locked_until 
            FROM " . DB_PREFIX . "user 
            WHERE username = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                
                return !empty($row['locked_until']) && 
                       strtotime($row['locked_until']) > time();
            }
            $stmt->close();
        }
        
        return false;
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $username): void
    {
        $ip = $this->request->server['REMOTE_ADDR'] ?? '';
        $current_time = time();
        
        // Log attempt in login_attempts table
        $stmt = $this->db->prepare("
            INSERT INTO " . DB_PREFIX . "login_attempts (username, ip, attempt_time) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param('ssi', $username, $ip, $current_time);
            $stmt->execute();
            $stmt->close();
        }

        // Update user failed attempts
        $stmt = $this->db->prepare("
            UPDATE " . DB_PREFIX . "user 
            SET failed_attempts = failed_attempts + 1,
                locked_until = CASE 
                    WHEN failed_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                    ELSE locked_until 
                END
            WHERE username = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('iis', self::MAX_LOGIN_ATTEMPTS, self::LOCKOUT_TIME, $username);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Reset failed attempts on successful login
     */
    private function resetFailedAttempts(string $username): void
    {
        $stmt = $this->db->prepare("
            UPDATE " . DB_PREFIX . "user 
            SET failed_attempts = 0, locked_until = NULL 
            WHERE username = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin(int $user_id): void
    {
        $ip = $this->request->server['REMOTE_ADDR'] ?? '';
        
        $stmt = $this->db->prepare("
            UPDATE " . DB_PREFIX . "user 
            SET last_login = NOW(), ip = ? 
            WHERE user_id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('si', $ip, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Load user details and permissions securely
     */
    private function loadUser(int $user_id): void
    {
        $stmt = $this->db->prepare("
            SELECT user_id, username, user_group_id, email, last_login 
            FROM " . DB_PREFIX . "user 
            WHERE user_id = ? AND status = '1'
        ");
        
        if (!$stmt) {
            $this->logout();
            return;
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            $this->logout();
            return;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        $this->user_id = (int)$user['user_id'];
        $this->username = $user['username'];
        $this->user_group_id = (int)$user['user_group_id'];
        $this->email = $user['email'];
        $this->last_login = $user['last_login'] ? new DateTime($user['last_login']) : null;

        $this->loadPermissions($this->user_group_id);
    }

    /**
     * Load permissions from user group with validation
     */
    private function loadPermissions(int $group_id): void
    {
        $stmt = $this->db->prepare("
            SELECT permission 
            FROM " . DB_PREFIX . "user_group 
            WHERE user_group_id = ?
        ");
        
        if (!$stmt) {
            error_log("Failed to prepare permissions query");
            return;
        }

        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!empty($row['permission'])) {
                $permissions = @unserialize($row['permission']);
                
                if ($permissions === false && $row['permission'] !== 'b:0;') {
                    // Try JSON decode as fallback
                    $permissions = json_decode($row['permission'], true);
                }
                
                if (is_array($permissions)) {
                    $this->permission = $this->sanitizePermissions($permissions);
                } else {
                    error_log("Failed to unserialize permissions for group ID: $group_id");
                    $this->permission = [];
                }
            }
        } else {
            $stmt->close();
        }
    }

    /**
     * Sanitize permissions array to prevent injection
     */
    private function sanitizePermissions(array $permissions): array
    {
        $sanitized = [];
        
        foreach ($permissions as $key => $values) {
            $clean_key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            
            if (is_array($values)) {
                $sanitized[$clean_key] = array_map(function($value) {
                    return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
                }, $values);
            }
        }
        
        return $sanitized;
    }

    /**
     * Legacy SHA1 password hash (OpenCart style) - for backward compatibility
     */
    private function legacySHA1(string $salt, string $password): string
    {
        return sha1($salt . sha1($salt . sha1($password)));
    }

    /**
     * Hash password using modern bcrypt
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Two-factor authentication verification placeholder
     */
    public function verify(string $code = ''): bool
    {
        // Implement 2FA verification logic here
        // For now, return true (no 2FA required)
        return true;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $key, string $value): bool
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
        $value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
        
        return isset($this->permission[$key]) && 
               is_array($this->permission[$key]) && 
               in_array($value, $this->permission[$key], true);
    }

    /**
     * Check if user has any permission for a key
     */
    public function hasAnyPermission(string $key): bool
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
        return isset($this->permission[$key]) && 
               is_array($this->permission[$key]) && 
               !empty($this->permission[$key]);
    }

    /**
     * Get all permissions for a key
     */
    public function getPermissions(string $key): array
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
        return $this->permission[$key] ?? [];
    }

    /**
     * Is the user logged in?
     */
    public function isLogged(): bool
    {
        return $this->user_id > 0;
    }

    /**
     * Get current user ID
     */
    public function getId(): int
    {
        return $this->user_id;
    }

    /**
     * Get current username
     */
    public function getUserName(): string
    {
        return $this->username;
    }

    /**
     * Get user email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get user group ID
     */
    public function getUserGroupId(): int
    {
        return $this->user_group_id;
    }

    /**
     * Get last login time
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->last_login;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->user_group_id === 1; // Assuming group ID 1 is admin
    }

    /**
     * Log out user and clear session state securely
     */
    public function logout(): void
    {
        // Clear session data
        if (isset($this->session->data['user_id'])) {
            unset($this->session->data['user_id']);
        }
        if (isset($this->session->data['user_ip'])) {
            unset($this->session->data['user_ip']);
        }
        if (isset($this->session->data['last_activity'])) {
            unset($this->session->data['last_activity']);
        }
        if (isset($this->session->data['session_token'])) {
            unset($this->session->data['session_token']);
        }

        // Reset user properties
        $this->user_id = 0;
        $this->username = '';
        $this->user_group_id = 0;
        $this->permission = [];
        $this->email = null;
        $this->last_login = null;

        // Regenerate session ID to prevent session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Clean up old login attempts and sessions
     */
    public function cleanup(): void
    {
        // Clean old login attempts (older than 24 hours)
        $stmt = $this->db->prepare("
            DELETE FROM " . DB_PREFIX . "login_attempts 
            WHERE attempt_time < ?
        ");
        
        if ($stmt) {
            $cutoff = time() - 86400; // 24 hours ago
            $stmt->bind_param('i', $cutoff);
            $stmt->execute();
            $stmt->close();
        }

        // Unlock accounts that have passed their lockout time
        $stmt = $this->db->prepare("
            UPDATE " . DB_PREFIX . "user 
            SET locked_until = NULL, failed_attempts = 0 
            WHERE locked_until IS NOT NULL AND locked_until < NOW()
        ");
        
        if ($stmt) {
            $stmt->execute();
            $stmt->close();
        }
    }
}