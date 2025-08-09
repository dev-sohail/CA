<?php
/**
 * Application Configuration
 */


// Application settings
define('APP_NAME', 'SMS Framework');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/SMS_frame');
define('APP_ENV', 'development'); // development, production

// Debug settings
define('DEBUG', APP_ENV === 'development');

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session settings
// ini_set('session.cookie_httponly', 1);
// ini_set('session.use_only_cookies', 1);
// ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
if (ini_get('register_globals')) {
    ini_set('session.use_cookies', 'On');
    ini_set('session.use_trans_sid', 'Off');
    session_set_cookie_params(0, '/');
    session_start();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $globals = array($_REQUEST, $_SESSION, $_SERVER, $_FILES);
    foreach ($globals as $global) {
        foreach (array_keys($global) as $key) {
            unset(${$key});
        }
    }
}

// Timezone
date_default_timezone_set('UTC');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'sms_session');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Pagination settings
// define('ITEMS_PER_PAGE', 10);

// Cache settings
define('CACHE_ENABLED', false);
define('CACHE_DURATION', 3600); // 1 hour

$http = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/';
define('HTTP_HOST', $http);


// Email settings
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_ADDRESS', 'noreply@sms.com');
define('MAIL_FROM_NAME', 'SMS System');

// Application paths
define('APP_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/SMS_frame');
// define('APP_HOST_ROOT', $_SERVER['HTTP_HOST'] . '/SMS_frame');

// URL paths
define('APP_ROOT_URL', '/index.php');
define('APP_PUBLIC_URL', APP_ROOT_URL . '/public');
define('APP_VIEWS_URL', APP_ROOT_URL . '/app/views');
define('APP_AUTH_URL', APP_VIEWS_URL . '/auth');
define('APP_PORTALS_URL', APP_VIEWS_URL . '/portals');

// Portal URLs
define('APP_TPORTAL_URL', APP_PORTALS_URL . '/tportal');
define('APP_SPORTAL_URL', APP_PORTALS_URL . '/sportal');
define('APP_STPORTAL_URL', APP_PORTALS_URL . '/stportal');
define('APP_PPORTAL_URL', APP_PORTALS_URL . '/pportal');
define('APP_ADMIN_URL', APP_SPORTAL_URL . '/admin');

// Asset URLs
define('APP_CSS_URL', APP_PUBLIC_URL . '/css');
define('APP_JS_URL', APP_PUBLIC_URL . '/js');
define('APP_IMAGES_URL', APP_PUBLIC_URL . '/images');
define('APP_UPLOADS_URL', APP_PUBLIC_URL . '/uploads'); 