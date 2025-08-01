<?php
/**
 * File Structure Reorganizer Script
 * This script reorganizes your existing PHP class files into a more logical structure
 */

class FileReorganizer {
    private $baseDir;
    private $backupDir;
    private $dryRun;
    
    // New structure mapping
    private $newStructure = [
        // Core framework files
        'Core' => [
            'app/AppKernel.php',
            'app/ServiceContainer.php',
            'app/ServiceProvider.php',
            'app/EventDispatcher.php',
            'core/App.php',
            'core/Autoloader.php',
            'core/Controller.php',
            'core/Model.php',
            'core/View.php',
            'core/Router.php',
            'core/Dispatcher.php',
            'core/ErrorHandler.php',
            'core/Profiler.php',
            'config.php'
        ],
        
        // HTTP & Web layer
        'Http' => [
            'request.php',
            'response.php',
            'middleware/Middleware.php',
            'middleware/Cookie.php',
            'middleware/Cors.php',
            'middleware/Headers.php',
            'middleware/RateLimiter.php',
            'middleware/RequestFilter.php'
        ],
        
        // Authentication & Security
        'Auth' => [
            'user.php',
            'utils/Auth.php',
            'utils/Password.php',
            'security/session.php',
            'security/BruteForceGuard.php',
            'security/DeviceFingerprint.php',
            'security/SessionHijackProtector.php'
        ],
        
        // Security & Validation
        'Security' => [
            'utils/Acl.php',
            'utils/Csrf.php',
            'utils/Firewall.php',
            'utils/Sanitizer.php',
            'utils/Validator.php',
            'security/InputNormalizer.php',
            'security/SecurityScanner.php',
            'encryption.php',
            'captcha.php'
        ],
        
        // Database layer
        'Database' => [
            'database/Database.php',
            'database/DBConfig.php',
            'database/ORM.php',
            'database/QueryBuilder.php',
            'database/Migration.php',
            'database/Seeder.php',
            'database/Transaction.php'
        ],
        
        // Caching system
        'Cache' => [
            'cache.php',
            'services/CacheManager.php',
            'view/ViewCache.php'
        ],
        
        // API layer
        'Api' => [
            'api/ApiRequest.php',
            'api/ApiResponseFormatter.php',
            'api/ApiVersioning.php',
            'api/ApiDocsGenerator.php',
            'api/HttpClient.php',
            'api/ServiceDiscovery.php',
            'misc/ApiAuth.php',
            'misc/ApiRateLimiter.php',
            'misc/ApiResponse.php',
            'json.php'
        ],
        
        // Business logic
        'Business' => [
            'business/ActivityFeed.php',
            'business/ApprovalManager.php',
            'business/NotificationQueue.php',
            'business/RoleHierarchy.php',
            'business/WorkflowEngine.php'
        ],
        
        // Commerce/E-commerce
        'Commerce' => [
            'commerce/DiscountEngine.php',
            'commerce/InvoiceGenerator.php',
            'commerce/MultiCurrency.php',
            'commerce/SubscriptionManager.php',
            'commerce/TaxCalculator.php',
            'cart.php',
            'currency.php'
        ],
        
        // Services layer
        'Services' => [
            'services/Event.php',
            'services/Job.php',
            'services/Queue.php',
            'services/Notification.php',
            'services/PushNotification.php',
            'services/SMS.php',
            'services/Webhook.php',
            'mail.php'
        ],
        
        // View & UI layer
        'View' => [
            'view/template.php',
            'view/Layout.php',
            'view/Component.php',
            'view/AssetPipeline.php',
            'view/SvgIcon.php',
            'misc/AssetManager.php',
            'misc/FormBuilder.php',
            'misc/HtmlHelper.php',
            'misc/ThemeManager.php'
        ],
        
        // Utilities & Helpers
        'Utils' => [
            'helpers/Arr.php',
            'helpers/Date.php',
            'helpers/Env.php',
            'helpers/File.php',
            'helpers/Logger.php',
            'helpers/Number.php',
            'helpers/Path.php',
            'helpers/Str.php',
            'helpers/Timer.php',
            'url--.php',
            'pagination.php',
            'image.php',
            'document.php'
        ],
        
        // File & Media handling
        'Media' => [
            'misc/ImageProcessor.php',
            'misc/Uploader.php'
        ],
        
        // Localization & Language
        'Localization' => [
            'language.php',
            'misc/Locale.php'
        ],
        
        // Modules & Plugins
        'Modules' => [
            'modules/ModuleManager.php',
            'modules/PluginManager.php',
            'modules/FeatureToggle.php',
            'modules/HookManager.php',
            'modules/Lifecycle.php',
            'modules/ServiceRegistry.php',
            'misc/PluginManager.php'
        ],
        
        // Console & CLI
        'Console' => [
            'app/ConsoleKernel.php',
            'app/CLIHelper.php',
            'app/Command.php',
            'app/Scheduler.php'
        ],
        
        // Configuration
        'Config' => [
            'app/ConfigLoader.php'
        ],
        
        // Logging & Monitoring
        'Logging' => [
            'logging/AuditTrail.php',
            'logging/LoggerManager.php',
            'logging/MetricsCollector.php',
            'logging/RequestLogger.php',
            'logging/UsageTracker.php'
        ],
        
        // Testing
        'Testing' => [
            'tests/TestCase.php',
            'tests/Mocker.php',
            'tests/Fuzzer.php',
            'tests/ScenarioRunner.php',
            'tests/TestDataFactory.php'
        ],
        
        // Support & Documentation
        'Support' => [
            'support/Changelog.php',
            'support/DocGenerator.php',
            'support/ErrorCodeMap.php',
            'support/HelpManager.php',
            'support/SupportTicket.php'
        ],
        
        // System & Admin
        'System' => [
            'misc/Backup.php',
            'misc/Installer.php',
            'misc/LicenseManager.php',
            'misc/Version.php',
            'misc/GeoIP.php',
            'misc/Slugger.php',
            'affiliate.php',
            'sgrid.php',
            'icvSoap.php'
        ]
    ];
    
