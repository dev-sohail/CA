<?php

/**
 * Class SessionHijackProtector
 *
 * Protects against session hijacking by binding session to IP and User-Agent.
 * Can also regenerate session ID periodically.
 */
class SessionHijackProtector
{
    protected array $config = [
        'check_ip'          => true,
        'check_user_agent'  => true,
        'regenerate_time'   => 300, // seconds (e.g., 5 minutes)
    ];

    protected $session;

    public function __construct($session, array $config = [])
    {
        $this->session = $session;
        $this->config = array_merge($this->config, $config);

        $this->initialize();
    }

    protected function initialize(): void
    {
        if (!isset($this->session->data['_secure'])) {
            $this->session->data['_secure'] = [
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created'    => time(),
                'last_regen' => time(),
            ];
        }

        if (!$this->isValid()) {
            $this->session->destroy();
            session_regenerate_id(true);
            $this->session->start(); // you may want to restart session explicitly
            $this->initialize();     // re-bind secure keys
        } elseif ($this->shouldRegenerate()) {
            session_regenerate_id(true);
            $this->session->data['_secure']['last_regen'] = time();
        }
    }

    /**
     * Validate current session against hijack attempts.
     */
    protected function isValid(): bool
    {
        $secure = $this->session->data['_secure'];

        if ($this->config['check_ip'] && ($_SERVER['REMOTE_ADDR'] ?? '') !== $secure['ip']) {
            return false;
        }

        if ($this->config['check_user_agent'] && ($_SERVER['HTTP_USER_AGENT'] ?? '') !== $secure['user_agent']) {
            return false;
        }

        return true;
    }

    /**
     * Determine if session ID should be regenerated.
     */
    protected function shouldRegenerate(): bool
    {
        return time() - $this->session->data['_secure']['last_regen'] > $this->config['regenerate_time'];
    }
}

// $protector = new SessionHijackProtector($registry->get('session'));
// $protector = new SessionHijackProtector($registry->get('session'), [
//     'check_ip' => true,
//     'check_user_agent' => true,
//     'regenerate_time' => 600, // regenerate every 10 minutes
// ]);
