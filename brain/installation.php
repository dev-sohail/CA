<?php
/**
 * CyberAfridi Framework Installation System
 * Handles initial application setup and configuration
 */

// Prevent direct access in production
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/..'));
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Configuration paths
$configFile = ROOT . DIRECTORY_SEPARATOR . '.env';
$lockFile = ROOT . DIRECTORY_SEPARATOR . 'installed.lock';

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

/**
 * Redirect to main application if already installed
 */
if (file_exists($lockFile)) {
    header('Location: /', true, 302);
    exit('Installation already completed. Redirecting...');
}

/**
 * Create .env file if it doesn't exist
 */
if (!file_exists($configFile)) {
    $envDir = dirname($configFile);
    if (!is_dir($envDir)) {
        mkdir($envDir, 0755, true);
    }
    
    $handle = fopen($configFile, 'w');
    if ($handle === false) {
        die('Error: Could not create .env file. Please check directory permissions.');
    }
    fclose($handle);
}

/**
 * Validation and sanitization functions
 */
function validateInput($input, $type = 'string') {
    $input = trim($input);
    
    switch ($type) {
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) !== false ? $input : '';
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false ? $input : '';
        case 'hostname':
            return preg_match('/^[a-zA-Z0-9.-]+$/', $input) ? $input : '';
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

function generateSecureKey($length = 32) {
    return base64_encode(random_bytes($length));
}

function testDatabaseConnection($host, $user, $pass, $name, $port = 3306) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return ['success' => true, 'message' => 'Connection successful'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

$errors = [];
$success = false;

/**
 * Process installation form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $dbHost = validateInput($_POST['db_host'] ?? '', 'hostname');
        $dbPort = filter_var($_POST['db_port'] ?? 3306, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) ?: 3306;
        $dbUser = validateInput($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = validateInput($_POST['db_name'] ?? '');
        $siteTitle = validateInput($_POST['site_title'] ?? '');
        $siteCategory = validateInput($_POST['site_category'] ?? '');
        $siteIcon = validateInput($_POST['site_icon'] ?? '', 'url');
        $adminEmail = validateInput($_POST['admin_email'] ?? '', 'email');
        $appUrl = validateInput($_POST['app_url'] ?? '', 'url');

        // Validation
        if (empty($dbHost)) $errors[] = 'Database host is required.';
        if (empty($dbUser)) $errors[] = 'Database username is required.';
        if (empty($dbName)) $errors[] = 'Database name is required.';
        if (empty($siteTitle)) $errors[] = 'Site title is required.';
        if (empty($adminEmail)) $errors[] = 'Valid admin email is required.';
        if (empty($appUrl)) $errors[] = 'Valid application URL is required.';

        // Test database connection
        if (empty($errors)) {
            $dbTest = testDatabaseConnection($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if (!$dbTest['success']) {
                $errors[] = 'Database connection failed: ' . htmlspecialchars($dbTest['message']);
            }
        }

        // Generate configuration if no errors
        if (empty($errors)) {
            $appKey = generateSecureKey();
            $appSecret = generateSecureKey();
            $jwtSecret = generateSecureKey();
            
    $envContent = <<<ENV
#########################################
# CyberAfridi Custom Framework Settings
#########################################

APP_NAME={$siteTitle}
APP_ENV=production
SITE_CATEGORY={$siteCategory}
# DEV_MODE=0
APP_DEBUG=false
APP_URL=https://myawesomeapp.com
SITE_ICON={$siteIcon}
APP_PORT=8080
APP_KEY=base64:ReplaceWithGeneratedKey
APP_SECRET=supersecretstring
APP_TYPE=general #general, e-commerance, managment-system
TIMEZONE=UTC
LOCALE=en

# =========================================
# HTTP CONFIGURATION
# =========================================
FORCE_HTTPS=
#if empty => http else https

# =========================================
# DATABASE CONFIGURATION
# =========================================
# DB_CONNECTION=pgsql
# DB_HOST=db
# DB_PORT=5432
# DB_DATABASE=myapp_db
# DB_USERNAME=myapp_user
# DB_PASSWORD=supersecurepassword
DB_DRIVER=mysql
DB_HOSTNAME={$dbHost}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPass}
DB_DATABASE={$dbName}
DB_PREFIX=

# Secondary/Read Replica (Optional)
DB_READ_HOST=replica-db
DB_READ_PORT=5432
DB_READ_USERNAME=readonly
DB_READ_PASSWORD=readonlypass

# =========================================
# REDIS / CACHE CONFIG
# =========================================
# CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
SESSION_NAME=caframework_session
CACHE_DRIVER=file
CACHE_TTL=3600

# =========================================
# MAIL SETTINGS
# =========================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@myawesomeapp.com
MAIL_FROM_NAME="MyAwesomeApp"
CONFIG_SMTP_TIMEOUT=30

# =========================================
# LOGGING & ERROR TRACKING
# =========================================
LOG_CHANNEL=stack
# LOG_LEVEL=info
MEMORY_LIMIT=256M
TIME_LIMIT=3600
LOG_LEVEL=debug
LOG_FILE=storage/logs/error.log
SENTRY_DSN=https://examplePublicKey@o0.ingest.sentry.io/0
SENTRY_ENVIRONMENT=production

# =========================================
# CLOUD STORAGE (S3, MinIO, etc)
# =========================================
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=myapp-bucket
AWS_URL=https://myapp-bucket.s3.amazonaws.com
AWS_ENDPOINT=https://s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# =========================================
# PAYMENT GATEWAYS
# =========================================
STRIPE_KEY=pk_test_123456789
STRIPE_SECRET=sk_test_abcdefg
PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-secret
PAYMENT_CURRENCY=USD

# =========================================
# OAUTH / SOCIAL AUTH
# =========================================
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
FACEBOOK_CLIENT_ID=your-fb-client-id
FACEBOOK_CLIENT_SECRET=your-fb-client-secret
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
OAUTH_REDIRECT_URI=https://myawesomeapp.com/oauth/callback

# =========================================
# THIRD-PARTY SERVICES
# =========================================
RECAPTCHA_SITE_KEY=your-recaptcha-site-key
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key
SENDGRID_API_KEY=your-sendgrid-api-key
TWILIO_ACCOUNT_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
TWILIO_PHONE_NUMBER=+1234567890

# =========================================
# SECURITY
# =========================================
JWT_SECRET=supersecretjwtkey
JWT_TTL=3600
# SESSION_LIFETIME=120
SESSION_LIFETIME=7200
CORS_ALLOWED_ORIGINS=https://frontend.myawesomeapp.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE
CORS_ALLOWED_HEADERS=Authorization,Content-Type

# =========================================
# FEATURE FLAGS
# =========================================
FEATURE_REGISTRATION=true
FEATURE_BETA_ACCESS=false
FEATURE_TWO_FACTOR_AUTH=true

# =========================================
# FRONTEND / SPA INTEGRATION
# =========================================
FRONTEND_URL=https://frontend.myawesomeapp.com
API_URL=https://api.myawesomeapp.com

# =========================================
# DOCKER / KUBERNETES
# =========================================
DOCKER_ENV=true
K8S_NAMESPACE=myapp-prod
CONFIG_RELOAD_TOKEN=optional_reload_token

# =========================================
# CI / CD INTEGRATIONS
# =========================================
GITHUB_TOKEN=ghp_xxx
CI_BRANCH=main
CI_COMMIT_SHA=abc123def456
CI_DEPLOY_ENV=production

# =========================================
# MONITORING / APM
# =========================================
DATADOG_API_KEY=your-datadog-key
NEW_RELIC_LICENSE_KEY=your-newrelic-license-key
PROMETHEUS_ENABLED=true
HEALTHCHECK_URL=https://myawesomeapp.com/health
ENV;

            // Save configuration file
            if (file_put_contents($configFile, $envContent, LOCK_EX) !== false) {
                // Set secure permissions
                chmod($configFile, 0600);
                
                // Create installation lock file
                $lockContent = json_encode([
                    'installed_at' => date('c'),
                    'version' => '1.0.0',
                    'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                if (file_put_contents($lockFile, $lockContent, LOCK_EX) !== false) {
                    chmod($lockFile, 0644);
                    $_SESSION['installation_completed'] = true;
                    $success = true;
                } else {
                    $errors[] = 'Could not create installation lock file.';
                }
            } else {
                $errors[] = 'Could not write configuration file. Please check permissions.';
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-redirect after successful installation
if ($success) {
    echo '<script>
        setTimeout(function() {
            window.location.href = "/";
        }, 3000);
    </script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CyberAfridi Framework - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .installation-container { margin-top: 2rem; margin-bottom: 2rem; }
        .card { box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; }
        .card-header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .btn-primary { background: linear-gradient(45deg, #667eea, #764ba2); border: none; }
        .btn-primary:hover { background: linear-gradient(45deg, #5a67d8, #6b46c1); }
        .success-message { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .form-label { font-weight: 600; }
        .required { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container installation-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            CyberAfridi Framework Installation
                        </h2>
                        <p class="mb-0 mt-2">Configure your application settings</p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success success-message text-center" role="alert">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <h4>Installation Completed Successfully!</h4>
                                <p>Your application has been configured and is ready to use.</p>
                                <p><small>Redirecting to your application in 3 seconds...</small></p>
                                <a href="/" class="btn btn-success">Go to Application</a>
                            </div>
                        <?php else: ?>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="row">
                                    <!-- Database Configuration -->
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Database Configuration</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="db_host" class="form-label">Database Host <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="db_host" name="db_host" 
                                                           value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" 
                                                           placeholder="localhost" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="db_port" class="form-label">Database Port</label>
                                                    <input type="number" class="form-control" id="db_port" name="db_port" 
                                                           value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306'); ?>" 
                                                           min="1" max="65535">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="db_user" class="form-label">Database Username <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="db_user" name="db_user" 
                                                           value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="db_pass" class="form-label">Database Password</label>
                                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                                    <div class="form-text">Leave blank if no password is required</div>
                                                </div>
                                                
                                                <div class="mb-0">
                                                    <label for="db_name" class="form-label">Database Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="db_name" name="db_name" 
                                                           value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Application Configuration -->
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Application Settings</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="site_title" class="form-label">Application Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="site_title" name="site_title" 
                                                           value="<?php echo htmlspecialchars($_POST['site_title'] ?? ''); ?>" 
                                                           placeholder="My Awesome App" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="app_url" class="form-label">Application URL <span class="required">*</span></label>
                                                    <input type="url" class="form-control" id="app_url" name="app_url" 
                                                           value="<?php echo htmlspecialchars($_POST['app_url'] ?? 'https://'); ?>" 
                                                           placeholder="https://myawesomeapp.com" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="site_category" class="form-label">Site Category</label>
                                                    <input type="text" class="form-control" id="site_category" name="site_category" 
                                                           value="<?php echo htmlspecialchars($_POST['site_category'] ?? ''); ?>" 
                                                           placeholder="Business, Blog, Portfolio, etc.">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="admin_email" class="form-label">Administrator Email <span class="required">*</span></label>
                                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                                           value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" 
                                                           placeholder="admin@myawesomeapp.com" required>
                                                </div>
                                                
                                                <div class="mb-0">
                                                    <label for="site_icon" class="form-label">Site Icon URL</label>
                                                    <input type="url" class="form-control" id="site_icon" name="site_icon" 
                                                           value="<?php echo htmlspecialchars($_POST['site_icon'] ?? ''); ?>" 
                                                           placeholder="https://example.com/icon.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-rocket me-2"></i>
                                        Install Application
                                    </button>
                                </div>
                            </form>
                            
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>