    public function __construct($baseDir, $dryRun = true) {
        $this->baseDir = rtrim($baseDir, '/\\');
        $this->dryRun = $dryRun;
        $this->backupDir = $this->baseDir . '_backup_' . date('Y-m-d_H-i-s');
    }
    
    public function reorganize() {
        echo "=== File Structure Reorganizer ===\n";
        echo "Base Directory: {$this->baseDir}\n";
        echo "Mode: " . ($this->dryRun ? "DRY RUN (no files will be moved)" : "LIVE RUN") . "\n";
        echo "Backup Directory: {$this->backupDir}\n\n";
        
        if (!$this->dryRun) {
            $this->createBackup();
        }
        
        $this->createNewStructure();
        $this->moveFiles();
        $this->generateReport();
    }
    
    private function createBackup() {
        echo "Creating backup...\n";
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Copy entire directory structure
        $this->copyDirectory($this->baseDir, $this->backupDir);
        echo "Backup created at: {$this->backupDir}\n\n";
    }
    
    private function copyDirectory($src, $dst) {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
    
    private function createNewStructure() {
        echo "Creating new directory structure...\n";
        
        foreach (array_keys($this->newStructure) as $directory) {
            $newDir = $this->baseDir . '/' . $directory;
            
            if ($this->dryRun) {
                echo "[DRY RUN] Would create directory: $newDir\n";
            } else {
                if (!is_dir($newDir)) {
                    mkdir($newDir, 0755, true);
                    echo "Created directory: $newDir\n";
                }
            }
        }
        echo "\n";
    }
    
    private function moveFiles() {
        echo "Moving files to new structure...\n";
        $movedFiles = [];
        $notFoundFiles = [];
        
        foreach ($this->newStructure as $newDir => $files) {
            foreach ($files as $oldPath) {
                $oldFullPath = $this->baseDir . '/' . $oldPath;
                $fileName = basename($oldPath);
                $newFullPath = $this->baseDir . '/' . $newDir . '/' . $fileName;
                
                if (file_exists($oldFullPath)) {
                    if ($this->dryRun) {
                        echo "[DRY RUN] Would move: $oldPath -> $newDir/$fileName\n";
                    } else {
                        if (rename($oldFullPath, $newFullPath)) {
                            echo "Moved: $oldPath -> $newDir/$fileName\n";
                            $movedFiles[] = $oldPath;
                        } else {
                            echo "ERROR: Failed to move $oldPath\n";
                        }
                    }
                } else {
                    $notFoundFiles[] = $oldPath;
                    echo "WARNING: File not found: $oldPath\n";
                }
            }
        }
        
        if (!$this->dryRun) {
            $this->cleanupEmptyDirectories();
        }
        
        echo "\n";
    }
    
    private function cleanupEmptyDirectories() {
        echo "Cleaning up empty directories...\n";
        
        $oldDirs = [
            'api', 'app', 'business', 'commerce', 'core', 'database',
            'helpers', 'logging', 'middleware', 'misc', 'modules',
            'security', 'services', 'support', 'tests', 'utils', 'view'
        ];
        
        foreach ($oldDirs as $dir) {
            $dirPath = $this->baseDir . '/' . $dir;
            if (is_dir($dirPath) && $this->isDirectoryEmpty($dirPath)) {
                rmdir($dirPath);
                echo "Removed empty directory: $dir\n";
            }
        }
    }
    
    private function isDirectoryEmpty($dir) {
        $handle = opendir($dir);
        while (($entry = readdir($handle)) !== false) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }
    
    private function generateReport() {
        echo "\n=== REORGANIZATION REPORT ===\n";
        echo "New Structure Overview:\n\n";
        
        foreach ($this->newStructure as $dir => $files) {
            echo "$dir/ (" . count($files) . " files)\n";
            echo "├── " . implode("\n├── ", array_map('basename', $files)) . "\n\n";
        }
        
        if ($this->dryRun) {
            echo "\n*** THIS WAS A DRY RUN - NO FILES WERE ACTUALLY MOVED ***\n";
            echo "To perform the actual reorganization, run with dryRun = false\n";
        } else {
            echo "\nReorganization completed!\n";
            echo "Backup available at: {$this->backupDir}\n";
        }
        
        $this->generateAutoloader();
    }
    
    private function generateAutoloader() {
        $autoloaderContent = '<?php
/**
 * Auto-generated Autoloader for reorganized class structure
 */

class CTFrameAutoloader {
    private static $classMap = [
        // Core
        \'App\' => \'Core/App.php\',
        \'Controller\' => \'Core/Controller.php\',
        \'Model\' => \'Core/Model.php\',
        \'View\' => \'Core/View.php\',
        \'Router\' => \'Core/Router.php\',
        
        // Database
        \'Database\' => \'Database/Database.php\',
        \'ORM\' => \'Database/ORM.php\',
        \'QueryBuilder\' => \'Database/QueryBuilder.php\',
        
        // Add more mappings as needed...
    ];
    
    public static function register() {
        spl_autoload_register([__CLASS__, \'load\']);
    }
    
    public static function load($className) {
        if (isset(self::$classMap[$className])) {
            $file = __DIR__ . \'/\' . self::$classMap[$className];
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }
}

// Register the autoloader
CTFrameAutoloader::register();
';
        
        $autoloaderPath = $this->baseDir . '/autoload.php';
        
        if ($this->dryRun) {
            echo "\n[DRY RUN] Would create autoloader at: $autoloaderPath\n";
        } else {
            file_put_contents($autoloaderPath, $autoloaderContent);
            echo "\nGenerated new autoloader: autoload.php\n";
        }
    }
}

// Usage
if (php_sapi_name() === 'cli') {
    $baseDir = isset($argv[1]) ? $argv[1] : __DIR__;
    $dryRun = !isset($argv[2]) || $argv[2] !== 'live';
    
    $reorganizer = new FileReorganizer($baseDir, $dryRun);
    $reorganizer->reorganize();
} else {
    echo "This script should be run from command line.\n";
    echo "Usage: php reorganize.php [directory] [live]\n";
    echo "Example: php reorganize.php C:\\wamp64\\www\\me\\CT\\ct_frame\\brain\\Classes live\n";
}
?>