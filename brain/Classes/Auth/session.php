<?php
/**
 * Class Session
 *
 * Simple wrapper around PHP sessions, enabling access to session data
 * and configuration in a controlled manner.
 *
 * Example:
 * ```php
 * $session = new Session();
 * $session->data['user_id'] = 5;
 * $userId = $session->data['user_id'];
 * $sessionId = $session->getId();
 * ```
 */
class Session {
    /**
     * Stores session data, linked to $_SESSION.
     *
     * @var array
     */
    public array $data = [];

    /**
     * Initializes the PHP session and links `$this->data` to `$_SESSION`.
     * Ensures cookies are used and disables URL-based session IDs.
     */
    public function __construct() {
        if (!session_id()) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }

        $this->data =& $_SESSION;
    }

    /**
     * Returns the current session ID.
     *
     * @return string
     */
    public function getId(): string {
        return session_id();
    }

    /**
     * Destroys the current session and clears session data.
     */
    public function destroy(): void {
        if (session_id()) {
            session_unset();
            session_destroy();
            $this->data = [];
        }
    }
}
