<?php
/****/

    /**
     * Class Encryption
     * 
     * Provides secure AES-256-CBC encryption and decryption utilities using a shared key.
     * 
     * This class supports encryption of strings using the OpenSSL extension with a random
     * 16-byte Initialization Vector (IV) for each operation. The IV is prepended to the 
     * encrypted payload and Base64 encoded to ensure safe storage or transmission.
     * 
     * Usage:
     * 
     * ```php
     * $encryption = new \App\Core\Classes\Encryption('your-secret-key');
     * $cipherText = $encryption->encrypt('MySecretData');
     * $plainText = $encryption->decrypt($cipherText);
     * ```
     * 
     * Security:
     * - Uses AES-256-CBC cipher
     * - IV is generated randomly and stored with ciphertext
     * - Key is hashed with SHA-256 to ensure correct length
     * 
     * Requirements:
     * - OpenSSL PHP extension
     */
/****/


final class Encryption
{
    /** @var string $key A 256-bit binary encryption key derived from the user-supplied key */
    private string $key;

    /**
     * Constructor.
     * Hashes the provided key to ensure it's a 256-bit value suitable for AES-256 encryption.
     *
     * @param string $key A secret key (passphrase or token)
     */
    public function __construct(string $key = 'CT_FRAME_DEFAULT_KEY')
    {
        $this->key = hash('sha256', $key, true); // Generate a 256-bit binary key
    }

    /**
     * Encrypt a plain string using AES-256-CBC.
     *
     * @param string $value Plaintext value to encrypt
     * @return string Base64-encoded ciphertext (includes IV + encrypted data)
     */
    public function encrypt(string $value): string
    {
        $iv = random_bytes(16); // 128-bit IV
        $encrypted = openssl_encrypt(
            $value,
            'aes-256-cbc',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt an encrypted string previously encrypted by this class.
     *
     * @param string $value Base64-encoded ciphertext (IV + encrypted data)
     * @return string Decrypted plaintext, or an empty string if decryption fails
     */
    public function decrypt(string $value): string
    {
        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, 16);
        $cipherText = substr($decoded, 16);

        $decrypted = openssl_decrypt(
            $cipherText,
            'aes-256-cbc',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted !== false ? $decrypted : '';
    }
}
