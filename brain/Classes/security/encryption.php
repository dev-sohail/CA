<?php

/**
 * Class Encryption
 *
 * Handles encryption, decryption, and hashing.
 *
 * Features:
 * - AES-256-CBC encryption/decryption
 * - SHA256 hashing
 * - Password hashing and verification (BCrypt)
 * - Random token generation
 */
class Encryption
{
    private string $key;
    private string $method = 'AES-256-CBC';

    public function __construct(string $key = 'CT_Framework_Encryption_Key')
    {
        if (empty($key)) {
            throw new InvalidArgumentException("Encryption key cannot be empty.");
        }

        $this->key = hash('sha256', $key, true); // 32-byte key for AES-256
    }

    /**
     * Encrypt a string
     */
    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

        // Combine IV + encrypted data for storage
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a string
     */
    public function decrypt(string $data): string
    {
        $raw = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->method);
        $iv = substr($raw, 0, $ivLength);
        $encrypted = substr($raw, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Generate a secure random token
     */
    public function token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * SHA256 hash (e.g. for data integrity)
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Create a secure password hash (BCrypt)
     */
    public function password(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Compare two hashes securely (constant-time)
     */
    public function secureCompare(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }
}
